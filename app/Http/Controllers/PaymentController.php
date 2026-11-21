<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * تسجيل كامل قيمة الحجز بعد تعبئة بيانات الدفع.
     */
    public function store(Request $request, Booking $booking)
    {
        abort_unless(
            $booking->customer_id === $request->user()->id
            && in_array($booking->status, ['approved', 'awaiting_payment'], true)
            && ! $booking->payment()->exists(),
            403
        );

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
                && in_array($lockedBooking->status, ['approved', 'awaiting_payment'], true),
                403
            );

            if ($lockedBooking->payment()->exists()) {
                return 'already_paid';
            }

            if (
                $lockedBooking->payment_deadline
                && $lockedBooking->payment_deadline->lte(now())
            ) {
                $lockedBooking->update(['status' => 'expired']);

                return 'expired';
            }

            Payment::create([
                'booking_id' => $lockedBooking->id,
                'amount' => $lockedBooking->original_price,
                'payment_method' => $data['payment_method'],
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $lockedBooking->update(['status' => 'confirmed']);

            Notification::create([
                'user_id' => $lockedBooking->vendorService->vendor_id,
                'title' => 'تم تأكيد الحجز',
                'message' => "تم دفع كامل قيمة حجز {$lockedBooking->service_name}.",
                'type' => 'payment',
            ]);

            Notification::create([
                'user_id' => $lockedBooking->customer_id,
                'title' => 'تم تأكيد الحجز',
                'message' => "تم تسجيل دفع كامل قيمة حجز {$lockedBooking->service_name}.",
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
            ->with('success', 'تم تأكيد الحجز وتسجيل كامل المبلغ.');
    }

    /**
     * صفحة دفع قيمة الحجز قبل تأكيده.
     */
    public function checkout(Request $request, Booking $booking)
    {
        abort_unless($booking->customer_id === $request->user()->id, 403);

        if (
            $booking->payment_deadline
            && $booking->payment_deadline->lte(now())
            && in_array($booking->status, ['approved', 'awaiting_payment'], true)
            && ! $booking->payment()->exists()
        ) {
            return redirect()
                ->route('market.index')
                ->with('error', 'انتهت مهلة الدفع وأصبح الموعد متاحًا للحجز.');
        }

        abort_unless(
            in_array($booking->status, ['approved', 'awaiting_payment'], true)
            && ! $booking->payment()->exists(),
            403
        );

        $booking->load('vendorService.vendor');

        return view('pages.public.checkout', compact('booking'));
    }

    /**
     * فاتورة العميل بعد تسجيل الدفع.
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
