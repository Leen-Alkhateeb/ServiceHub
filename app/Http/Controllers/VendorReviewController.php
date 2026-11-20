<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VendorReviewController extends Controller
{
    public function index(Request $request): View
    {
        $vendorId = $request->user()->id;
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
        ]);
        $bookings = Booking::query()
            ->whereHas('vendorService', fn (Builder $offer) => $offer->where('vendor_id', $vendorId))
            ->with(['customer', 'reviews' => fn ($reviews) => $reviews->where('reviewer_id', $vendorId)->where('is_active', true)])
            ->when($filters['q'] ?? null, fn (Builder $rows, string $search) => $rows->where('service_name', 'like', '%'.$search.'%'))
            ->when($filters['from'] ?? null, fn (Builder $rows, string $from) => $rows->where('booking_date', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $rows, string $to) => $rows->where('booking_date', '<=', $to))
            ->orderByRaw('COALESCE((SELECT MAX(created_at) FROM reviews WHERE booking_id = bookings.id AND reviewer_id = ? AND is_active = ?), created_at) DESC', [$vendorId, true])
            ->latest('id')->paginate(12)->withQueryString();

        return view('pages.vendor.vendor-reviews', compact('bookings', 'filters'));
    }

    public function store(Request $request, Booking $booking): RedirectResponse
    {
        $vendorId = $request->user()->id;
        abort_unless((int) $booking->vendorService->vendor_id === (int) $vendorId, 404);
        abort_unless($booking->status === 'completed' && $booking->completed_at, 403);
        $request->merge(['review_booking_id' => $booking->id]);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($booking, $vendorId, $data) {
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            abort_unless($current->status === 'completed' && $current->completed_at, 403);
            $review = Review::firstOrCreate(
                ['booking_id' => $current->id, 'reviewer_id' => $vendorId],
                $data + ['reviewed_user_id' => $current->customer_id],
            );
            if (! $review->is_active && ! $review->wasRecentlyCreated) {
                $review->update($data + ['comment' => null, 'is_active' => true]);
            }
        });

        return redirect()->route('vendor.reviews')->with('status', 'تم حفظ تقييم العميل.');
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $review = $this->activeReview($request, $booking);
        $request->merge(['review_booking_id' => $booking->id]);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);
        Review::whereKey($review)->where('is_active', true)->update($data + ['comment' => null]);

        return redirect()->route('vendor.reviews')->with('status', 'تم تعديل التقييم.');
    }

    public function destroy(Request $request, Booking $booking): RedirectResponse
    {
        $this->activeReview($request, $booking)->update(['is_active' => false]);

        return redirect()->route('vendor.reviews')->with('status', 'تم حذف التقييم.');
    }

    private function activeReview(Request $request, Booking $booking): Review
    {
        $vendorId = $request->user()->id;
        abort_unless((int) $booking->vendorService->vendor_id === (int) $vendorId, 404);

        return $booking->reviews()->where('reviewer_id', $vendorId)->where('is_active', true)->firstOrFail();
    }
}
