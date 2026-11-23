<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Category;
use App\Models\RecurringAvailability;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MarketController extends Controller
{
     
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string'],
            'category' => ['nullable', 'integer'],
            'location' => ['nullable', 'string'],
            'min_price' => ['nullable', 'numeric'],
            'max_price' => ['nullable', 'numeric'],
            'rating' => ['nullable', 'numeric'],
            'sort' => ['nullable', 'string'],
        ]);

        $activeReviews = fn ($reviews) => $reviews->where('is_active', true);

        $query = VendorService::query()->publiclyVisible()
            ->with([
                'service.category',
                'vendor' => fn ($q) => $q
                    ->withAvg(['reviewsReceived' => $activeReviews], 'rating')
                    ->withCount(['reviewsReceived' => $activeReviews]),
            ])
            ->whereHas('service', fn ($q) => $q
                ->where('approval_status', 'approved')
                ->where('is_active', true)
                ->where('is_deleted', false))
            ->whereHas('vendor', fn ($q) => $q->where('status', 'active'));

        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';

            $query->where(function ($w) use ($term) {
                $w->whereHas('service', fn ($s) =>
                    $s->where('name', 'like', $term))
                    ->orWhereHas('vendor', fn ($v) => $v
                        ->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term));
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('service', fn ($s) =>
                $s->where('category_id', $request->category));
        }

        if ($request->filled('location')) {
            $query->whereHas('vendor', fn ($v) =>
                $v->where('location', $request->location));
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        if ($request->filled('rating')) {
            $query->whereHas('vendor', fn ($v) => $v->whereRaw(
                '(select avg(rating) from reviews
                 where reviews.reviewed_user_id = users.id
                 and reviews.is_active = 1) >= ?',
                [(float) $request->rating]
            ));
        }

        match ($request->sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->latest('id'),
        };

        return view('pages.public.services', [
            'services' => $query->paginate(9)->withQueryString(),
            'categories' => Category::where('is_deleted', false)
                ->orderBy('name')
                ->get(),
            'selectedCategory' => $request->filled('category')
                ? Category::where('is_deleted', false)->find($request->integer('category'))
                : null,
            'locations' => User::where('role', 'vendor')
                ->whereNotNull('location')
                ->distinct()
                ->orderBy('location')
                ->pluck('location'),
            'favoriteIds' => auth()->check()
                ? auth()->user()->favorites()->pluck('vendor_service_id')->all()
                : [],
        ]);
    }

     
    public function show(VendorService $vendorService)
    {
        abort_unless(VendorService::publiclyVisible()->whereKey($vendorService)->exists(), 404);

        $vendorService->load(['service.category', 'vendor.vendorProfile']);
        $vendor = $vendorService->vendor;

        abort_unless(
            $vendor->status === 'active'
            && $vendor->vendorProfile?->application_status === 'approved'
            && $vendorService->service->approval_status === 'approved'
            && $vendorService->service->is_active
            && ! $vendorService->service->is_deleted
            && ! $vendorService->service->category->is_deleted,
            404
        );

        $activeReviews = Review::query()
            ->where('reviewed_user_id', $vendor->id)
            ->where('is_active', true);

        $rating = round((float) (clone $activeReviews)->avg('rating'), 1);
        $reviewsCount = (clone $activeReviews)->count();

        $reviews = (clone $activeReviews)
            ->with('reviewer')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('pages.public.service-details', [
            'vendorService' => $vendorService,
            'vendor' => $vendor,
            'rating' => $rating,
            'reviewsCount' => $reviewsCount,
            'reviews' => $reviews,
            'days' => $this->availableDays($vendorService),
            'isFavorite' => auth()->check()
                && auth()->user()->favorites()
                    ->where('vendor_service_id', $vendorService->id)
                    ->exists(),
        ]);
    }

     
    private function availableDays(VendorService $vendorService): array
    {
        $daysToShow = 30;
        $durationMinutes = max(
            5,
            (int) ($vendorService->duration_minutes ?: 60)
        );

        $firstDate = today(Booking::SCHEDULE_TIMEZONE);
        $lastDate = $firstDate->copy()->addDays($daysToShow - 1);

        $windows = RecurringAvailability::where('vendor_id', $vendorService->vendor_id)
            ->where('is_active', true)
            ->get()
            ->groupBy('day_of_week');

        $taken = Booking::whereHas(
                'vendorService',
                fn ($q) => $q->where('vendor_id', $vendorService->vendor_id)
            )
            ->whereBetween('booking_date', [$firstDate, $lastDate])
            ->blockingSlot()
            ->get()
            ->groupBy(fn ($booking) =>
                Carbon::parse($booking->booking_date)->toDateString()
            );

        $days = [];

        for ($i = 0; $i < $daysToShow; $i++) {
            $date = $firstDate->copy()->addDays($i);
            $slots = [];

            foreach ($windows->get($date->dayOfWeek, []) as $window) {
                $cursor = $date->copy()
                    ->setTimeFromTimeString($window->start_time);

                $windowEnd = $date->copy()
                    ->setTimeFromTimeString($window->end_time);

                while (
                    $cursor->copy()
                        ->addMinutes($durationMinutes)
                        ->lte($windowEnd)
                ) {
                    $slotStart = $cursor->copy();
                    $slotEnd = $slotStart->copy()
                        ->addMinutes($durationMinutes);

                    $isTaken = $taken
                        ->get($date->toDateString(), collect())
                        ->contains(function ($booking) use (
                            $date,
                            $slotStart,
                            $slotEnd
                        ) {
                            $bookingStart = Carbon::parse(
                                $date->toDateString() . ' ' . $booking->start_time, Booking::SCHEDULE_TIMEZONE
                            );

                            $bookingEnd = Carbon::parse(
                                $date->toDateString() . ' ' . $booking->end_time, Booking::SCHEDULE_TIMEZONE
                            );

                            return $slotStart->lt($bookingEnd)
                                && $slotEnd->gt($bookingStart);
                        });

                     
                    if ($slotStart->isFuture()) {
                        $slots[] = [
                            'value' => $date->toDateString()
                                . '|' . $slotStart->format('H:i')
                                . '|' . $window->id,
                            'start' => $slotStart->format('H:i'),
                            'end' => $slotEnd->format('H:i'),
                            'taken' => $isTaken,
                        ];
                    }

                    $cursor->addMinutes(Booking::SLOT_STEP_MINUTES);
                }
            }

            $days[] = [
                'date' => $date,
                'slots' => $slots,
            ];
        }

        return $days;
    }
}
