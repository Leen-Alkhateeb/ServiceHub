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
    // Statuses that keep a time slot occupied
    private const ACTIVE = ['pending', 'approved', 'confirmed', 'completed'];

    /** Customer creates a booking request */
    public function store(Request $request, VendorService $vendorService)
    {
        $customer = $request->user();
        $vendorService->loadMissing(['service.category', 'vendor.vendorProfile']);
        abort_unless($customer->status === 'active' && $vendorService->status === 'active'
            && $vendorService->vendor->status === 'active' && $vendorService->vendor->vendorProfile?->application_status === 'approved'
            && $vendorService->service->approval_status === 'approved' && ! $vendorService->service->is_deleted
            && ! $vendorService->service->category->is_deleted
            && (int) $vendorService->vendor_id !== (int) $customer->id, 403);

        $request->validate(
            ['slot' => ['required', 'regex:/^\d{4}-\d{2}-\d{2}\|\d{2}:\d{2}\|\d+$/']],
            ['slot.required' => 'Please choose a time slot.', 'slot.regex' => 'Invalid time slot.']
        );

        // slot format: date|start|availability_id
        [$date, $start, $availabilityId] = explode('|', $request->slot);
        $startAt = Carbon::parse("$date $start");
        $endAt   = $startAt->copy()->addMinutes(max(5, (int) ($vendorService->duration_minutes ?: 60)));

        $window = RecurringAvailability::where('id', $availabilityId)
            ->where('vendor_id', $vendorService->vendor_id)
            ->where('is_active', true)
            ->first();

        // The slot must really belong to the vendor's weekly schedule
        $valid = $window
            && $startAt->isFuture()
            && $startAt->lte(today()->addDays(29)->endOfDay())
            && $startAt->dayOfWeek === (int) $window->day_of_week
            && $start >= substr($window->start_time, 0, 5)
            && $endAt->isSameDay($startAt)
            && $endAt->format('H:i') <= substr($window->end_time, 0, 5);

        if (! $valid) {
            return back()->withErrors(['slot' => 'This time slot is not available.']);
        }

        $booking = DB::transaction(function () use ($vendorService, $customer, $date, $startAt, $endAt, $window) {
            // Re-check inside a transaction so two customers cannot take the same slot
            $taken = Booking::whereHas('vendorService', fn ($q) => $q->where('vendor_id', $vendorService->vendor_id))
                ->whereDate('booking_date', $date)
                ->where('start_time', '<', $endAt->format('H:i:s'))
                ->where('end_time', '>', $startAt->format('H:i:s'))
                ->whereIn('status', self::ACTIVE)
                ->lockForUpdate()
                ->exists();

            if ($taken) {
                return null;
            }

            // Name, description and price are copied so later vendor edits do not change this booking
            $booking = Booking::create([
                'customer_id'         => $customer->id,
                'vendor_service_id'   => $vendorService->id,
                'availability_id'     => $window->id,
                'booking_date'        => $date,
                'start_time'          => $startAt->format('H:i:s'),
                'end_time'            => $endAt->format('H:i:s'),
                'service_name'        => $vendorService->service->name,
                'service_description' => $vendorService->description ?? $vendorService->service->description,
                'original_price'      => $vendorService->price,
                'status'              => 'pending',
            ]);

            Notification::create([
                'user_id' => $vendorService->vendor_id,
                'title'   => 'New booking request',
                'message' => "{$customer->first_name} {$customer->last_name} requested {$booking->service_name} on {$startAt->format('D d M, H:i')}.",
                'type'    => 'booking',
            ]);

            return $booking;
        });

        if (! $booking) {
            return back()->withErrors(['slot' => 'Sorry, this slot was just taken. Please pick another one.']);
        }

        return redirect()->route('account.profile', ['tab' => 'bookings'])
            ->with('success', 'Booking request sent. We will notify you when the provider responds.');
    }

    /** Customer cancels (only before paying) */
    public function cancel(Request $request, Booking $booking)
    {
        abort_unless(
            $booking->customer_id === $request->user()->id
            && in_array($booking->status, ['pending', 'approved'], true),
            403
        );

        $booking->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()->id,
        ]);

        Notification::create([
            'user_id' => $booking->vendorService->vendor_id,
            'title'   => 'Booking cancelled',
            'message' => "{$request->user()->first_name} cancelled {$booking->service_name}.",
            'type'    => 'booking',
        ]);

        return redirect()->route('account.profile', ['tab' => 'bookings'])->with('success', 'تم إلغاء الحجز.');
    }
}
