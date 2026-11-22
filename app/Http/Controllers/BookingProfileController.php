<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\RecurringAvailability;
use App\Models\User;
use App\Models\VendorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingProfileController extends Controller
{
    /** إرسال طلب الحجز إلى الفني للموافقة. */
    public function store(Request $request, VendorService $vendorService)
    {
        $customer = $request->user();

        $vendorService->loadMissing([
            'service.category',
            'vendor.vendorProfile',
        ]);

        abort_unless(
            $customer
            && $customer->status === 'active'
            && $vendorService->status === 'active'
            && $vendorService->vendor?->status === 'active'
            && $vendorService->vendor->vendorProfile?->application_status === 'approved'
            && $vendorService->service?->approval_status === 'approved'
            && $vendorService->service->is_active
            && ! $vendorService->service->is_deleted
            && ! $vendorService->service->category?->is_deleted
            && (int) $vendorService->vendor_id !== (int) $customer->id,
            403
        );

        $request->validate(
            [
                'slot' => [
                    'required',
                    'string',
                    'regex:/^\d{4}-\d{2}-\d{2}\|\d{2}:\d{2}\|\d+$/D',
                ],
            ],
            [
                'slot.required' => 'يرجى اختيار موعد.',
                'slot.string' => 'الموعد المختار غير صالح.',
                'slot.regex' => 'الموعد المختار غير صالح.',
            ]
        );

        // صيغة الموعد: YYYY-MM-DD|HH:MM|availability_id
        [$date, $start, $availabilityId] = explode('|', $request->input('slot'));

        try {
            $startAt = Carbon::createFromFormat('!Y-m-d H:i', "$date $start", Booking::SCHEDULE_TIMEZONE);
        } catch (\Throwable $exception) {
            $startAt = null;
        }

        if (! $startAt || $startAt->format('Y-m-d H:i') !== "$date $start") {
            return back()
                ->withErrors(['slot' => 'الموعد المختار غير صالح.'])
                ->withInput();
        }

        $duration = max(5, (int) ($vendorService->duration_minutes ?: 60));
        $endAt = $startAt->copy()->addMinutes($duration);

        $window = RecurringAvailability::query()
            ->whereKey($availabilityId)
            ->where('vendor_id', $vendorService->vendor_id)
            ->where('is_active', true)
            ->first();

        $windowStart = $window ? $startAt->copy()->setTimeFromTimeString($window->start_time) : null;
        $valid = $window
            && $startAt->isFuture()
            && $startAt->lte(today(Booking::SCHEDULE_TIMEZONE)->addDays(29)->endOfDay())
            && $startAt->dayOfWeek === (int) $window->day_of_week
            && $startAt->gte($windowStart)
            && ($startAt->timestamp - $windowStart->timestamp) % (Booking::SLOT_STEP_MINUTES * 60) === 0
            && $endAt->isSameDay($startAt)
            && $endAt->format('H:i') <= substr($window->end_time, 0, 5);

        if (! $valid) {
            return back()
                ->withErrors(['slot' => 'هذا الموعد غير متاح. اختر موعدًا آخر.'])
                ->withInput();
        }

        $booking = DB::transaction(function () use (
            $vendorService,
            $customer,
            $date,
            $startAt,
            $endAt,
            $window
        ) {
            User::query()->lockForUpdate()->findOrFail($vendorService->vendor_id);

            // منع حجز موعد يتداخل مع موعد آخر لمقدّم الخدمة.
            $taken = Booking::query()
                ->whereHas(
                    'vendorService',
                    fn ($query) => $query->where(
                        'vendor_id',
                        $vendorService->vendor_id
                    )
                )
                ->whereDate('booking_date', $date)
                ->where('start_time', '<', $endAt->format('H:i:s'))
                ->where('end_time', '>', $startAt->format('H:i:s'))
                ->blockingSlot()
                ->lockForUpdate()
                ->exists();

            if ($taken) {
                return null;
            }

            $booking = Booking::create([
                'customer_id' => $customer->id,
                'vendor_service_id' => $vendorService->id,
                'availability_id' => $window->id,
                'booking_date' => $date,
                'start_time' => $startAt->format('H:i:s'),
                'end_time' => $endAt->format('H:i:s'),
                'service_name' => $vendorService->service->name,
                'service_description' => $vendorService->description
                    ?? $vendorService->service->description,
                'original_price' => $vendorService->price,
                'status' => 'pending',
            ]);

            Notification::create([
                'user_id' => $vendorService->vendor_id,
                'title' => 'طلب حجز جديد بانتظار ردك',
                'message' => "اختار {$customer->first_name} {$customer->last_name} خدمة {$booking->service_name} بتاريخ {$startAt->format('Y-m-d H:i')}.",
                'type' => 'booking',
            ]);

            return $booking;
        });

        if (! $booking) {
            return back()
                ->withErrors([
                    'slot' => 'عذرًا، تم حجز هذا الموعد للتو. اختر موعدًا آخر.',
                ])
                ->withInput();
        }

        $historyPage = $customer->role === 'customer'
            ? route('customer.bookings')
            : route('account.profile', ['tab' => 'bookings']);

        return redirect($historyPage)
            ->with('success', 'تم إرسال طلب الحجز. يمكنك دفع كامل قيمة الخدمة بعد موافقة الفني.');
    }

    public function cancel(Request $request, Booking $booking)
    {
        $actor = $request->user();
        DB::transaction(function () use ($actor, $booking) {
            $booking = Booking::query()->with(['vendorService', 'payment'])->lockForUpdate()->findOrFail($booking->id);
            $vendorId = (int) $booking->vendorService->vendor_id;
            $customerId = (int) $booking->customer_id;
            $actorId = (int) $actor->id;
            $byCustomer = $actorId === $customerId;
            abort_unless($actor->status === 'active' && ($byCustomer || $actorId === $vendorId) && $booking->canBeCancelled(), 403);
            abort_if(! $byCustomer && $booking->status === 'pending', 403);

            $now = now();
            $paid = $booking->payment?->status === 'paid' ? (int) round($booking->payment->amount * 100) : 0;
            $price = (int) round($booking->original_price * 100);
            $penalty = $paid > 0 ? ($byCustomer ? min($paid, (int) round($price * 0.30)) : (int) round($price * 0.15)) : 0;
            $vendorAmount = $byCustomer ? intdiv($penalty, 2) : -$penalty;
            $platformAmount = $byCustomer ? $penalty - $vendorAmount : 0;
            $refund = $paid - ($byCustomer ? $penalty : 0);

            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => $now,
                'cancelled_by' => $actorId,
                'cancellation_penalty' => $penalty / 100,
                'cancellation_refund_amount' => $refund / 100,
                'cancellation_platform_share' => $platformAmount / 100,
            ]);

            if ($paid > 0 && $refund === $paid) {
                $booking->payment->update(['status' => 'refunded']);
            }
            if ($vendorAmount !== 0) {
                $booking->walletTransactions()->create([
                    'vendor_id' => $vendorId,
                    'transaction_type' => $byCustomer ? 'refund_share' : 'penalty',
                    'amount' => $vendorAmount / 100,
                    'description' => $byCustomer ? 'حصة الفني من غرامة إلغاء العميل' : 'غرامة إلغاء الحجز من الفني',
                ]);
            }

            Notification::create([
                'user_id' => $byCustomer ? $vendorId : $customerId,
                'title' => 'تم إلغاء الحجز',
                'message' => "ألغى {$actor->first_name} حجز خدمة {$booking->service_name}.",
                'type' => 'booking',
            ]);
        }, 3);

        return back()->with('success', 'تم إلغاء الحجز وتسجيل التسوية المالية.');
    }
}
