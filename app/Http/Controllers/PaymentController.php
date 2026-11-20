<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /** تسجيل العربون والانتقال إلى الفاتورة */
    public function store(Request $request, Booking $booking)
    {
        abort_unless(
            $booking->customer_id === $request->user()->id
            && $booking->status === 'approved',
            403
        );

        if ($booking->payment_deadline
            && Carbon::parse($booking->payment_deadline)->isPast()) {
            $booking->update(['status' => 'expired']);

            return redirect()
                ->route('customer.bookings')
                ->with('error', 'انتهت مهلة الدفع وأُلغي الحجز.');
        }

        // الدفع الحالي هو عربون بالبطاقة؛ لا تُحفظ بيانات البطاقة.
        $data = $request->validate([
            'payment_method' => ['required', 'in:card'],
            'card_name' => ['nullable', 'string', 'max:100'],
            'card_number' => ['nullable', 'string', 'max:30'],
        ]);

        $result = DB::transaction(function () use ($booking, $data) {
            $lockedBooking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($booking->id);

            abort_unless(
                $lockedBooking->customer_id === auth()->id()
                && $lockedBooking->status === 'approved',
                403
            );

            if ($lockedBooking->payment()->exists()) {
                return 'already_paid';
            }

            if ($lockedBooking->payment_deadline
                && Carbon::parse($lockedBooking->payment_deadline)->isPast()) {
                $lockedBooking->update(['status' => 'expired']);

                return 'expired';
            }

            $deposit = round((float) $lockedBooking->original_price * 0.5, 2);

            Payment::create([
                'booking_id' => $lockedBooking->id,
                'amount' => $deposit,
                'payment_method' => $data['payment_method'],
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $lockedBooking->update(['status' => 'confirmed']);

            Notification::create([
                'user_id' => $lockedBooking->vendorService->vendor_id,
                'title' => 'تم دفع العربون',
                'message' => "تم دفع نصف قيمة {$lockedBooking->service_name}. يُدفع المبلغ المتبقي عند وصولك.",
                'type' => 'payment',
            ]);

            Notification::create([
                'user_id' => $lockedBooking->customer_id,
                'title' => 'تم تأكيد الحجز',
                'message' => "تم تسجيل العربون لحجز {$lockedBooking->service_name}. يُدفع المبلغ المتبقي عند وصول الفني.",
                'type' => 'payment',
            ]);

            return 'paid';
        });

        if ($result === 'expired') {
            return redirect()
                ->route('customer.bookings')
                ->with('error', 'انتهت مهلة الدفع وأُلغي الحجز.');
        }

        return redirect()
            ->route('payment.invoice', $booking)
            ->with('success', 'تم تأكيد الحجز.');
    }

    /** الفاتورة للعميل صاحب الحجز */
    public function invoice(Request $request, Booking $booking)
    {
        abort_unless(
            $booking->customer_id === $request->user()->id
            && $booking->payment()->exists(),
            403
        );

        $booking->load([
            'customer',
            'vendorService.vendor',
            'payment',
        ]);

        return view('pages.public.booking-details', compact('booking'));
    }

    /** صفحة دفع العربون */
    public function checkout(Request $request, Booking $booking)
    {
        abort_unless($booking->customer_id === $request->user()->id, 403);

        if ($booking->payment_deadline
            && Carbon::parse($booking->payment_deadline)->isPast()
            && $booking->status === 'approved'
            && ! $booking->payment()->exists()) {
            $booking->update(['status' => 'expired']);

            return redirect()
                ->route('customer.bookings')
                ->with('error', 'انتهت مهلة الدفع وأُلغي الحجز.');
        }

        abort_unless(
            $booking->status === 'approved'
            && ! $booking->payment()->exists(),
            403
        );

        $booking->load('vendorService.vendor');

        return view('pages.public.checkout', compact('booking'));
    }
}