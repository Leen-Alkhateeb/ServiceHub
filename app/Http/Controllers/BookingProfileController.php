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
    /** إنشاء الحجز وتحويل العميل مباشرةً إلى صفحة دفع العربون. */
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
                    'regex:/^\d{4}-\d{2}-\d{2}\|\d{2}:\d{2}\|\d+$/',
                ],
            ],
            [
                'slot.required' => 'يرجى اختيار موعد.',
                'slot.regex' => 'الموعد المختار غير صالح.',
            ]
        );

        // صيغة الموعد: YYYY-MM-DD|HH:MM|availability_id
        [$date, $start, $availabilityId] = explode('|', $request->input('slot'));

        try {
            $startAt = Carbon::createFromFormat('Y-m-d H:i', "$date $start");
        } catch (\Throwable $exception) {
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

        $valid = $window
            && $startAt->isFuture()
            && $startAt->lte(now()->addDays(29)->endOfDay())
            && $startAt->dayOfWeek === (int) $window->day_of_week
            && $startAt->format('H:i') >= substr($window->start_time, 0, 5)
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

            // يبقى الحجز بانتظار تعبئة الدفع، والموعد محجوز لمدة 30 دقيقة.
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
                'status' => 'awaiting_payment',
                'payment_deadline' => now()->addMinutes(30),
            ]);

            Notification::create([
                'user_id' => $vendorService->vendor_id,
                'title' => 'حجز جديد بانتظار العربون',
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

        return redirect()->route('payment.checkout', $booking);
    }

    /** إلغاء الحجز قبل دفع العربون. */
    public function cancel(Request $request, Booking $booking)
    {
        DB::transaction(function () use ($request, $booking) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            abort_unless(
                $booking->customer_id === $request->user()->id
                && in_array(
                    $booking->status,
                    ['pending', 'awaiting_payment', 'approved'],
                    true
                )
                && ! $booking->payment()->exists(),
                403
            );

            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()->id,
            ]);

            Notification::create([
                'user_id' => $booking->vendorService->vendor_id,
                'title' => 'تم إلغاء الحجز',
                'message' => "ألغى العميل {$request->user()->first_name} حجز خدمة {$booking->service_name}.",
                'type' => 'booking',
            ]);
        });

        return redirect()
            ->route('market.index')
            ->with('success', 'تم إلغاء الحجز.');
    }
}
