<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingEvidence;
use App\Models\InstantOffer;
use App\Services\InstantMarketplace;
use App\Services\InstantFinance;
use App\Services\InstantImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class InstantBookingController extends Controller
{
    private const REPORT_TYPES = [
        'arrival_verification_issue' => 'رفض أو تعذّر التحقق من الوصول',
        'completion_issue' => 'تعذّر تأكيد الإنجاز',
        'non_execution' => 'عدم تنفيذ الخدمة',
        'partial_service' => 'تنفيذ جزئي',
        'stuck_booking' => 'الحجز عالق',
        'exceptional_cancellation' => 'طلب إلغاء استثنائي',
        'suspected_fraud' => 'اشتباه بالاحتيال',
    ];

    public function __construct(private InstantMarketplace $market) {}

    public function show(Request $request, Booking $booking)
    {
        $vendor = $this->participant($request, $booking, true);
        $admin = $request->user()->role === 'admin';
        $offer = InstantOffer::with('instantRequest')->findOrFail($booking->instant_offer_id);
        $events = DB::table('booking_events')->where('booking_id', $booking->id)->latest('id')->paginate(12);
        $adjustment = $this->pendingAdjustment($booking)->first();
        $booking->load(['payment', 'refundRequest', 'cases', 'evidence']);
        if ($admin) {
            $booking->load(['customer', 'vendorService.vendor', 'walletTransactions']);
        }
        $objectionImages = ! $vendor && $booking->refundRequest
            ? BookingEvidence::where('refund_request_id', $booking->refundRequest->id)->where('kind', 'objection')->where('is_active', true)->get() : [];
        $caseImages = BookingEvidence::whereIn('refund_request_id', $booking->cases->modelKeys())
            ->where('kind', 'exceptional_case')->where('is_active', true)->get()->groupBy('refund_request_id');
        $operationalCases = $booking->cases->where('case_type', '!=', 'objection');
        $compensationCase = $operationalCases->first(fn ($case) => $case->compensation_amount !== null);
        $hasBlockingCases = $booking->cases->contains('status', 'pending');
        $reportTypes = self::REPORT_TYPES;
        $arrival = $admin ? DB::table('booking_events')->where('booking_id', $booking->id)->where('event_type', 'arrival_recorded')->latest('id')->first() : null;
        $code = $vendor && $booking->status === 'arrived' && $booking->arrival_code_encrypted && $this->codeStillValid($booking)
            ? Crypt::decryptString($booking->arrival_code_encrypted) : null;

        return response()->view('pages.instant.booking', compact('booking', 'vendor', 'admin', 'offer', 'events', 'adjustment', 'code', 'objectionImages', 'caseImages', 'arrival', 'operationalCases', 'compensationCase', 'hasBlockingCases', 'reportTypes'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function image(Request $request, Booking $booking, BookingEvidence $image)
    {
        $vendor = $this->participant($request, $booking, true);
        $completion = (int) $image->booking_id === $booking->id && in_array($image->kind, ['before', 'after'], true);
        $objection = ! $vendor && $image->kind === 'objection' && $image->refund_request_id
            && $booking->refundRequest()->whereKey($image->refund_request_id)->exists();
        $caseEvidence = $image->kind === 'exceptional_case' && $image->refund_request_id
            && $booking->cases()->whereKey($image->refund_request_id)->exists()
            && ($request->user()->role === 'admin' || (int) $image->uploaded_by === $request->user()->id);
        abort_unless(($completion || $objection || $caseEvidence) && $image->is_active && str_starts_with($image->path, 'instant-evidence/'), 404);

        return Storage::disk('local')->download($image->path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function pay(Request $request, Booking $booking, InstantFinance $finance)
    {
        $this->participant($request, $booking);
        if ($booking->status === 'cancelled') {
            $finance->payCancellationDebt($booking, $request->user()->id);
        } else {
            $finance->collect($booking, $request->user()->id);
        }

        return back()->with('success', 'تم تسجيل الدفع التجريبي.');
    }

    public function object(Request $request, Booking $booking)
    {
        abort_if($this->participant($request, $booking), 404);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000'], ...InstantImages::rules('images')]);
        $paths = [];
        try {
            DB::transaction(function () use ($booking, $data, &$paths) {
                $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
                abort_unless($current->status === 'completed' && $current->objection_deadline?->isFuture() && ! $current->settled_at && ! $current->refundRequest()->exists(), 409);
                abort_if($current->cases()->where('case_type', '!=', 'objection')->where('status', 'accepted')
                    ->whereNotNull('approved_service_amount')->exists(), 409, 'الحجز له قرار مالي استثنائي معتمد.');
                $case = $current->refundRequest()->create(['reason' => $data['reason'], 'submitted_by' => $current->customer_id, 'status' => 'pending']);
                app(InstantImages::class)->save($data['images'] ?? [], 'objection', 'refund_request_id', $case->id, $current->customer_id, $paths);
                $this->market->notify($current->vendorService->vendor_id, 'العميل قدّم اعتراض على الحجز الفوري.');
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }

        return back()->with('success', 'تم إرسال الاعتراض وإيقاف التسوية لحين المراجعة.');
    }

    public function decide(Request $request, Booking $booking, InstantFinance $finance)
    {
        abort_unless(config('instant.enabled'), 404);
        $data = $request->validate(['decision' => ['required', 'in:accepted,rejected'], 'reason' => ['required', 'string', 'max:2000']]);
        $finance->decide($booking, $request->user()->id, $data['decision'], $data['reason']);

        return redirect()->route('instant.booking', $booking)->with('success', 'تم تسجيل قرار الاعتراض.');
    }

    public function noShow(Request $request, Booking $booking, InstantImages $images)
    {
        abort_unless($this->participant($request, $booking), 404);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000'], ...InstantImages::rules('images')]);
        $paths = [];
        try {
            DB::transaction(function () use ($request, $booking, $data, $images, &$paths) {
                $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
                abort_unless($this->participant($request, $current) && $current->status === 'arrived' && ! $current->arrival_verified_at
                    && $current->arrived_at?->copy()->addMinutes(10)->lte(now())
                    && ! $current->cases()->where('case_type', 'customer_no_show')->whereIn('status', ['pending', 'accepted'])->exists(), 409);
                abort_unless(DB::table('booking_events')->where('booking_id', $current->id)->where('event_type', 'arrival_recorded')
                    ->whereNotNull('latitude')->whereNotNull('longitude')->exists(), 409);
                $case = $current->cases()->make(['reason' => $data['reason'], 'submitted_by' => $request->user()->id, 'status' => 'pending']);
                $case->forceFill(['case_type' => 'customer_no_show', 'submitted_by' => $request->user()->id])->save();
                $images->save($data['images'] ?? [], 'exceptional_case', 'refund_request_id', $case->id, $request->user()->id, $paths);
                $this->market->notify($current->customer_id, 'رفع الفني طلب مراجعة عدم الحضور للإدارة.');
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }

        return back()->with('success', 'تم رفع طلب مراجعة عدم الحضور.');
    }

    public function decideOperationalCase(Request $request, Booking $booking, InstantFinance $finance)
    {
        $this->participant($request, $booking, true);
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate([
            'case_id' => ['nullable', 'integer'], 'decision' => ['required', 'in:accepted,rejected'],
            'reason' => ['required', 'string', 'max:2000'], 'close_without_charge' => ['nullable', 'boolean'],
            'responsibility' => ['nullable', 'in:customer,vendor'],
            'service_amount' => ['nullable', 'numeric', 'decimal:0,3', 'between:0,9999999.999'],
            'confirm_financial_resolution' => ['sometimes', 'accepted'],
        ]);
        if ($data['decision'] === 'accepted' && (isset($data['service_amount']) || ($data['close_without_charge'] ?? false))) {
            abort_unless(isset($data['case_id']) && (($data['close_without_charge'] ?? false) || isset($data['confirm_financial_resolution'])), 422);
            abort_if(($data['close_without_charge'] ?? false) && isset($data['service_amount']), 422);
            $finance->resolveExceptionalService($booking, $data['case_id'], $request->user()->id,
                (string) ($data['service_amount'] ?? '0.000'), $data['reason'], $data['responsibility'] ?? null);

            return back()->with('success', 'تم اعتماد القرار المالي للحالة.');
        }
        $vendor = $booking->vendorService->vendor_id;
        DB::transaction(function () use ($request, $booking, $data, $finance, $vendor) {
            \App\Models\User::query()->whereKey([$booking->customer_id, $vendor])->orderBy('id')->lockForUpdate()->get();
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $cases = $current->pendingCases()->where('case_type', '!=', 'objection')
                ->when(isset($data['case_id']), fn ($rows) => $rows->whereKey($data['case_id']))->lockForUpdate()->limit(2)->get();
            abort_if($cases->isEmpty(), 404);
            abort_unless($cases->count() === 1, 409, 'اختار الحالة المطلوب مراجعتها.');
            $case = $cases->first();
            $penaltyCase = in_array($case->case_type, ['customer_no_show', 'vendor_late'], true);
            abort_if($data['decision'] === 'accepted' && in_array($case->case_type, ['partial_service', 'non_execution', 'exceptional_cancellation'], true), 422, 'حدد قيمة العمل المعتمدة.');
            $case->forceFill(['status' => $data['decision'], 'admin_response' => $data['reason'], 'reviewed_at' => now(), 'reviewed_by' => $request->user()->id])->save();
            if ($data['decision'] === 'accepted' && $penaltyCase) {
                abort_if($current->pendingCases()->exists() || $current->refundRequest?->status === 'accepted', 409, 'في حالة أخرى تؤثر على القرار المالي.');
                $late = $case->case_type === 'vendor_late';
                $finance->cancellationPenalty($current, $late, $case);
                if (! $late) {
                    $current->forceFill(['status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => $request->user()->id])->save();
                }
            }
            $this->market->event($current, $request->user()->id, [
                'event_type' => 'admin_decision', 'description' => $data['reason'], 'new_status' => $current->status,
            ]);
            $this->market->notify($current->customer_id, 'اتخذت الإدارة قرارًا بشأن مراجعة الحجز الفوري.');
            $this->market->notify($vendor, 'اتخذت الإدارة قرارًا بشأن مراجعة الحجز الفوري.');
        }, 3);

        return back()->with('success', 'تم تسجيل قرار الإدارة.');
    }

    public function reportCase(Request $request, Booking $booking, InstantImages $images)
    {
        $this->participant($request, $booking);
        $data = $request->validate([
            'case_type' => ['required', \Illuminate\Validation\Rule::in(array_keys(self::REPORT_TYPES))],
            'reason' => ['required', 'string', 'max:2000'],
            ...InstantImages::rules('images'),
        ]);
        $paths = [];
        try {
            DB::transaction(function () use ($request, $booking, $data, $images, &$paths) {
                $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
                $vendor = $this->participant($request, $current);
                $actor = $request->user()->id;
                $allowed = match ($data['case_type']) {
                    'arrival_verification_issue' => $vendor && $current->status === 'arrived' && ! $current->arrival_verified_at,
                    'completion_issue' => ! $vendor && $current->status === 'in_progress',
                    'partial_service' => in_array($current->status, ['in_progress', 'completed'], true),
                    'exceptional_cancellation' => in_array($current->status, ['in_progress', 'completed'], true),
                    'non_execution' => in_array($current->status, ['arrived', 'in_progress', 'completed'], true),
                    'stuck_booking' => in_array($current->status, InstantMarketplace::ACTIVE_BOOKINGS, true),
                    'suspected_fraud' => true,
                };
                abort_unless($allowed && (! $current->settled_at || $data['case_type'] === 'suspected_fraud'), 409);
                abort_if($current->cases()->where('case_type', $data['case_type'])->where('submitted_by', $actor)
                    ->where('reason', $data['reason'])->exists(), 409, 'هذا البلاغ مسجّل بالفعل.');
                $case = $current->cases()->make(['reason' => $data['reason'], 'submitted_by' => $request->user()->id, 'status' => 'pending']);
                $case->forceFill(['case_type' => $data['case_type'], 'submitted_by' => $actor])->save();
                $images->save($data['images'] ?? [], 'exceptional_case', 'refund_request_id', $case->id, $actor, $paths);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }

        return back()->with('success', 'تم إرسال الحالة للإدارة.');
    }

    public function fund(Request $request, Booking $booking, InstantFinance $finance)
    {
        abort_unless(config('instant.enabled'), 404);
        $funded = $finance->fundVendor($booking, $request->user()->id);

        return back()->with($funded ? 'success' : 'error', $funded
            ? 'تم تسجيل تمويل مستحق الفني من الرصيد التجريبي.'
            : 'الرصيد التجريبي غير كافٍ. بقي المستحق معلّقًا.');
    }

    public function correctOverpayment(Request $request, Booking $booking, int $entry, InstantFinance $finance)
    {
        abort_unless(config('instant.enabled'), 404);
        $data = $request->validate([
            'corrected_entitlement' => ['required', 'numeric', 'decimal:0,3', 'between:0,9999999.999'],
            'reason' => ['required', 'string', 'max:2000'], 'confirm' => ['accepted'],
        ]);
        $finance->recordOverpayment($booking, $entry, $request->user()->id, (string) $data['corrected_entitlement'], $data['reason']);

        return back()->with('success', 'تم توثيق تصحيح الدفعة وتسجيل الدين.');
    }

    public function repayOverpayment(Request $request, int $debt, InstantFinance $finance)
    {
        abort_unless(config('instant.enabled'), 404);
        $finance->repayOverpayment($debt, $request->user()->id);

        return back()->with('success', 'تم تسجيل السداد التجريبي الكامل.');
    }

    public function fundCompensation(Request $request, Booking $booking, InstantFinance $finance)
    {
        abort_unless(config('instant.enabled'), 404);
        $funded = $finance->fundCompensation($booking, $request->user()->id);

        return back()->with($funded ? 'success' : 'error', $funded
            ? 'تم تسجيل تمويل التعويض من الرصيد التجريبي.'
            : 'الرصيد التجريبي غير كافٍ. بقي التعويض معلّقًا.');
    }

    public function transition(Request $request, Booking $booking)
    {
        $this->participant($request, $booking);
        $action = $request->validate(['action' => ['required', 'in:depart,arrive,reissue,verify,start,complete,cancel']])['action'];
        if ($action === 'verify') {
            return $this->verify($request, $booking);
        }
        $images = $action === 'complete' ? $request->validate([...InstantImages::rules('before_images'), ...InstantImages::rules('after_images')]) : [];
        $paths = [];
        try {
            DB::transaction(function () use ($request, $booking, $action, $images, &$paths) {
            if ($action === 'cancel') {
                \App\Models\User::query()->whereKey([$booking->customer_id, $booking->vendorService->vendor_id])->orderBy('id')->lockForUpdate()->get();
            }
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $vendor = $this->participant($request, $current);
            abort_if($current->pendingCases()->exists(), 409);
            $actor = $request->user()->id;
            $event = ['event_type' => 'status_change', 'status_type' => 'operational', 'previous_status' => $current->status];
            $changes = match ($action) {
                'depart' => $this->depart($current, $vendor),
                'arrive' => $this->arrive($current, $request, $vendor, $event),
                'reissue' => $this->reissue($current, $vendor),
                'start' => $this->start($current, $vendor),
                'complete' => $this->complete($current, $request, $vendor),
                'cancel' => $this->cancel($current, $actor, $vendor, $event),
            };
            $current->forceFill($changes)->save();
            foreach (['before', 'after'] as $kind) {
                app(InstantImages::class)->save($images[$kind.'_images'] ?? [], $kind, 'booking_id', $current->id, $actor, $paths);
            }
            $this->market->event($current, $actor, $event + ['new_status' => $current->status]);
            $this->market->notify($vendor ? $current->customer_id : $current->vendorService->vendor_id, 'تحدّثت حالة حجزك الفوري رقم '.$current->id.'.');
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }

        return back()->with('success', 'تم تحديث الحجز الفوري.');
    }

    public function adjust(Request $request, Booking $booking)
    {
        $this->participant($request, $booking);
        $data = $request->validate(['decision' => ['required', 'in:propose,accepted,rejected,withdrawn']]);
        DB::transaction(function () use ($request, $booking, $data) {
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $vendor = $this->participant($request, $current);
            $actor = $request->user()->id;
            abort_unless(in_array($current->status, ['arrived', 'in_progress'], true), 409);
            abort_if($current->pendingCases()->exists(), 409);
            $pending = $this->pendingAdjustment($current)->first();
            if ($data['decision'] === 'propose') {
                abort_unless($vendor && ! $pending, 409);
                $proposal = $request->validate(['proposed_price' => ['required', 'numeric', 'decimal:0,3', 'between:0.001,9999999.999'], 'description' => ['required', 'string', 'max:2000']]);
                $this->market->event($current, $actor, $proposal + ['event_type' => 'price_adjustment', 'previous_price' => $current->agreed_price, 'adjustment_status' => 'pending']);
            } else {
                $choice = $data['decision'];
                abort_unless($pending && ($vendor === ($choice === 'withdrawn')), 409);
                $request->validate(['adjustment_id' => ['required', 'integer']]);
                abort_unless((int) $pending->id === $request->integer('adjustment_id'), 409);
                $this->pendingAdjustment($current)->where('id', $pending->id)->update(['adjustment_status' => $choice, 'resolved_by' => $actor, 'resolved_at' => now()]);
                if ($choice === 'accepted') {
                    $current->forceFill(['agreed_price' => $pending->proposed_price])->save();
                }
            }
            $this->market->notify($vendor ? $current->customer_id : $current->vendorService->vendor_id, 'تحديث على تعديل سعر حجزك الفوري رقم '.$current->id.'.');
        }, 3);

        return back()->with('success', 'تم تسجيل تحديث السعر.');
    }

    private function depart(Booking $booking, bool $vendor): array
    {
        abort_unless($vendor && $booking->status === 'confirmed', 409);

        return ['status' => 'on_the_way', 'departed_at' => now()];
    }

    private function arrive(Booking $booking, Request $request, bool $vendor, array &$event): array
    {
        abort_unless($vendor && $booking->status === 'on_the_way', 409);
        $gps = $request->validate(['latitude' => ['required', 'numeric', 'between:-90,90'], 'longitude' => ['required', 'numeric', 'between:-180,180']], [
            'latitude.required' => 'حدد موقعك الحالي لتسجيل الوصول.',
            'longitude.required' => 'حدد موقعك الحالي لتسجيل الوصول.',
        ]);
        $event = $gps + ['event_type' => 'arrival_recorded', 'status_type' => 'operational', 'previous_status' => $booking->status];

        return ['status' => 'arrived', 'arrived_at' => now()] + $this->newCode();
    }

    private function reissue(Booking $booking, bool $vendor): array
    {
        abort_unless($vendor && $booking->status === 'arrived' && ! $booking->arrival_verified_at, 409);
        abort_if($booking->arrival_code_locked_until?->isFuture(), 429);

        return $this->newCode($booking->arrival_code_hash);
    }

    private function newCode(?string $previousHash = null): array
    {
        $length = config('instant.arrival_code_length');
        do {
            $code = str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
        } while ($previousHash && Hash::check($code, $previousHash));

        return ['arrival_code_hash' => Hash::make($code), 'arrival_code_encrypted' => Crypt::encryptString($code),
            'arrival_code_expires_at' => now()->addSeconds(config('instant.arrival_code_valid_seconds'))];
    }

    private function verify(Request $request, Booking $booking)
    {
        $code = $request->input('arrival_code');
        $request->replace($request->except('arrival_code'));
        if (! is_string($code) || ! preg_match('/^\d{6}$/D', $code)) {
            throw ValidationException::withMessages(['arrival_code' => 'أدخل رمز الوصول من ستة أرقام.']);
        }
        $matched = DB::transaction(function () use ($request, $booking, $code) {
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            abort_unless(! $this->participant($request, $current) && $current->status === 'arrived'
                && ! $current->arrival_verified_at && $this->codeStillValid($current), 409);
            abort_if($current->pendingCases()->exists(), 409);
            abort_if($current->arrival_code_locked_until?->isFuture(), 429);
            if (! $current->arrival_code_hash || ! Hash::check($code, $current->arrival_code_hash)) {
                $attempts = ($current->arrival_code_locked_until ? 0 : $current->arrival_code_failed_attempts) + 1;
                $locked = $attempts >= config('instant.arrival_code_attempt_limit') ? now()->addSeconds(config('instant.arrival_code_lock_seconds')) : null;
                $current->forceFill(['arrival_code_failed_attempts' => $attempts, 'arrival_code_locked_until' => $locked])->save();

                return false;
            }
            $current->forceFill(['arrival_verified_at' => now(), 'arrival_code_hash' => null, 'arrival_code_encrypted' => null,
                'arrival_code_failed_attempts' => 0, 'arrival_code_locked_until' => null, 'arrival_code_expires_at' => null])->save();
            $this->market->event($current, $request->user()->id, ['event_type' => 'arrival_verified']);
            $this->market->notify($current->vendorService->vendor_id, 'تم التحقق من الوصول لحجزك الفوري.');

            return true;
        }, 3);
        if (! $matched) {
            throw ValidationException::withMessages(['arrival_code' => 'رمز الوصول غير صحيح.']);
        }

        return back()->with('success', 'تم التحقق من وصول الفني.');
    }

    private function start(Booking $booking, bool $vendor): array
    {
        abort_unless($vendor && $booking->status === 'arrived' && $booking->arrival_verified_at, 409);

        return ['status' => 'in_progress', 'started_at' => now()];
    }

    private function complete(Booking $booking, Request $request, bool $vendor): array
    {
        abort_unless($vendor && $booking->status === 'in_progress' && $booking->arrival_verified_at && ! $this->pendingAdjustment($booking)->exists(), 409);
        $data = $request->validate(['completion_description' => ['required', 'string', 'max:2000']]);
        $now = now();

        return $data + ['status' => 'completed', 'completed_at' => $now, 'vendor_completed_at' => $now,
            'payment_deadline' => $now->copy()->addHours(24), 'objection_deadline' => $now->copy()->addHours(24), 'settlement_status' => 'on_hold'];
    }

    private function cancel(Booking $booking, int $actor, bool $vendor, array &$event): array
    {
        $late = $booking->status === 'on_the_way' && $booking->expected_arrival_at?->copy()->addMinutes(10)->lte(now());
        abort_unless(in_array($booking->status, ['confirmed', 'on_the_way', 'arrived'], true), 409);
        $event['event_type'] = 'cancellation';
        if ($vendor || ($booking->status !== 'confirmed' && ! $late)) {
            app(InstantFinance::class)->cancellationPenalty($booking, $vendor);
        } elseif ($late) {
            $case = $booking->cases()->make(['reason' => 'إلغاء بعد تجاوز مهلة الوصول', 'submitted_by' => $actor, 'status' => 'pending']);
            $case->forceFill(['case_type' => 'vendor_late', 'submitted_by' => $actor])->save();
        }

        return ['status' => 'cancelled', 'cancelled_by' => $actor, 'cancelled_at' => now()];
    }

    private function codeStillValid(Booking $booking): bool
    {
        return $booking->arrival_code_expires_at?->isFuture() ?? false;
    }

    private function pendingAdjustment(Booking $booking)
    {
        return DB::table('booking_events')->where('booking_id', $booking->id)->where('event_type', 'price_adjustment')->where('adjustment_status', 'pending');
    }

    private function participant(Request $request, Booking $booking, bool $allowAdmin = false): bool
    {
        $actor = $request->user();
        $vendor = $actor?->role === 'vendor' && $actor->id === (int) $booking->vendorService?->vendor_id;
        abort_unless(config('instant.enabled') && $booking->booking_type === 'instant' && $actor?->status === 'active'
            && ($vendor || $actor->id === (int) $booking->customer_id || ($allowAdmin && $actor->role === 'admin')), 404);

        return $vendor;
    }
}
