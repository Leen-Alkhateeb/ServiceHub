<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $vendorId = $request->user()->id;
        $vendorBookings = Booking::query()
            ->whereHas('vendorService', fn ($query) => $query->where('vendor_id', $vendorId))
            ->with(['customer', 'vendorService', 'payment'])
            ->latest('booking_date')
            ->latest('start_time');

        $pendingBookings = (clone $vendorBookings)->where('status', 'pending')->get();
        $otherBookings = (clone $vendorBookings)->where('status', '!=', 'pending')->get();

        return view('pages.vendor.vendor-bookings', compact('pendingBookings', 'otherBookings'));
    }

    public function update(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
        ]);
        $vendorId = (int) $request->user()->id;

        DB::transaction(function () use ($booking, $data, $vendorId) {
            $current = Booking::query()
                ->whereKey($booking->id)
                ->whereHas('vendorService', fn ($query) => $query->where('vendor_id', $vendorId))
                ->lockForUpdate()
                ->firstOrFail();

            if ($current->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'تمت معالجة طلب الحجز مسبقاً.']);
            }

            $updates = ['status' => $data['status']];
            if ($data['status'] === 'approved') {
                $updates['approved_at'] = now();
                $updates['payment_deadline'] = now()->addMinutes(30);
            }
            $current->update($updates);

            Notification::create([
                'user_id' => $current->customer_id,
                'title' => $data['status'] === 'approved' ? 'Booking approved' : 'Booking rejected',
                'message' => $data['status'] === 'approved'
                    ? "Your booking for {$current->service_name} was approved. Please pay within 30 minutes."
                    : "Your booking request for {$current->service_name} was declined.",
                'type' => 'booking',
            ]);
        });

        return redirect()->route('vendor.vendor-bookings.index')->with('success', 'تم تحديث طلب الحجز.');
    }
}
