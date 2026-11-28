<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingEvidence;
use App\Models\InstantOffer;
use App\Models\InstantRequest;
use App\Models\User;
use App\Models\VendorService;
use App\Services\InstantMarketplace;
use App\Services\InstantImages;
use App\Services\InstantServiceSuggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\ConnectionException;

class InstantMarketplaceController extends Controller
{
    private const LOCATION = ['latitude' => ['required', 'numeric', 'between:-90,90'], 'longitude' => ['required', 'numeric', 'between:-180,180']];
    private const LOCATION_ERRORS = [
        'latitude.required' => 'حدد موقعك الحالي واسمح للمتصفح بمشاركته.',
        'longitude.required' => 'حدد موقعك الحالي واسمح للمتصفح بمشاركته.',
    ];

    public function __construct(private InstantMarketplace $market) {}

    public function index(Request $request)
    {
        $user = $this->actor($request);
        $requests = InstantRequest::where('customer_id', $user->id)->latest('id')->paginate(8);
        $bookings = Booking::where('booking_type', 'instant')->where('customer_id', $user->id)->latest('id')->paginate(8, ['*'], 'bookings_page');
        $services = $this->market->catalog()->with('category')->orderBy('name')->get();
        $customerDebts = DB::table('wallet_transactions as debt')->join('bookings as b', 'b.id', '=', 'debt.booking_id')
            ->leftJoin('refund_requests as cases', 'cases.id', '=', 'debt.refund_request_id')
            ->where('debt.user_id', $user->id)->where('debt.account_type', 'customer')->where('debt.transaction_type', 'debt')
            ->where('debt.status', 'outstanding')->whereNull('b.deleted_at')->orderByDesc('debt.id')
            ->paginate(8, ['debt.id', 'debt.booking_id', 'debt.amount', 'debt.idempotency_key', 'debt.description', 'b.service_name', 'cases.reason'], 'debts_page');

        return response()->view('pages.instant.index', compact('requests', 'bookings', 'services', 'customerDebts'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function start(Request $request)
    {
        $user = $this->actor($request);
        $data = $request->validate(['service_id' => ['required', 'integer'], 'description' => ['required', 'string', 'max:2000'], 'address' => ['required', 'string', 'max:255'], ...self::LOCATION, ...InstantImages::rules('images')], self::LOCATION_ERRORS);
        $images = $data['images'] ?? [];
        unset($data['images']);

        return redirect()->route('instant.search', $this->market->start($user, $data, $images));
    }

    public function image(Request $request, InstantRequest $instantRequest, BookingEvidence $image)
    {
        $user = $this->actor($request);
        abort_unless($image->kind === 'problem' && (int) $image->instant_request_id === $instantRequest->id && $image->is_active
            && str_starts_with($image->path, 'instant-evidence/'), 404);
        if ($user->id !== (int) $instantRequest->customer_id) {
            $offers = VendorService::where('vendor_id', $user->id)->where('service_id', $instantRequest->service_id)->with('vendor.vendorProfile')->get();
            abort_unless($offers->contains(fn ($offer) => $this->market->eligible($instantRequest, $offer)), 404);
        }

        return Storage::disk('local')->download($image->path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function suggestion(Request $request, InstantServiceSuggestion $suggestion)
    {
        $this->actor($request);
        $data = $request->validate(['problem' => ['required', 'string', 'max:2000']]);
        return response()->json($suggestion->suggest($data['problem']));
    }

    public function search(Request $request, InstantRequest $instantRequest)
    {
        abort_unless((int) $instantRequest->customer_id === $this->actor($request)->id, 404);
        $offers = $instantRequest->offers()->with(['vendorService.vendor' => fn ($user) => $user->withAvg('reviewsReceived', 'rating'), 'vendorService.vendor.vendorProfile'])
            ->where('status', 'pending')->latest('id')->get()
            ->filter(fn ($offer) => $this->market->eligible($instantRequest, $offer->vendorService, $offer->arrival_minutes, $offer->duration_minutes));
        $booking = Booking::where('instant_offer_id', $instantRequest->offers()->where('status', 'accepted')->value('id'))
            ->where('booking_type', 'instant')->first();

        return response()->view('pages.instant.search', compact('instantRequest', 'offers', 'booking'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function cancelSearch(Request $request, InstantRequest $instantRequest)
    {
        abort_unless((int) $instantRequest->customer_id === $this->actor($request)->id, 404);
        DB::transaction(function () use ($instantRequest) {
            $current = InstantRequest::query()->lockForUpdate()->findOrFail($instantRequest->id);
            abort_unless($current->status === 'searching', 409);
            $vendors = $current->offers()->where('status', 'pending')->with('vendorService')->get()->pluck('vendorService.vendor_id')->filter()->unique();
            $current->update(['status' => 'cancelled']);
            $current->offers()->where('status', 'pending')->update(['status' => 'invalidated']);
            foreach ($vendors as $vendor) {
                $this->market->notify($vendor, 'أوقف العميل البحث عن فني للطلب رقم '.$current->id.'.');
            }
            $this->market->notify($current->customer_id, 'تم إيقاف البحث عن فني للطلب رقم '.$current->id.'.');
        }, 3);

        return redirect()->route('instant.search', $instantRequest);
    }

    public function accept(Request $request, InstantRequest $instantRequest, InstantOffer $offer)
    {
        return redirect()->route('instant.booking', $this->market->accept($this->actor($request), $instantRequest, $offer));
    }

    public function vendor(Request $request)
    {
        $vendor = $this->vendorActor($request);
        $profile = $vendor->vendorProfile;
        $services = VendorService::publiclyVisible()->where('vendor_id', $vendor->id)
            ->whereIn('service_id', $this->market->catalog()->select('id'))
            ->with(['service', 'vendor.vendorProfile'])->get();
        $enabled = $services->where('instant_enabled', true)->keyBy('service_id');
        $matches = $this->nearbyRequests($enabled, 20);
        $locations = $matches->mapWithKeys(function (InstantRequest $candidate) {
            $latitude = round((float) $candidate->latitude, 2);
            $longitude = round((float) $candidate->longitude, 2);

            return [$candidate->id => [
                'map' => 'https://www.openstreetmap.org/export/embed.html?'.http_build_query([
                    'bbox' => implode(',', [max(-180, $longitude - 0.018), max(-90, $latitude - 0.009), min(180, $longitude + 0.018), min(90, $latitude + 0.009)]),
                    'layer' => 'mapnik',
                    'marker' => "$latitude,$longitude",
                ]),
                'point' => "$longitude,$latitude",
            ]];
        });
        $estimates = $this->roadEstimates($locations, (float) $profile->latitude, (float) $profile->longitude);
        $offers = InstantOffer::whereIn('vendor_service_id', $services->pluck('id'))->where('status', 'pending')
            ->whereHas('instantRequest', fn ($rows) => $rows->where('status', 'searching'))->latest('id')->paginate(10);
        $bookings = Booking::where('booking_type', 'instant')->whereHas('vendorService', fn ($rows) => $rows->where('vendor_id', $vendor->id))->latest('id')->paginate(8, ['*'], 'bookings_page');

        $images = BookingEvidence::whereIn('instant_request_id', $matches->pluck('id'))->where('kind', 'problem')->where('is_active', true)->get()->groupBy('instant_request_id');

        return response()->view('pages.instant.vendor', compact('profile', 'services', 'enabled', 'matches', 'offers', 'bookings', 'images', 'locations', 'estimates'))
            ->header('Cache-Control', 'private, no-store');
    }

    private function roadEstimates(Collection $locations, float $latitude, float $longitude): array
    {
        if ($locations->isEmpty() || ! config('instant.routing_url')) {
            return [];
        }

        $origin = round($longitude, 3).','.round($latitude, 3);
        $destinations = $locations->pluck('point')->unique()->values();
        $points = $origin.';'.$destinations->implode(';');
        $cacheKey = 'instant-road:'.hash('sha256', config('instant.routing_url').'|'.$points);
        $estimates = Cache::get($cacheKey);
        if ($estimates !== null) {
            return $estimates;
        }
        if (! Cache::add('instant-road:request-slot', true, 1)) {
            return [];
        }

        $estimates = [];
        try {
            $client = Http::withUserAgent('ServiceHub/1.0 (instant road estimates)')
                ->connectTimeout(2)->timeout(3);
            if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA')) {
                $client->withOptions(['curl' => [CURLOPT_SSL_OPTIONS => CURLSSLOPT_NATIVE_CA]]);
            }

            $table = $client->get(rtrim(config('instant.routing_url'), '/').'/table/v1/driving/'.$points, [
                    'sources' => '0',
                    'destinations' => implode(';', range(1, $destinations->count())),
                    'annotations' => 'distance,duration',
                ]);
            if ($table->successful() && $table->json('code') === 'Ok') {
                foreach ($destinations as $index => $point) {
                    $meters = $table->json("distances.0.$index");
                    $seconds = $table->json("durations.0.$index");
                    if (is_numeric($meters) && is_numeric($seconds)) {
                        $estimates[$point] = [
                            'km' => round($meters / 1000, 1),
                            'minutes' => max(1, (int) ceil($seconds / 60)),
                        ];
                    }
                }
            }
        } catch (ConnectionException) {
            Cache::put($cacheKey, [], 30);

            return [];
        }

        Cache::put($cacheKey, $estimates, $estimates ? 300 : 30);

        return $estimates;
    }

    public function vendorInboxCount(Request $request)
    {
        $vendor = $this->vendorActor($request);
        $count = 0;
        if ($vendor->vendorProfile->instant_available) {
            $enabled = VendorService::publiclyVisible()->where('vendor_id', $vendor->id)->where('instant_enabled', true)
                ->whereIn('service_id', $this->market->catalog()->select('id'))
                ->with(['service', 'vendor.vendorProfile'])->get()->keyBy('service_id');
            $count = $this->nearbyRequests($enabled, 100, $vendor->id)->count();
        }

        return response()->json(['count' => $count])->header('Cache-Control', 'private, no-store');
    }

    private function nearbyRequests(Collection $enabled, int $limit, ?int $excludeOfferedBy = null): Collection
    {
        return InstantRequest::where('status', 'searching')->whereIn('service_id', $enabled->keys())
            ->when($excludeOfferedBy, fn ($requests, $vendor) => $requests->whereDoesntHave('offers', fn ($offers) =>
                $offers->where('status', 'pending')->whereHas('vendorService', fn ($services) => $services->where('vendor_id', $vendor))))
            ->latest('id')->cursor()->filter(fn ($candidate) => $this->market->eligible($candidate, $enabled[$candidate->service_id]))
            ->take($limit)->collect();
    }

    public function availability(Request $request)
    {
        $vendor = $this->vendorActor($request);
        $request->validate(['instant_available' => ['required', 'boolean']]);
        $available = $request->boolean('instant_available');
        $location = $available ? $request->validate(self::LOCATION, self::LOCATION_ERRORS) : [];
        DB::transaction(function () use ($request, $vendor, $available, $location) {
            User::query()->lockForUpdate()->findOrFail($vendor->id);
            $profile = $vendor->vendorProfile;
            abort_unless($profile, 403);
            abort_if($request->boolean('refresh_location') && (! $profile->instant_available || ! $available), 409);
            $profile->forceFill(['instant_available' => $available] + ($available ? $location + ['location_updated_at' => now(), 'last_seen_at' => now()] : [
                'latitude' => null, 'longitude' => null, 'location_updated_at' => null, 'last_seen_at' => null,
            ]))->save();
            if (! $available) {
                InstantOffer::where('status', 'pending')->whereHas('vendorService', fn ($rows) => $rows->where('vendor_id', $vendor->id))->update(['status' => 'invalidated']);
            }
        }, 3);

        return $request->expectsJson() ? response()->json(['available' => $available]) : back()->with('success', 'تم تحديث حالة التوفّر.');
    }

    public function vendorService(Request $request, VendorService $offer)
    {
        $vendor = $this->vendorActor($request);
        abort_unless((int) $offer->vendor_id === $vendor->id, 404);
        $request->validate(['instant_enabled' => ['required', 'boolean']]);
        DB::transaction(function () use ($request, $vendor, $offer) {
            User::query()->lockForUpdate()->findOrFail($vendor->id);
            $current = VendorService::query()->lockForUpdate()->findOrFail($offer->id);
            $enabled = $request->boolean('instant_enabled');
            abort_if($enabled && ($current->status !== 'active' || ! $this->market->catalog()->whereKey($current->service_id)->exists()), 422);
            $current->forceFill(['instant_enabled' => $enabled])->save();
            if (! $enabled) {
                InstantOffer::where('vendor_service_id', $current->id)->where('status', 'pending')->update(['status' => 'invalidated']);
            }
        }, 3);

        return back()->with('success', 'تم تحديث الخدمة.');
    }

    public function offer(Request $request, InstantRequest $instantRequest)
    {
        $vendor = $this->vendorActor($request);
        $data = $request->validate([
            'vendor_service_id' => ['required', 'integer'], 'offered_price' => ['required', 'numeric', 'decimal:0,3', 'between:0.001,9999999.999'],
            'arrival_minutes' => ['required', 'integer', 'between:1,1439'], 'duration_minutes' => ['required', 'integer', 'between:5,1440'],
        ]);
        $this->market->offer($vendor, $instantRequest, $data);

        return back()->with('success', 'تم إرسال العرض.');
    }

    public function withdraw(Request $request, InstantOffer $offer)
    {
        $vendor = $this->vendorActor($request);
        abort_unless((int) $offer->vendorService?->vendor_id === $vendor->id, 404);
        DB::transaction(function () use ($offer) {
            $parent = InstantRequest::query()->lockForUpdate()->findOrFail($offer->instant_request_id);
            $current = $parent->offers()->lockForUpdate()->findOrFail($offer->id);
            abort_unless($parent->status === 'searching' && $current->status === 'pending', 409);
            $current->update(['status' => 'withdrawn']);
            $this->market->notify($parent->customer_id, 'سحب فني عرضه على طلب الخدمة الفورية');
            $this->market->notify($current->vendorService->vendor_id, 'تم سحب عرضك على الطلب رقم '.$parent->id.'.');
        }, 3);

        return back()->with('success', 'تم سحب العرض.');
    }

    public function admin(\App\Services\InstantFinance $finance)
    {
        abort_unless(config('instant.enabled'), 404);
        $requests = InstantRequest::latest('id')->paginate(10, ['*'], 'requests_page');
        $bookings = Booking::where('booking_type', 'instant')->latest('id')->paginate(10, ['*'], 'bookings_page');

        $pending = Booking::where('booking_type', 'instant')->whereIn('settlement_status', ['awaiting_admin', 'insufficient_funds'])
            ->where('objection_deadline', '<=', now())->whereDoesntHave('pendingCases')
            ->latest('id')->paginate(10, ['*'], 'payouts_page');
        $simulatedCommission = DB::table('wallet_transactions')->where('account_type', 'platform')->whereNull('booking_id')
            ->where('idempotency_key', 'like', 'now-cash:%')
            ->where('transaction_type', 'commission')->where('status', 'posted')->sum('amount');
        $debts = DB::table('wallet_transactions')->where('transaction_type', 'debt')->where('status', 'outstanding')->sum('amount');
        $cases = \App\Models\RefundRequest::query()->whereHas('booking', fn ($rows) => $rows->where('booking_type', 'instant')->whereNull('deleted_at'))
            ->where(fn ($rows) => $rows->where('status', 'pending')->orWhere('compensation_status', 'approved'))
            ->with('booking')->latest('id')->paginate(10, ['*'], 'cases_page');

        $availableFils = $finance->availableFils();
        $commissions = \App\Models\WalletTransaction::query()->whereNull('booking_id')->where('account_type', 'platform')
            ->where('transaction_type', 'commission')->where('idempotency_key', 'like', 'now-cash:%')
            ->with('corrections')->latest('id')->paginate(8, ['*'], 'commissions_page');

        return response()->view('pages.instant.admin', compact('requests', 'bookings', 'pending', 'simulatedCommission', 'debts', 'availableFils', 'cases', 'commissions'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function correctCommission(Request $request, int $entry, \App\Services\InstantFinance $finance)
    {
        abort_unless(config('instant.enabled'), 404);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,3', 'between:0.001,9999999.999'],
            'reason' => ['required', 'string', 'max:2000'], 'request_id' => ['required', 'uuid'], 'confirm' => ['accepted'],
        ]);
        $finance->reverseCommission($entry, $request->user()->id, (string) $data['amount'], $data['reason'], $data['request_id']);

        return back()->with('success', 'تم تسجيل قيد التصحيح في الرصيد التجريبي.');
    }

    private function actor(Request $request): User
    {
        abort_unless(config('instant.enabled'), 404);
        abort_unless($request->user()?->status === 'active', 403);

        return $request->user();
    }

    private function vendorActor(Request $request): User
    {
        $vendor = $this->actor($request);
        abort_unless($vendor->role === 'vendor' && $vendor->vendorProfile?->application_status === 'approved', 403);

        return $vendor;
    }
}
