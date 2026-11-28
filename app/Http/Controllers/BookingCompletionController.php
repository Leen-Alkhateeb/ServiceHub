<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingEvidence;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BookingCompletionController extends Controller
{
    private const IMAGES = ['evidence' => ['nullable', 'array', 'max:4'], 'evidence.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']];

    public function evidence(Request $request, Booking $booking, BookingEvidence $evidence): StreamedResponse
    {
        $user = $request->user();
        $customer = (int) $booking->customer_id === (int) $user->id;
        $vendor = (int) $booking->vendorService?->vendor_id === (int) $user->id;
        abort_unless((int) $evidence->booking_id === (int) $booking->id && $user->status === 'active'
            && ($customer || ($vendor && $evidence->kind === 'completion') || $user->role === 'admin'), 404);

        $disk = Storage::disk('local');
        abort_unless(str_starts_with($evidence->path, 'booking-evidence/') && $disk->exists($evidence->path), 404);

        return $disk->download($evidence->path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function confirm(Request $request, Booking $booking): RedirectResponse
    {
        abort_if($booking->booking_type === 'instant', 404);
        $actor = (int) $request->user()->id;
        $isVendor = $actor === (int) $booking->vendorService?->vendor_id && $actor !== (int) $booking->customer_id;
        $data = $isVendor ? $request->validate(['completion_description' => ['nullable', 'string', 'max:2000'], ...self::IMAGES]) : [];
        $message = $this->withEvidence(function (array &$paths) use ($request, $booking, $data) {
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $actor = (int) $request->user()->id;
            $customer = (int) $current->customer_id;
            $vendor = (int) $current->vendorService->vendor_id;
            abort_unless(in_array($actor, [$customer, $vendor], true), 403);

            $own = $actor === $customer ? 'customer_completed_at' : 'vendor_completed_at';
            if ($current->{$own}) {
                return 'تم تسجيل تأكيدك مسبقًا.';
            }

            abort_unless($actor === $customer ? $current->acceptsCustomerAcknowledgment() : $current->acceptsCompletionConfirmation(), 403);
            $confirmedAt = now();
            $current->{$own} = $confirmedAt;
            $legacyConfirmation = $actor === $customer && $current->vendor_completed_at && ! $current->objection_deadline;
            if ($actor === $vendor && $actor !== $customer) {
                $current->completion_description = $data['completion_description'] ?? null;
                $this->saveEvidence($current, $data['evidence'] ?? [], 'completion', $paths);
            }
            if ($actor !== $customer || $legacyConfirmation) {
                $current->status = 'completed';
                $current->completed_at = $confirmedAt;
                $current->objection_deadline = $confirmedAt->copy()->addHours(24);
            }
            $current->save();

            $message = $actor !== $customer || $legacyConfirmation
                ? 'أكد الفني إنجاز الخدمة. عند العميل 24 ساعة لتقديم اعتراض من تفاصيل الحجز.'
                : 'تم تسجيل تأكيد العميل. تأكيد العميل لا يغيّر مهلة الاعتراض أو موعد تحرير المستحقات.';

            Notification::create([
                'user_id' => $actor === $customer ? $vendor : $customer,
                'title' => 'تحديث اكتمال الحجز',
                'message' => $message,
                'type' => 'booking',
            ]);

            return $message;
        });

        return back()->with('success', $message);
    }

    public function review(Request $request, Booking $booking): RedirectResponse
    {
        abort_if($booking->booking_type === 'instant', 404);
        abort_unless((int) $booking->customer_id === (int) $request->user()->id, 403);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($booking, $data) {
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            abort_unless($current->status === 'completed' && $current->completed_at, 403);
            $current->reviews()->updateOrCreate(
                ['reviewer_id' => $current->customer_id],
                $data + ['reviewed_user_id' => $current->vendorService->vendor_id, 'comment' => null, 'is_active' => true],
            );
        });

        return back()->with('success', 'تم حفظ تقييم الفني.');
    }

    public function object(Request $request, Booking $booking): RedirectResponse
    {
        abort_if($booking->booking_type === 'instant', 404);
        abort_unless((int) $booking->customer_id === (int) $request->user()->id, 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000'], ...self::IMAGES]);

        $this->withEvidence(function (array &$paths) use ($booking, $data) {
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            abort_unless($current->status === 'completed' && $current->objection_deadline?->isFuture(), 403);
            abort_if($current->refundRequest()->exists() || $current->walletTransactions()
                ->whereIn('transaction_type', ['credit', 'payout'])->exists(), 409);

            $current->refundRequest()->create(['reason' => $data['reason'], 'submitted_by' => $current->customer_id, 'status' => 'pending']);
            $this->saveEvidence($current, $data['evidence'] ?? [], 'objection', $paths);
        });

        return back()->with('success', 'تم إرسال الاعتراض للإدارة وإيقاف تحرير المبلغ لحين المراجعة.');
    }

    private function withEvidence(callable $callback): mixed
    {
        $paths = [];
        try {
            return DB::transaction(function () use ($callback, &$paths) {
                return $callback($paths);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }
    }

    private function saveEvidence(Booking $booking, array $images, string $kind, array &$paths): void
    {
        foreach ($images as $image) {
            $path = $image->store('booking-evidence', 'local');
            if (! $path) {
                throw ValidationException::withMessages(['evidence' => 'تعذر حفظ صورة مرفقة. حاول مرة ثانية.']);
            }
            $paths[] = $path;
            $booking->evidence()->create(['kind' => $kind, 'path' => $path]);
        }
    }
}
