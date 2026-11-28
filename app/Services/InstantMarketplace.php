<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\InstantOffer;
use App\Models\InstantRequest;
use App\Models\Notification;
use App\Models\Service;
use App\Models\User;
use App\Models\VendorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class InstantMarketplace
{
    public const ACTIVE_BOOKINGS = ['confirmed', 'on_the_way', 'arrived', 'in_progress'];

    public function catalog(): Builder
    {
        return Service::query()->where('supports_instant', true)->where('is_active', true)
            ->where('is_deleted', false)->where('approval_status', 'approved')
            ->whereHas('category', fn ($rows) => $rows->where('is_deleted', false));
    }

    public function start(User $customer, array $data, array $uploads = []): InstantRequest
    {
        $paths = [];
        try {
            return DB::transaction(function () use ($customer, $data, $uploads, &$paths) {
            abort_unless(User::query()->lockForUpdate()->findOrFail($customer->id)->status === 'active', 403);
            $this->checkCustomer($customer->id);
            abort_unless($this->catalog()->whereKey($data['service_id'])->exists(), 422);
            $request = InstantRequest::create($data + ['customer_id' => $customer->id, 'status' => 'searching', 'expires_at' => now()->addMinutes(5)]);
            app(InstantImages::class)->save($uploads, 'problem', 'instant_request_id', $request->id, $customer->id, $paths);
            $this->notifyMatches($request);

            return $request;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }
    }

    public function eligible(InstantRequest $request, VendorService $offer, int $arrival = 0, int $duration = 0): bool
    {
        $vendor = $offer->vendor;
        $profile = $vendor?->vendorProfile;
        $age = (int) config('instant.location_max_age_seconds');
        if ($age < 1 || $request->status !== 'searching' || ! $request->expires_at->isFuture()
            || (int) $offer->vendor_id === (int) $request->customer_id || (int) $offer->service_id !== (int) $request->service_id
            || ! $offer->instant_enabled || $offer->status !== 'active'
            || $vendor?->role !== 'vendor' || $vendor->status !== 'active' || $profile?->application_status !== 'approved'
            || ! $profile->work_permit_path || ! $profile->permit_expiry_date || $profile->permit_expiry_date < today(Booking::SCHEDULE_TIMEZONE)->toDateString()
            || ! $profile->instant_available || $profile->latitude === null || $profile->longitude === null
            || ! $profile->location_updated_at || ! $profile->last_seen_at
            || now()->diffInSeconds($profile->location_updated_at, true) > $age || now()->diffInSeconds($profile->last_seen_at, true) > $age
            || ! $this->catalog()->whereKey($request->service_id)->exists()) {
            return false;
        }

        $radius = 3;
        $elapsed = now()->diffInSeconds($request->created_at, true);
        $stages = config('instant.radius_after_seconds');
        if (is_numeric($stages[5]) && is_numeric($stages[10]) && (int) $stages[5] > 0
            && (int) $stages[10] > (int) $stages[5] && (int) $stages[10] < 300) {
            $radius = $elapsed >= (int) $stages[10] ? 10 : ($elapsed >= (int) $stages[5] ? 5 : 3);
        }
        $customerLat = deg2rad((float) $request->latitude);
        $vendorLat = deg2rad((float) $profile->latitude);
        $longitude = deg2rad((float) $request->longitude - (float) $profile->longitude);
        $distance = 12742 * asin(sqrt(min(1, sin(($customerLat - $vendorLat) / 2) ** 2 + cos($customerLat) * cos($vendorLat) * sin($longitude / 2) ** 2)));
        if ($distance > $radius) {
            return false;
        }

        $start = now(Booking::SCHEDULE_TIMEZONE);
        $end = $start->copy()->addMinutes(max(1, $arrival + $duration));
        $bookings = Booking::query()->whereHas('vendorService', fn ($rows) => $rows->where('vendor_id', $vendor->id));

        return ! (clone $bookings)->where('booking_type', 'instant')->whereIn('status', self::ACTIVE_BOOKINGS)->exists()
            && ! $bookings->where('booking_type', 'scheduled')->blockingSlot()->overlappingInterval($start, $end)->exists();
    }

    public function offer(User $vendor, InstantRequest $request, array $data): void
    {
        DB::transaction(function () use ($vendor, $request, $data) {
            User::query()->lockForUpdate()->findOrFail($vendor->id);
            $current = InstantRequest::query()->lockForUpdate()->findOrFail($request->id);
            $offer = VendorService::query()->where('vendor_id', $vendor->id)->with('vendor.vendorProfile')->findOrFail($data['vendor_service_id']);
            abort_unless($this->eligible($current, $offer, $data['arrival_minutes'], $data['duration_minutes']), 409);
            $current->offers()->updateOrCreate(['vendor_service_id' => $offer->id], $data + ['status' => 'pending']);
            $this->notify($current->customer_id, 'وصلك عرض لخدمة فورية');
        }, 3);
    }

    public function accept(User $customer, InstantRequest $request, InstantOffer $offer): Booking
    {
        abort_unless((int) $request->customer_id === $customer->id && (int) $offer->instant_request_id === $request->id, 404);
        $vendorId = (int) $offer->vendorService?->vendor_id;
        return DB::transaction(function () use ($customer, $request, $offer, $vendorId) {
            $users = User::query()->whereKey([$customer->id, $vendorId])->orderBy('id')->lockForUpdate()->get();
            abort_unless($users->count() === 2, 409);
            abort_unless($users->firstWhere('id', $customer->id)->status === 'active', 403);
            $request = InstantRequest::query()->lockForUpdate()->findOrFail($request->id);
            $offer = $request->offers()->with('vendorService.vendor.vendorProfile', 'vendorService.service')->lockForUpdate()->findOrFail($offer->id);
            if ($offer->status === 'accepted') {
                return Booking::where('instant_offer_id', $offer->id)->firstOrFail();
            }
            $this->checkCustomer($customer->id, $request->id);
            $vendorService = $offer->vendorService;
            abort_unless($offer->status === 'pending' && (int) $vendorService->vendor_id === $vendorId
                && $this->eligible($request, $vendorService, $offer->arrival_minutes, $offer->duration_minutes), 409);
            $start = now(Booking::SCHEDULE_TIMEZONE)->addMinutes($offer->arrival_minutes);
            $end = $start->copy()->addMinutes($offer->duration_minutes);
            $booking = new Booking;
            $booking->forceFill([
                'customer_id' => $customer->id, 'vendor_service_id' => $vendorService->id,
                'booking_type' => 'instant', 'instant_offer_id' => $offer->id, 'status' => 'confirmed',
                'availability_id' => null, 'booking_date' => $start->toDateString(),
                'start_time' => $start->toTimeString(), 'end_time' => $end->toTimeString(),
                'service_name' => $vendorService->service->name, 'service_description' => $request->description,
                'original_price' => $offer->offered_price, 'agreed_price' => $offer->offered_price,
                'expected_arrival_at' => $start->copy()->utc(), 'settlement_status' => 'pending',
            ])->save();
            $offer->update(['status' => 'accepted']);
            $request->update(['status' => 'assigned']);
            InstantOffer::query()->where('status', 'pending')->where(fn ($rows) => $rows
                ->where('instant_request_id', $request->id)
                ->orWhereHas('vendorService', fn ($services) => $services->where('vendor_id', $vendorId)))
                ->update(['status' => 'invalidated']);
            $this->event($booking, $customer->id, ['event_type' => 'status_change', 'status_type' => 'operational', 'new_status' => 'confirmed']);
            $this->notify($vendorId, 'العميل اختار عرضك لخدمة فورية');

            return $booking;
        }, 3);
    }

    public function expire(): void
    {
        InstantRequest::where('status', 'searching')->where('expires_at', '<=', now())->chunkById(100, function ($requests) {
            foreach ($requests as $candidate) {
                DB::transaction(function () use ($candidate) {
                    $request = InstantRequest::query()->lockForUpdate()->find($candidate->id);
                    if ($request?->status === 'searching' && ! $request->expires_at->isFuture()) {
                        $request->update(['status' => 'expired']);
                        $request->offers()->where('status', 'pending')->update(['status' => 'expired']);
                        $this->notify($request->customer_id, 'انتهت مهلة البحث عن فني');
                    }
                }, 3);
            }
        });
    }

    public function notifyActiveMatches(): void
    {
        InstantRequest::where('status', 'searching')->where('expires_at', '>', now())->chunkById(100, function ($requests) {
            foreach ($requests as $candidate) {
                DB::transaction(function () use ($candidate) {
                    $request = InstantRequest::query()->lockForUpdate()->find($candidate->id);
                    if ($request?->status === 'searching' && $request->expires_at->isFuture()) {
                        $this->notifyMatches($request);
                    }
                }, 3);
            }
        });
    }

    private function notifyMatches(InstantRequest $request): void
    {
        $message = 'طلب خدمة فورية قريب رقم '.$request->id.'.';
        VendorService::publiclyVisible()->where('instant_enabled', true)->where('service_id', $request->service_id)
            ->with('vendor.vendorProfile')->chunkById(100, function ($offers) use ($request, $message) {
                $notified = Notification::whereIn('user_id', $offers->pluck('vendor_id'))->where('title', 'صلّحها الآن')
                    ->where('message', $message)->pluck('user_id')->all();
                foreach ($offers->unique('vendor_id') as $offer) {
                    if (! in_array($offer->vendor_id, $notified) && $this->eligible($request, $offer)) {
                        $this->notify($offer->vendor_id, $message);
                    }
                }
            });
    }

    private function checkCustomer(int $id, ?int $exceptRequest = null): void
    {
        if (app(InstantFinance::class)->hasDebt($id)) {
            throw ValidationException::withMessages(['service_id' => 'سدّد المبلغ المستحق قبل طلب حجز جديد.']);
        }
        $busy = InstantRequest::where('customer_id', $id)->where('status', 'searching')->where('expires_at', '>', now())
            ->when($exceptRequest, fn ($rows) => $rows->whereKeyNot($exceptRequest))->exists()
            || Booking::where('customer_id', $id)->where('booking_type', 'instant')->whereIn('status', self::ACTIVE_BOOKINGS)->exists();
        if ($busy) {
            throw ValidationException::withMessages(['service_id' => 'عندك طلب فوري أو حجز فوري شغّال.']);
        }
    }

    public function event(Booking $booking, int $actor, array $data): void
    {
        DB::table('booking_events')->insert($data + ['booking_id' => $booking->id, 'performed_by' => $actor]);
    }

    public function notifyDurationOverruns(): void
    {
        Booking::where('booking_type', 'instant')->where('status', 'in_progress')
            ->whereNotNull('expected_arrival_at')->whereNotNull('instant_offer_id')
            ->chunkById(100, function ($bookings) {
                foreach ($bookings as $booking) {
                    DB::transaction(function () use ($booking) {
                        $current = Booking::query()->lockForUpdate()->find($booking->id);
                        if (! $current || $current->status !== 'in_progress') return;
                        $duration = InstantOffer::query()->whereKey($current->instant_offer_id)->value('duration_minutes');
                        if (! $duration || ! $current->expected_arrival_at) return;
                        $estimatedEnd = $current->expected_arrival_at->copy()->addMinutes($duration)->timezone(Booking::SCHEDULE_TIMEZONE);
                        if ($estimatedEnd->isFuture()) return;
                        $vendor = $current->vendorService->vendor_id;
                        $next = Booking::query()->where('booking_type', 'scheduled')->whereIn('status', ['confirmed', 'paid'])
                            ->whereHas('vendorService', fn ($rows) => $rows->where('vendor_id', $vendor))
                            ->whereRaw('TIMESTAMP(booking_date, end_time) > ?', [$estimatedEnd->toDateTimeString()])
                            ->orderBy('booking_date')->orderBy('start_time')->first();
                        $description = $next
                            ? 'تجاوز الحجز الفوري المدة المقدّرة. راجع الحجز المجدول التالي رقم '.$next->id.'.'
                            : 'تجاوز الحجز الفوري المدة المقدّرة.';

                        if (DB::table('booking_events')->where('booking_id', $current->id)
                            ->where('status_type', 'operational')->where('new_status', 'duration_overrun')
                            ->where('description', $description)->exists()) return;
                        $this->event($current, $vendor, [
                            'event_type' => 'status_change', 'status_type' => 'operational',
                            'new_status' => 'duration_overrun', 'description' => $description,
                        ]);
                        $this->notify($vendor, $description);
                    }, 3);
                }
            });
    }

    public function notify(int $recipient, string $message): void
    {
        Notification::create(['user_id' => $recipient, 'title' => 'صلّحها الآن', 'message' => $message, 'type' => 'booking']);
    }
}
