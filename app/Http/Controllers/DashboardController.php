<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $vendorId = $request->user()->id;
        $bookings = Booking::query()->whereHas(
            'vendorService',
            fn (Builder $service) => $service->where('vendor_id', $vendorId)
        );
        $today = now()->toDateString();
        $time = now()->format('H:i:s');
        $upcoming = fn (Builder $query) => $query
            ->where('status', 'confirmed')
            ->where(function (Builder $dates) use ($today, $time) {
                $dates->whereDate('booking_date', '>', $today)
                    ->orWhere(function (Builder $sameDay) use ($today, $time) {
                        $sameDay->whereDate('booking_date', $today)->where('start_time', '>=', $time);
                    });
            });

        $stats = [
            'pending' => (clone $bookings)->where('status', 'pending')->count(),
            'upcoming' => $upcoming(clone $bookings)->count(),
            'completed' => (clone $bookings)->where('status', 'completed')->count(),
            'rating' => Review::query()
                ->where('reviewed_user_id', $vendorId)
                ->where('is_active', true)
                ->avg('rating'),
        ];

        $pendingBookings = (clone $bookings)
            ->where('status', 'pending')
            ->with('customer')
            ->latest('id')
            ->limit(5)
            ->get();
        $upcomingBookings = $upcoming(clone $bookings)
            ->with('customer')
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        return view('pages.vendor.vendor-dashboard', [
            'stats' => $stats,
            'pendingBookings' => $pendingBookings,
            'upcomingBookings' => $upcomingBookings,
            'vendorName' => $request->user()->first_name,
        ]);
    }
}
