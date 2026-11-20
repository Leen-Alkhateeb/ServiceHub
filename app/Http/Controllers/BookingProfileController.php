<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\RecurringAvailability;
use App\Models\VendorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingProfileController extends Controller
{
    private const ACTIVE = ['pending', 'approved', 'confirmed', 'completed'];

    /** حجز الموعد والانتقال مباشرةً إلى الدفع */
    public function store(Request $request, VendorService $vendorService)
    {
        $customer = $request->user();

        abort_unless(
            $customer->role === 'customer'
            && $vendorService->status === 'active',
            403
        );

        $request->validate(
            [
                'slot' => ['required', 'regex:/^\d{4}-\d{2}-\d{2}\|\d{2}:\d{2}\|\d+$/'],
            ],
            [
                'slot.required' => 'يرجى اختيار موعد.',
                'slot.regex' => 'الموعد المختار غير صالح.',
            ]
        );

        [$date, $start, $availabilityId] = explode('|', $request->input('slot'));

        $startAt = Carbon::parse("$date $start");
        $durationMinutes = max(
            5,
            (int) ($vendorService->duration_minutes ?: 60)
        );
        $endAt = $startAt->copy()->addMinutes($durationMinutes);

        $window = RecurringAvailability::query()
            ->whereKey($availabilityId)
            ->where('vendor_id', $vendorService->vendor_id)
            ->where('is_active', true)
            ->first();

        $valid = $window
            && $startAt->isFuture()
            && $startAt->lte(now()->addDays(30)->endOfDay())
            && $startAt->dayOfWeek === (int) $window->day_of_week
            && $startAt->format('H:i') >= substr($window->start_time, 0, 5)
            && $endAt->format('H:i') <= substr($window->end_time, 0, 5);

        if (! $valid) {
            return back()->withErrors([
                'slot' => 'هذا الموعد غير متاح. اختر موعدًا آخر.',
            ])->withInput();
        }

        $booking = DB::transaction(function () use (
            $vendorService,
            $customer,
            $date,
            $startAt,
            $endAt,
            $window
        ) {
            $startTime = $startAt->format('H:i:s');
            $endTime = $endAt->format('H:i:s');

            // إذا أعاد العميل إرسال حجزه نفسه، نعيده إلى الدفع بدل إنشاء حجز مكرر.
            $existingBooking = Booking::query()
                ->where('customer_id', $customer->id)
                ->where('vendor_service_id', $vendorService->id)
                ->whereDate('booking_date', $date)
                ->where('start_time', $startTime)
                ->whereIn('status', ['pending', 'approved'])
                ->whereDoesntHave('payment')
                ->lockForUpdate()
                ->first();

            if ($existingBooking) {
                if ($existingBooking->status === 'pending') {
                    $existingBooking->update([
                        'status' => 'approved',
                        'approved_at' => now(),
                        'payment_deadline' => now()->addMinutes(30),
                    ]);
                } elseif (! $existingBooking->payment_deadline) {
                    $existingBooking->update([
                        'payment_deadline' => now()->addMinutes(30),
                    ]);
                }

                return $existingBooking;
            }

            // يمنع حجز وقت يتداخل مع حجز نشط آخر للفني.
            $taken = Booking::query()
                ->whereHas(
                    'vendorService',
                    fn ($query) => $query->where(
                        'vendor_id',
                        $vendorService->vendor_id
                    )
                )
                ->whereDate('booking_date', $date)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->whereIn('status', self::ACTIVE)
                ->lockForUpdate()
                ->exists();

            if ($taken) {
                return null;
            }

            $vendorService->loadMissing('service');

            $booking = Booking::create([
                'customer_id' => $customer->id,
                'vendor_service_id' => $vendorService->id,
                'availability_id' => $window->id,
                'booking_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'service_name' => $vendorService->service->name,
                'service_description' =>
                    $vendorService->description
                    ?? $vendorService->service->description,
                'original_price' => $vendorService->price,
                'status' => 'approved',
                'approved_at' => now(),
                'payment_deadline' => now()->addMinutes(30),
            ]);

            Notification::create([
                'user_id' => $vendorService->vendor_id,
                'title' => 'تم حجز موعد جديد',
                'message' => "{$customer->first_name} {$customer->last_name} حجز {$booking->service_name} بتاريخ {$startAt->format('Y-m-d H:i')}، وبانتظار إتمام الدفع.",
                'type' => 'booking',
            ]);

            return $booking;
        });

        if (! $booking) {
            return back()->withErrors([
                'slot' => 'هذا الموعد محجوز بالفعل. اختر موعدًا آخر.',
            ])->withInput();
        }

        return redirect()
            ->route('payment.checkout', $booking)
            ->with('success', 'تم حجز الموعد. أكمل الدفع خلال 30 دقيقة.');
    }

    /** إلغاء الحجز قبل الدفع */
    public function cancel(Request $request, Booking $booking)
    {
        abort_unless(
            $booking->customer_id === $request->user()->id
            && in_array($booking->status, ['pending', 'approved'], true),
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
            'message' => "{$request->user()->first_name} ألغى حجز {$booking->service_name}.",
            'type' => 'booking',
        ]);

        return redirect()
            ->route('customer.bookings')
            ->with('success', 'تم إلغاء الحجز.');
    }
}