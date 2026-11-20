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
    /** Demo payment: nothing is charged and card data is NOT saved */
    public function store(Request $request, Booking $booking)
    {
        abort_unless($booking->customer_id === $request->user()->id && $booking->status === 'approved', 403);

        // Deadline passed → booking expires
        if ($booking->payment_deadline && Carbon::parse($booking->payment_deadline)->isPast()) {
            $booking->update(['status' => 'expired']);

            return redirect()->route('customer.bookings')
                ->with('error', 'The payment deadline has passed and the booking expired.');
        }

        $request->merge(['card_number' => preg_replace('/\s+/', '', (string) $request->card_number)]);

        $data = $request->validate([
            'payment_method' => ['required', 'in:card,cash'],
            'card_name'      => ['required_if:payment_method,card', 'nullable', 'string', 'max:100'],
            'card_number'    => ['required_if:payment_method,card', 'nullable', 'digits:16'],
        ]);

        DB::transaction(function () use ($booking, $data) {
            Payment::create([
                'booking_id'     => $booking->id,
                'amount'         => $booking->original_price,
                'payment_method' => $data['payment_method'],
                'status'         => 'paid',
                'paid_at'        => now(),
            ]);

            $booking->update(['status' => 'confirmed']);

            Notification::create([
                'user_id' => $booking->vendorService->vendor_id,
                'title'   => 'Payment received',
                'message' => "{$booking->service_name} was paid and is now confirmed.",
                'type'    => 'payment',
            ]);

            Notification::create([
                'user_id' => $booking->customer_id,
                'title'   => 'Payment successful',
                'message' => "Your booking {$booking->invoice_number} is confirmed.",
                'type'    => 'payment',
            ]);
        });

        return redirect()->route('payment.invoice', $booking)->with('success', 'Payment successful.');
    }

    /** Printable invoice (customer who owns the booking only) */
    public function invoice(Request $request, Booking $booking)
    {
        abort_unless($booking->customer_id === $request->user()->id && $booking->payment, 403);

        $booking->load(['customer', 'vendorService.vendor', 'payment']);

      return view('pages.public.booking-details', compact('booking'));
    }
    /** صفحة الدفع */
public function checkout(Request $request, Booking $booking)
{
    abort_unless($booking->customer_id === $request->user()->id && $booking->status === 'approved', 403);

    $booking->load('vendorService.vendor');

    return view('pages.public.checkout', compact('booking'));
}
}
