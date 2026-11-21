<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\PortfolioProject;
use App\Models\Review;
use App\Models\VendorService;
use App\Services\AccountHistory;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorPagesController extends Controller
{
    public function profile(Request $request, AccountHistory $history): View|RedirectResponse
    {
        $vendorId = $request->integer('vendor');
        if ($request->has('vendor')) {
            abort_unless($vendorId > 0, 404);
        } elseif ($request->has('id')) {
            $vendorId = $this->visibleOffers()->where('offers.id', $request->integer('id'))->value('offers.vendor_id');
            abort_unless($vendorId, 404);
        } else {
            if (!$request->user()) {
                return redirect()->guest(route('login'));
            }
            abort_unless($request->user()->role === 'vendor' && $request->user()->status === 'active', 403);
            $vendorId = $request->user()->id;
        }

        $user = $request->user();
        $ownProfile = $user && (int) $user->id === (int) $vendorId && $user->status === 'active';
        $profile = $this->visibleVendors($ownProfile)->where('users.id', $vendorId)->first([
            'users.id',
            'users.first_name',
            'users.last_name',
            'users.location',
            'users.profile_image',
            'profiles.work_type',
            'profiles.experience_years',
            'profiles.bio',
            'profiles.application_status',
        ]);
        abort_unless($profile, 404);
        $tab = $request->query('tab', 'services');
        $tabs = $ownProfile
            ? ['services' => 'خدماتي', 'portfolio' => 'أعمالي', 'bookings' => 'طلباتي', 'payments' => 'مدفوعاتي', 'income' => 'مستحقاتي']
            : ['services' => 'الخدمات', 'portfolio' => 'معرض الأعمال'];
        abort_unless(is_string($tab) && isset($tabs[$tab]), 404);
        $historyData = $ownProfile ? $history->forTab($user, $tab, $request) : ['entries' => null];

        $rating = Review::where('reviewed_user_id', $vendorId)->where('is_active', true)->avg('rating');
        $projects = $tab === 'portfolio' ? PortfolioProject::with('coverImage')->where('vendor_id', $vendorId)->where('is_active', true)->latest('id')
            ->paginate(8, ['*'], 'projects_page')->withQueryString() : null;
        $services = $tab === 'services' ? $this->visibleOffers()->where('offers.vendor_id', $vendorId)->latest('offers.id')
            ->paginate(8, [
                'offers.id',
                'offers.status',
                'services.name',
                'offers.price',
                'offers.description',
                'offers.duration_minutes',
                'offers.image',
                'services.image as catalog_image',
                'services.description as default_description'
            ], 'services_page')
            ->withQueryString()->through(function ($service) {
                $service->image_url = $this->resolveImage($service->id, $service->image, $service->catalog_image)[0] ?? null;

                return $service;
            }) : null;

        $catalog = $ownProfile ? DB::table('services')
            ->join('categories', 'categories.id', '=', 'services.category_id')
            ->where('services.approval_status', 'approved')->where('services.is_deleted', false)
            ->where('categories.is_deleted', false)
            ->whereNotExists(fn(Builder $offers) => $offers->selectRaw('1')->from('vendor_services')
                ->whereColumn('vendor_services.service_id', 'services.id')
                ->where('vendor_services.vendor_id', $vendorId)->where('vendor_services.status', 'active'))
            ->orderBy('categories.name')->orderBy('services.name')
            ->get(['services.id', 'services.name', 'categories.name as category_name']) : collect();
        $requests = $ownProfile ? VendorService::with('service')->where('vendor_id', $vendorId)
            ->whereIn('status', ['pending', 'rejected'])->latest('updated_at')->latest('id')
            ->paginate(5, ['*'], 'requests_page')->withQueryString() : null;

        $categories = $ownProfile ? DB::table('categories')->where('is_deleted', false)->orderBy('name')->get(['id', 'name']) : collect();

        return view('pages.vendor.vendor-profile', compact('profile', 'ownProfile', 'rating', 'projects', 'services', 'catalog', 'requests', 'categories', 'tab', 'tabs') + $historyData);
    }

    public function dashboard(Request $request): View
    {
        $vendor = $request->user();
        $bookings = Booking::whereHas('vendorService', fn($offer) => $offer->where('vendor_id', $vendor->id));
        $pending = (clone $bookings)->where('status', 'pending');
        $upcoming = (clone $bookings)->whereIn('status', ['approved', 'paid', 'confirmed', 'in_progress'])
            ->whereDate('booking_date', '>=', today());
        $stats = [
            'pending' => (clone $pending)->count(),
            'upcoming' => (clone $upcoming)->count(),
            'completed' => (clone $bookings)->where('status', 'completed')->count(),
            'rating' => $vendor->reviewsReceived()->where('is_active', true)->avg('rating'),
        ];
        $vendorName = $vendor->first_name.' '.$vendor->last_name;
        $pendingBookings = $pending->with('customer')->latest('id')->limit(5)->get();
        $upcomingBookings = $upcoming->with('customer')->oldest('booking_date')->oldest('start_time')->limit(5)->get();

        return view('pages.vendor.vendor-dashboard', compact('stats', 'vendor', 'vendorName', 'pendingBookings', 'upcomingBookings'));
    }

    public function serviceReviews(Request $request, VendorService $offer): JsonResponse
    {
        $this->authorizeOffer($request, $offer);

        $reviews = Review::query()
            ->join('bookings', 'bookings.id', '=', 'reviews.booking_id')
            ->join('users as customer', 'customer.id', '=', 'reviews.reviewer_id')
            ->where('bookings.vendor_service_id', $offer->id)
            ->where('reviews.reviewed_user_id', $offer->vendor_id)
            ->where('reviews.is_active', true)
            ->whereColumn('reviews.reviewer_id', 'bookings.customer_id')
            ->where('bookings.status', 'completed')
            ->orderByDesc('reviews.id')
            ->cursorPaginate(8, ['reviews.id', 'reviews.rating', 'reviews.comment', 'reviews.created_at', 'customer.first_name']);

        return response()->json($reviews);
    }

    public function serviceImage(Request $request, VendorService $offer): StreamedResponse
    {
        $this->authorizeOffer($request, $offer);
        $path = $this->resolveImage($offer->id, $offer->image, $offer->service->image)[1] ?? null;
        abort_unless($path, 404);

        $disk = Storage::disk('public');
        $type = $disk->mimeType($path);
        abort_unless(in_array($type, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'], true), 404);

        return $disk->response($path, null, ['Content-Type' => $type, 'X-Content-Type-Options' => 'nosniff']);
    }

    private function resolveImage(int $offerId, ?string ...$images): array
    {
        foreach (array_unique($images) as $image) {
            if (!$image) {
                continue;
            }
            if (filter_var($image, FILTER_VALIDATE_URL) && in_array(parse_url($image, PHP_URL_SCHEME), ['https', 'http'], true)) {
                return [$image, null];
            }
            $path = ltrim(str_replace('\\', '/', $image), '/');
            if (str_contains($path, '..') || !preg_match('/\.(jpe?g|png|webp|gif|avif)$/i', $path)) {
                continue;
            }
            if (str_starts_with($path, 'assets/') && is_file(public_path($path))) {
                return [asset($path), null];
            }
            $path = str_starts_with($path, 'storage/') ? substr($path, 8) : $path;
            if (Storage::disk('public')->exists($path)) {
                return [route('vendor.service.image', $offerId), $path];
            }
        }

        return [];
    }

    private function authorizeOffer(Request $request, VendorService $offer): void
    {
        $user = $request->user();
        $ownProfile = $user && (int) $user->id === (int) $offer->vendor_id && $user->status === 'active';
        abort_unless($this->visibleVendors($ownProfile)->where('users.id', $offer->vendor_id)->exists(), 404);
        abort_unless($this->visibleOffers()->where('offers.id', $offer->id)->exists(), 404);
    }

    private function visibleVendors(bool $ownProfile): Builder
    {
        return DB::table('vendor_profiles as profiles')
            ->join('users', 'users.id', '=', 'profiles.user_id')
            ->where('users.role', 'vendor')->where('users.status', 'active')
            ->when(!$ownProfile, fn(Builder $query) => $query->where('profiles.application_status', 'approved'));
    }

    private function visibleOffers(): Builder
    {
        return DB::table('vendor_services as offers')
            ->join('services', 'services.id', '=', 'offers.service_id')
            ->join('categories', 'categories.id', '=', 'services.category_id')
            ->where('offers.status', 'active')
            ->where('services.approval_status', 'approved')
            ->where('services.is_deleted', false)
            ->where('categories.is_deleted', false);
    }
}
