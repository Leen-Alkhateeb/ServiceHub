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
    /**
     * تسجيل عربون 50٪ بعد تعبئة بيانات الدفع.
     */
    public function store(Request $request, Booking $booking)
    {
        abort_unless(
            $booking->customer_id === $request->user()->id
            && $booking->status === 'awaiting_payment'
            && ! $booking->payment()->exists(),
            403
        );

        if (
            $booking->payment_deadline
            && Carbon::parse($booking->payment_deadline)->isPast()
        ) {
            $booking->update(['status' => 'expired']);

            return redirect()
                ->route('market.index')
                ->with('error', 'انتهت مهلة الدفع وأصبح الموعد متاحًا للحجز.');
        }

        // إزالة الفراغات قبل التحقق من رقم البطاقة.
        $request->merge([
            'card_number' => preg_replace(
                '/\s+/',
                '',
                (string) $request->input('card_number')
            ),
        ]);

        $data = $request->validate([
            'payment_method' => ['required', 'in:card'],
            'card_name' => ['required', 'string', 'max:100'],
            'card_number' => ['required', 'digits:16'],
        ]);

        $result = DB::transaction(function () use ($booking, $data) {
            $lockedBooking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($booking->id);

            abort_unless(
                $lockedBooking->customer_id === auth()->id()
                && $lockedBooking->status === 'awaiting_payment',
                403
            );

            if ($lockedBooking->payment()->exists()) {
                return 'already_paid';
            }

            if (
                $lockedBooking->payment_deadline
                && Carbon::parse($lockedBooking->payment_deadline)->isPast()
            ) {
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
                'title' => 'تم تأكيد الحجز',
                'message' => "تم تسجيل عربون 50% لحجز {$lockedBooking->service_name}. المبلغ المتبقي يُدفع عند الاستلام.",
                'type' => 'payment',
            ]);

            Notification::create([
                'user_id' => $lockedBooking->customer_id,
                'title' => 'تم تأكيد الحجز',
                'message' => "تم تسجيل العربون لحجز {$lockedBooking->service_name}. المبلغ المتبقي يُدفع عند وصول الفني.",
                'type' => 'payment',
            ]);

            return 'paid';
        });

        if ($result === 'expired') {
            return redirect()
                ->route('market.index')
                ->with('error', 'انتهت مهلة الدفع وأصبح الموعد متاحًا للحجز.');
        }

        return redirect()
            ->route('payment.invoice', $booking)
            ->with('success', 'تم تأكيد الحجز وتسجيل العربون.');
    }

    /**
     * صفحة دفع العربون قبل تأكيد الحجز.
     */
    public function checkout(Request $request, Booking $booking)
    {
        abort_unless($booking->customer_id === $request->user()->id, 403);

        if (
            $booking->payment_deadline
            && Carbon::parse($booking->payment_deadline)->isPast()
            && $booking->status === 'awaiting_payment'
            && ! $booking->payment()->exists()
        ) {
            $booking->update(['status' => 'expired']);

            return redirect()
                ->route('market.index')
                ->with('error', 'انتهت مهلة الدفع وأصبح الموعد متاحًا للحجز.');
        }

        abort_unless(
            $booking->status === 'awaiting_payment'
            && ! $booking->payment()->exists(),
            403
        );

        $booking->load('vendorService.vendor');

        return view('pages.public.checkout', compact('booking'));
    }

    /**
     * فاتورة العميل بعد تسجيل العربون.
     */
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
}