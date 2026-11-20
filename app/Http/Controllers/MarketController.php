<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Category;
use App\Models\RecurringAvailability;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorService;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    /** Service Market: search + filters (price, category, location, rating) */
    public function index(Request $request)
    {
        $query = VendorService::query()
            ->where('status', 'active')
            ->with([
                'service.category',
                'vendor' => fn ($q) => $q->withAvg('reviewsReceived', 'rating')->withCount('reviewsReceived'),
            ])
            ->whereHas('service', fn ($q) => $q->where('approval_status', 'approved')->where('is_deleted', false))
            ->whereHas('vendor', fn ($q) => $q->where('status', 'active'));

        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(function ($w) use ($term) {
                $w->whereHas('service', fn ($s) => $s->where('name', 'like', $term))
                  ->orWhereHas('vendor', fn ($v) => $v->where('first_name', 'like', $term)
                                                       ->orWhere('last_name', 'like', $term));
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('service', fn ($s) => $s->where('category_id', $request->category));
        }

        if ($request->filled('location')) {
            $query->whereHas('vendor', fn ($v) => $v->where('location', $request->location));
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        if ($request->filled('rating')) {
            $query->whereHas('vendor', fn ($v) => $v->whereRaw(
                '(select avg(rating) from reviews where reviews.reviewed_user_id = users.id) >= ?',
                [(float) $request->rating]
            ));
        }

        match ($request->sort) {
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default      => $query->latest('id'),
        };

        return view('market.index', [
            'services'    => $query->paginate(9)->withQueryString(),
            'categories'  => Category::where('is_deleted', false)->orderBy('name')->get(),
            'locations'   => User::where('role', 'vendor')->whereNotNull('location')->distinct()->orderBy('location')->pluck('location'),
            'favoriteIds' => auth()->check() ? auth()->user()->favorites()->pluck('vendor_service_id')->all() : [],
        ]);
    }

    /** Service Details + booking popup */
    public function show(VendorService $vendorService)
    {
        abort_unless($vendorService->status === 'active', 404);

        $vendorService->load(['service.category', 'vendor.vendorProfile']);
        $vendor = $vendorService->vendor;

        return view('market.show', [
            'vendorService' => $vendorService,
            'vendor'        => $vendor,
            'rating'        => round((float) $vendor->reviewsReceived()->avg('rating'), 1),
            'reviewsCount'  => $vendor->reviewsReceived()->count(),
            'reviews'       => Review::with('reviewer')->where('reviewed_user_id', $vendor->id)->latest('id')->take(5)->get(),
            'days'          => $this->availableDays($vendorService),
            'isFavorite'    => auth()->check()
                                && auth()->user()->favorites()->where('vendor_service_id', $vendorService->id)->exists(),
        ]);
    }

    /** Next 7 days with hourly slots from the vendor's weekly schedule, minus booked ones */
    private function availableDays(VendorService $vendorService): array
    {
        $windows = RecurringAvailability::where('vendor_id', $vendorService->vendor_id)
            ->where('is_active', true)
            ->get()
            ->groupBy('day_of_week');

        $taken = Booking::whereHas('vendorService', fn ($q) => $q->where('vendor_id', $vendorService->vendor_id))
            ->whereBetween('booking_date', [today(), today()->addDays(7)])
            ->whereIn('status', ['pending', 'approved', 'confirmed', 'completed'])
            ->get()
            ->groupBy(fn ($b) => $b->booking_date->toDateString());

        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $date  = today()->addDays($i);
            $slots = [];

            foreach ($windows->get(strtolower($date->englishDayOfWeek), []) as $window) {
                $cursor = $date->copy()->setTimeFromTimeString($window->start_time);
                $end    = $date->copy()->setTimeFromTimeString($window->end_time);

                while ($cursor->copy()->addHour()->lte($end)) {
                    $start   = $cursor->format('H:i');
                    $isTaken = $taken->get($date->toDateString(), collect())
                        ->contains(fn ($b) => substr($b->start_time, 0, 5) === $start);

                    if (! $isTaken && $cursor->isFuture()) {
                        $slots[] = [
                            'value' => $date->toDateString() . '|' . $start . '|' . $window->id,
                            'start' => $start,
                            'end'   => $cursor->copy()->addHour()->format('H:i'),
                        ];
                    }
                    $cursor->addHour();
                }
            }

            $days[] = ['date' => $date, 'slots' => $slots];
        }

        return $days;
    }
}