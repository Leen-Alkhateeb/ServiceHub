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
                if (! $current->scheduledStart()?->isFuture()) {
                    throw ValidationException::withMessages(['status' => 'موعد الحجز مضى. اطلب من العميل اختيار موعد جديد.']);
                }
                $updates['approved_at'] = now();
                $updates['payment_deadline'] = now()->addMinutes(30);
            }
            $current->update($updates);

            Notification::create([
                'user_id' => $current->customer_id,
                'title' => $data['status'] === 'approved' ? 'تم قبول طلب الحجز' : 'تم رفض طلب الحجز',
                'message' => $data['status'] === 'approved'
                    ? "وافق الفني على طلب حجز {$current->service_name}. يمكنك دفع كامل قيمة الخدمة خلال 30 دقيقة من سجل طلباتك."
                    : "رفض الفني طلب حجز {$current->service_name}.",
                'type' => 'booking',
            ]);
        });

        return redirect()->route('vendor.vendor-bookings.index')->with('success', 'تم تحديث طلب الحجز.');
    }
}
