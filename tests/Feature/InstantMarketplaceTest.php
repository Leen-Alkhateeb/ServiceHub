<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\InstantOffer;
use App\Models\InstantRequest;
use App\Models\Service;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorService;
use App\Services\InstantFinance;
use App\Services\InstantMarketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstantMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\DataProvider('exceptionalResolutions')]
    public function test_exceptional_service_resolution_reconciles_payment_and_debt(
        string $caseType, bool $paid, string $approved, string $refund,
    ): void {
        [$customer, , $service] = $this->fixture();
        $booking = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'completed', 'agreed_price' => '100.000',
            'completed_at' => now(), 'objection_deadline' => now()->addDay(), 'payment_deadline' => now()->addDay(),
            'settlement_status' => 'on_hold',
        ]);
        $finance = app(InstantFinance::class);
        if ($paid) $finance->collect($booking, $customer->id);
        $this->actingAs($customer)->post(route('instant.cases', $booking), [
            'case_type' => $caseType, 'reason' => 'طلب تحديد قيمة العمل الفعلية',
        ])->assertRedirect();
        $case = $booking->cases()->sole();
        $admin = User::factory()->admin()->create();
        $route = route('admin.instant.operational-decision', $booking);
        $decision = ['decision' => 'accepted', 'case_id' => $case->id, 'service_amount' => $approved,
            'reason' => 'اطلعت على أدلة الطرفين', 'confirm_financial_resolution' => '1'];
        $this->actingAs($admin)->post($route, $decision)->assertRedirect();
        $this->post($route, $decision)->assertRedirect();
        $this->assertDatabaseHas('refund_requests', ['id' => $case->id, 'approved_service_amount' => $approved, 'refund_amount' => $refund]);
        $this->assertSame($refund !== '0.000' ? 1 : 0, $booking->walletTransactions()->where('transaction_type', 'refund')->count());
        if ($approved === '0.000') {
            $this->assertSame('cancelled', $booking->fresh()->status);
            $this->assertFalse($finance->hasDebt($customer->id));
            $this->assertFalse($booking->walletTransactions()->where('transaction_type', 'payout')->exists());
            if ($paid) $this->assertSame('refunded', $booking->payment()->sole()->status);
            return;
        }
        if (! $paid) {
            $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'debt', 'amount' => '40.000', 'status' => 'outstanding']);
            $finance->collect($booking, $customer->id);
            $this->assertSame('40.000', $booking->payment()->sole()->paid_amount);
        }
        $finance->run();
        $this->assertFalse($booking->walletTransactions()->where('transaction_type', 'payout')->exists());
        $this->travelTo($booking->fresh()->objection_deadline);
        $finance->run();
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'payout', 'amount' => '34.000']);
        $this->assertSame(6000, $finance->availableFils());
    }

    public static function exceptionalResolutions(): array
    {
        return [
            'paid partial' => ['partial_service', true, '40.000', '60.000'],
            'unpaid partial' => ['partial_service', false, '40.000', '0.000'],
            'paid non-execution' => ['non_execution', true, '0.000', '100.000'],
            'unpaid non-execution' => ['non_execution', false, '0.000', '0.000'],
            'paid exceptional cancellation' => ['exceptional_cancellation', true, '40.000', '60.000'],
        ];
    }

    public function test_exceptional_resolution_collects_only_the_unpaid_remainder_of_a_partial_payment(): void
    {
        [$customer, , $service] = $this->fixture();
        $booking = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'completed', 'agreed_price' => '100.000',
            'completed_at' => now(), 'objection_deadline' => now()->addDay(), 'payment_deadline' => now()->addDay(),
            'settlement_status' => 'on_hold',
        ]);
        \App\Models\Payment::factory()->for($booking)->create(['amount' => '20.000', 'paid_amount' => '20.000', 'status' => 'paid']);
        $this->actingAs($customer)->post(route('instant.cases', $booking), [
            'case_type' => 'partial_service', 'reason' => 'تنفيذ جزء من الخدمة',
        ])->assertRedirect();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.instant.operational-decision', $booking), [
            'decision' => 'accepted', 'case_id' => $booking->cases()->sole()->id, 'service_amount' => '40.000',
            'reason' => 'تم تحديد قيمة الجزء المنجز', 'confirm_financial_resolution' => '1',
        ])->assertRedirect();
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'debt', 'amount' => '20.000', 'status' => 'outstanding']);
        $finance = app(InstantFinance::class);
        $this->travelTo($booking->fresh()->objection_deadline);
        $finance->run();
        $this->assertFalse($booking->walletTransactions()->where('transaction_type', 'payout')->exists());
        $finance->collect($booking, $customer->id);
        $finance->collect($booking, $customer->id);
        $this->assertSame('40.000', $booking->payment()->sole()->paid_amount);
        $this->assertSame(1, $booking->walletTransactions()->where('transaction_type', 'debt_repayment')->count());
        $finance->run();
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'payout', 'amount' => '34.000']);
        $this->assertSame(6000, $finance->availableFils());
    }

    public function test_documented_vendor_and_customer_overpayments_recover_to_the_original_funding_party(): void
    {
        [$customer, $vendor, $service] = $this->fixture();
        $admin = User::factory()->admin()->create();
        $finance = app(InstantFinance::class);
        $paid = function (string $price) use ($customer, $service, $finance): Booking {
            $booking = Booking::factory()->for($service)->create([
                'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'completed', 'agreed_price' => $price,
                'completed_at' => now()->subDays(2), 'objection_deadline' => now()->subDay(), 'payment_deadline' => now()->subDay(),
                'settlement_status' => 'on_hold',
            ]);
            $finance->collect($booking, $customer->id);
            $finance->run();

            return $booking;
        };
        $original = $paid('100.000');
        $payout = $original->walletTransactions()->where('transaction_type', 'payout')->sole();
        $correction = ['corrected_entitlement' => '65.000', 'reason' => 'تمت مطابقة الاستحقاق', 'confirm' => '1'];
        $this->actingAs($admin)->post(route('admin.instant.correct-overpayment', [$original, $payout->id]), $correction)->assertRedirect();
        $this->post(route('admin.instant.correct-overpayment', [$original, $payout->id]), $correction)->assertRedirect();
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $original->id, 'transaction_type' => 'debt',
            'amount' => '20.000', 'user_id' => $vendor->id, 'status' => 'outstanding']);
        $this->assertSame('85.000', $payout->fresh()->amount);
        $this->assertSame(15000, $finance->availableFils());
        $next = $paid('30.000');
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $next->id, 'transaction_type' => 'payout', 'amount' => '5.500']);
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $original->id, 'transaction_type' => 'adjustment',
            'amount' => '20.000', 'user_id' => $customer->id, 'status' => 'posted']);

        $cancelled = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'confirmed', 'agreed_price' => '200.000',
        ]);
        $this->actingAs($vendor)->post(route('instant.transition', $cancelled), ['action' => 'cancel'])->assertRedirect();
        $paid('100.000');
        $comp = $cancelled->walletTransactions()->where('transaction_type', 'compensation')->sole();
        $this->actingAs($admin)->post(route('admin.instant.correct-overpayment', [$cancelled, $comp->id]),
            array_replace($correction, ['corrected_entitlement' => '20.000']))->assertRedirect();
        $this->assertSame('30.000', $comp->fresh()->amount);
        $this->assertTrue($finance->hasDebt($customer->id));
        $debt = $cancelled->walletTransactions()->where('account_type', 'customer')->where('transaction_type', 'debt')->sole();
        $this->assertSame('10.000', $debt->amount);
        $this->actingAs($vendor)->post(route('instant.debts.repay', $debt->id))->assertNotFound();
        $this->actingAs($customer)->post(route('instant.debts.repay', $debt->id))->assertRedirect();
        $this->post(route('instant.debts.repay', $debt->id))->assertRedirect();
        $this->assertFalse($finance->hasDebt($customer->id));
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $cancelled->id, 'transaction_type' => 'adjustment',
            'amount' => '10.000', 'user_id' => $vendor->id, 'status' => 'posted']);
        $this->assertSame(34500, $finance->availableFils());
    }

    public function test_non_execution_supersedes_an_existing_service_debt_without_recording_payment(): void
    {
        [$customer, , $service] = $this->fixture();
        $booking = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'completed', 'agreed_price' => '100.000',
            'completed_at' => now()->subDays(2), 'objection_deadline' => now()->subDay(), 'payment_deadline' => now()->subDay(),
            'settlement_status' => 'on_hold',
        ]);
        $finance = app(InstantFinance::class);
        $finance->run();
        $debt = $booking->walletTransactions()->where('transaction_type', 'debt')->sole();
        $this->actingAs($customer)->post(route('instant.cases', $booking), [
            'case_type' => 'non_execution', 'reason' => 'لم ينفذ العمل',
        ])->assertRedirect();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.instant.operational-decision', $booking), [
            'decision' => 'accepted', 'case_id' => $booking->cases()->sole()->id, 'service_amount' => '0.000',
            'reason' => 'تحققت من عدم التنفيذ', 'confirm_financial_resolution' => '1',
        ])->assertRedirect();
        $this->assertSame('100.000', $debt->fresh()->amount);
        $this->assertSame('superseded', $debt->fresh()->status);
        $this->assertDatabaseHas('wallet_transactions', ['related_transaction_id' => $debt->id, 'transaction_type' => 'adjustment', 'amount' => '100.000']);
        $this->assertFalse($finance->hasDebt($customer->id));
        $this->assertFalse($booking->payment()->exists());
        $this->assertSame(0, $finance->availableFils());
    }

    private function fixture(): array
    {
        config(['instant.enabled' => true]);
        $this->travelTo(now(Booking::SCHEDULE_TIMEZONE)->setTime(11, 0));
        $customer = User::factory()->create();
        $profile = VendorProfile::factory()->create([
            'instant_available' => true, 'latitude' => 31.9500, 'longitude' => 35.9100,
            'last_seen_at' => now(), 'location_updated_at' => now(),
        ]);
        $service = Service::factory()->create();
        $offer = VendorService::factory()->for($profile->user, 'vendor')->for($service)->create(['instant_enabled' => true]);

        return [$customer, $profile->user, $offer];
    }

    public function test_active_approved_services_are_instant_by_default_and_admin_tabs_render(): void
    {
        [$customer, , $offer] = $this->fixture();
        $inactive = Service::factory()->create(['is_active' => false]);

        $this->actingAs($customer)->get(route('instant.index'))->assertOk()
            ->assertSee($offer->service->name)->assertDontSee($inactive->name);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.instant.index'))->assertOk()
            ->assertSee('الطلبات الفورية')->assertDontSee('تفعيل الفوري');
        $this->get(route('admin.instant.index', ['section' => 'reviews']))->assertOk()
            ->assertSee('مستحقات الفنيين');
        $this->get(route('admin.instant.index', ['section' => 'finance']))->assertOk()
            ->assertSee('تصحيح العمولات التجريبية');
    }

    public function test_instant_suggestion_requests_details_and_only_accepts_ids_from_the_instant_catalog(): void
    {
        [$customer, , $offer] = $this->fixture();
        config(['services.gemini.key' => 'test-key', 'instant.suggestion_model' => 'test-model']);
        $route = route('instant.suggest');
        $response = fn ($result) => ['candidates' => [['content' => ['parts' => [['text' => json_encode($result)]]]]]];
        \Illuminate\Support\Facades\Http::fake([
            'https://generativelanguage.googleapis.com/*' => \Illuminate\Support\Facades\Http::sequence()
                ->push($response(['status' => 'clarify', 'question' => 'من وين بتنقط المي؟']))
                ->push($response(['status' => 'matched', 'service_id' => $offer->service_id]))
                ->push($response(['status' => 'matched', 'service_id' => 999999]))
                ->push($response(['status' => 'unsupported'])),
        ]);

        $this->actingAs($customer)->postJson($route, ['problem' => 'المي بتنقط'])
            ->assertOk()->assertJsonPath('status', 'clarify')->assertJsonPath('message', 'من وين بتنقط المي؟');
        $this->postJson($route, ['problem' => "المي بتنقط\nمن تحت حوض المطبخ"])
            ->assertOk()->assertJsonPath('service_id', $offer->service_id);
        $this->postJson($route, ['problem' => 'طلب غير معروف'])
            ->assertOk()->assertJsonPath('status', 'unavailable')->assertJsonMissingPath('service_id');
        $this->postJson($route, ['problem' => 'المكيف ما ببرد'])
            ->assertOk()->assertJsonPath('status', 'unsupported')->assertJsonMissingPath('service_id');
    }

    public function test_instant_offer_booking_arrival_price_and_settlement_are_isolated_and_idempotent(): void
    {
        [$customer, $vendor, $service] = $this->fixture();
        $data = ['service_id' => $service->service_id, 'description' => 'عطل في المضخة', 'address' => 'عنوان خاص للاختبار', 'latitude' => 31.9501, 'longitude' => 35.9101];
        $this->actingAs($customer)->post(route('instant.create'), $data)->assertRedirect();
        $instant = InstantRequest::sole();
        $this->post(route('instant.create'), $data)->assertSessionHasErrors('service_id');
        $this->actingAs($vendor)->get(route('instant.vendor'))->assertOk()->assertSee('عطل في المضخة')->assertDontSee($data['address']);
        $this->post(route('instant.offer', $instant), ['vendor_service_id' => $service->id, 'offered_price' => '100.000', 'arrival_minutes' => 15, 'duration_minutes' => 30])->assertRedirect();
        $offer = InstantOffer::sole();
        $accept = route('instant.accept', [$instant, $offer]);
        $this->actingAs($vendor)->post($accept)->assertNotFound();
        $this->actingAs($customer)->post($accept)->assertRedirect();
        $booking = Booking::where('booking_type', 'instant')->sole();
        $this->post($accept)->assertRedirect(route('instant.booking', $booking));
        $this->assertDatabaseCount('bookings', 1);
        $transition = route('instant.transition', $booking);
        $adjust = route('instant.adjust', $booking);
        $this->actingAs($vendor)->post($transition, ['action' => 'depart'])->assertRedirect();
        $this->post($transition, ['action' => 'arrive', 'latitude' => 31.95, 'longitude' => 35.91])->assertRedirect();
        $code = Crypt::decryptString($booking->refresh()->arrival_code_encrypted);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->post($transition, ['action' => 'start'])->assertConflict();
        $this->get(route('instant.booking', $booking))->assertOk()->assertSee($data['address'])->assertSee($code);
        $this->actingAs($customer)->get(route('instant.booking', $booking))->assertOk()->assertDontSee('<bdi>'.$code.'</bdi>', false);
        $wrong = $code === '000000' ? '111111' : '000000';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post($transition, ['action' => 'verify', 'arrival_code' => $wrong])->assertSessionHasErrors('arrival_code');
        }
        $this->post($transition, ['action' => 'verify', 'arrival_code' => $code])->assertTooManyRequests();
        $this->actingAs($vendor)->post($transition, ['action' => 'reissue'])->assertTooManyRequests();
        $this->travel(5)->minutes();
        $this->post($transition, ['action' => 'reissue'])->assertRedirect();
        $replacement = Crypt::decryptString($booking->refresh()->arrival_code_encrypted);
        $this->assertNotSame($code, $replacement);
        $this->actingAs($customer)->post($transition, ['action' => 'verify', 'arrival_code' => $replacement])->assertRedirect();
        $this->post($transition, ['action' => 'verify', 'arrival_code' => $replacement])->assertConflict();
        $this->actingAs($vendor)->post($transition, ['action' => 'start'])->assertRedirect();
        $this->post($adjust, ['decision' => 'propose', 'description' => 'قطعة إضافية', 'proposed_price' => '150.000'])->assertRedirect();
        $this->post($transition, ['action' => 'complete', 'completion_description' => 'إنجاز'])->assertConflict();
        $event = \Illuminate\Support\Facades\DB::table('booking_events')->where('event_type', 'price_adjustment')->sole();
        $this->actingAs($customer)->post($adjust, ['decision' => 'accepted', 'adjustment_id' => $event->id])->assertRedirect();
        $this->actingAs($vendor)->post($transition, ['action' => 'complete', 'completion_description' => 'تم الإصلاح'])->assertRedirect();
        $this->post($transition, ['action' => 'complete', 'completion_description' => 'طلب مكرر'])->assertRedirect();
        $this->assertSame('تم الإصلاح', $booking->fresh()->completion_description);
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('booking_events')->where('booking_id', $booking->id)
            ->where('new_status', 'completed')->count());
        $this->assertSame('150.000', $booking->refresh()->agreed_price);
        $this->assertSame('100.000', $booking->original_price);
        $this->actingAs($customer)->post(route('instant.pay', $booking), ['amount' => 1])->assertRedirect();
        $this->assertDatabaseHas('payments', ['booking_id' => $booking->id, 'paid_amount' => '150.000']);
        app(InstantFinance::class)->run();
        $this->assertDatabaseMissing('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'payout']);
        $this->travel(24)->hours();
        app(InstantFinance::class)->run();
        app(InstantFinance::class)->run();
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'payout', 'amount' => '127.500']);
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'commission', 'amount' => '22.500']);
        $this->assertSame(3, $booking->walletTransactions()->count());
        $this->actingAs(User::factory()->admin()->create())->delete(route('admin.users.destroy', $customer))->assertRedirect();
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseMissing('instant_requests', ['id' => $instant->id]);
        $this->assertDatabaseMissing('wallet_transactions', ['booking_id' => $booking->id]);
    }

    public function test_radius_expands_and_search_remains_open_until_customer_stops_it(): void
    {
        [$customer, $vendor, $service] = $this->fixture();
        $this->actingAs($customer)->post(route('instant.create'), ['service_id' => $service->service_id, 'description' => 'عطل', 'address' => 'عنوان', 'latitude' => 31.985, 'longitude' => 35.91]);
        $request = InstantRequest::sole();
        $market = app(InstantMarketplace::class);
        $this->assertFalse($market->eligible($request, $service));
        $this->travel(59)->seconds();
        $this->assertFalse($market->eligible($request, $service));
        $this->travel(1)->seconds();
        $this->assertTrue($market->eligible($request, $service));
        $this->travel(1)->seconds();
        $this->assertFalse($market->eligible($request, $service));
        $this->travel(10)->minutes();
        $vendor->vendorProfile->forceFill(['location_updated_at' => now(), 'last_seen_at' => now()])->save();
        $this->assertTrue($market->eligible($request, $service->fresh()));
        $this->assertNull($request->refresh()->expires_at);
        $this->actingAs($vendor)->post(route('instant.offer', $request), ['vendor_service_id' => $service->id,
            'offered_price' => '40.000', 'arrival_minutes' => 10, 'duration_minutes' => 30])->assertRedirect();
        $this->actingAs($customer)->get(route('instant.search', $request))->assertOk()->assertSee('البحث شغّال لحد ما تختار عرض أو توقفه.');
        $this->post(route('instant.cancel-search', $request))->assertRedirect();
        $this->assertSame('cancelled', $request->refresh()->status);
        $this->assertSame('invalidated', $request->offers()->sole()->status);
        $this->assertDatabaseHas('notifications', ['user_id' => $vendor->id, 'message' => 'أوقف العميل البحث عن فني للطلب رقم '.$request->id.'.']);
    }

    public function test_request_images_are_private_and_limited_to_five(): void
    {
        if (! function_exists('imagewebp')) $this->markTestSkipped('GD WebP support is required.');

        [$customer, $vendor, $service] = $this->fixture();
        $disk = Storage::fake('local');
        $data = ['service_id' => $service->service_id, 'description' => 'عطل', 'address' => 'عنوان', 'latitude' => 31.95, 'longitude' => 35.91];
        $images = fn (int $amount) => array_map(fn ($number) => UploadedFile::fake()->image("test-$number.png", 40, 40), range(1, $amount));
        $this->actingAs($customer)->post(route('instant.create'), $data + ['images' => $images(6)])->assertSessionHasErrors('images');
        $this->post(route('instant.create'), $data + ['images' => $images(5)])->assertRedirect();
        $request = InstantRequest::sole();
        $this->assertDatabaseCount('images', 5);
        $image = \App\Models\BookingEvidence::firstOrFail();
        $disk->assertExists($image->path);
        $route = route('instant.image', [$request, $image]);
        $this->actingAs(User::factory()->create())->get($route)->assertNotFound();
        $this->actingAs($vendor)->get($route)->assertOk();
        $this->actingAs($customer)->get($route)->assertOk();
    }

    public function test_vendor_bubble_counts_nearby_requests_until_the_vendor_offers(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            config('instant.routing_url').'/table/*' => \Illuminate\Support\Facades\Http::response([
                'code' => 'Ok', 'distances' => [[2400]], 'durations' => [[420]],
            ]),
        ]);
        [$customer, $vendor, $service] = $this->fixture();
        $this->actingAs($customer)->post(route('instant.create'), [
            'service_id' => $service->service_id, 'description' => 'عطل', 'address' => 'عنوان العميل الخاص', 'latitude' => 31.951234, 'longitude' => 35.912345,
        ])->assertRedirect();
        $count = route('instant.vendor.inbox-count');
        $this->getJson($count)->assertForbidden();
        $this->get(route('home'))->assertOk()->assertDontSee('data-instant-inbox', false);
        $this->actingAs($vendor)->get(route('vendor.dashboard'))->assertOk()->assertSee('data-instant-inbox', false);
        $this->get(route('home'))->assertOk()->assertSee('data-instant-inbox', false);
        $this->getJson($count)->assertOk()->assertJsonPath('count', 1);
        $this->get(route('instant.vendor'))->assertOk()
            ->assertSee('marker=31.95%2C35.91', false)
            ->assertSee('2.4 كم')
            ->assertSee('حوالي 7 د')
            ->assertDontSee('31.951234')
            ->assertDontSee('35.912345')
            ->assertDontSee('عنوان العميل الخاص');
        $this->post(route('instant.offer', InstantRequest::sole()), [
            'vendor_service_id' => $service->id, 'offered_price' => '40.000', 'arrival_minutes' => 10, 'duration_minutes' => 30,
        ])->assertRedirect();
        $this->getJson($count)->assertOk()->assertJsonPath('count', 0);
    }

    public function test_paid_objection_waits_for_deadline_and_allocates_the_agreed_price(): void
    {
        [$customer, $vendor, $service] = $this->fixture();
        $booking = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'original_price' => '100.000', 'agreed_price' => '150.000',
            'status' => 'completed', 'completed_at' => now(), 'payment_deadline' => now()->addHours(24),
            'objection_deadline' => now()->addHours(24), 'settlement_status' => 'on_hold',
        ]);
        $this->actingAs($customer)->post(route('instant.pay', $booking))->assertRedirect();
        $this->post(route('instant.object', $booking), ['reason' => 'الخدمة غير مكتملة'])->assertRedirect();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.instant.decide', $booking), ['decision' => 'accepted', 'reason' => 'مراجعة الأدلة'])->assertRedirect();
        app(InstantFinance::class)->run();
        $this->assertDatabaseMissing('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'refund']);
        $this->travel(24)->hours();
        app(InstantFinance::class)->run();
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'refund', 'amount' => '105.000']);
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'payout', 'amount' => '22.500']);
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'commission', 'amount' => '22.500']);
        $this->assertSame('completed', $booking->refresh()->status);
        $this->actingAs($customer)->post(route('instant.pay', $booking))->assertConflict();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('roundingCases')]
    public function test_instant_settlement_reconciles_fractional_allocations(
        string $price, bool $acceptedObjection, string $vendorShare, string $commission, ?string $refund,
    ): void {
        [$customer, , $service] = $this->fixture();
        $booking = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'original_price' => $price, 'agreed_price' => $price,
            'status' => 'completed', 'completed_at' => now(), 'payment_deadline' => now()->addDay(),
            'objection_deadline' => now()->addDay(), 'settlement_status' => 'on_hold',
        ]);
        $this->actingAs($customer)->post(route('instant.pay', $booking))->assertRedirect();
        if ($acceptedObjection) {
            $this->post(route('instant.object', $booking), ['reason' => 'الخدمة غير مكتملة'])->assertRedirect();
            $this->actingAs(User::factory()->admin()->create())->post(route('admin.instant.decide', $booking), [
                'decision' => 'accepted', 'reason' => 'مراجعة الأدلة',
            ])->assertRedirect();
        }
        $this->travel(24)->hours();
        app(InstantFinance::class)->run();
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'payout', 'amount' => $vendorShare]);
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'commission', 'amount' => $commission]);
        if ($refund !== null) {
            $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'refund', 'amount' => $refund]);
        }
        $this->assertSame('settled', $booking->refresh()->settlement_status);
    }

    public static function roundingCases(): array
    {
        return [
            'half-fils vendor round up' => ['1.010', false, '0.859', '0.151', null],
            'one fils' => ['0.001', false, '0.001', '0.000', null],
            'decimal maximum' => ['9999999.999', false, '8499999.999', '1500000.000', null],
            'objection remainder absorbed by platform' => ['1.010', true, '0.152', '0.151', '0.707'],
        ];
    }

    public function test_unpaid_objection_preserves_payment_deadline_and_collects_only_the_adjusted_debt(): void
    {
        [$customer, , $service] = $this->fixture();
        $booking = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'original_price' => '1.010', 'agreed_price' => '1.010',
            'status' => 'completed', 'completed_at' => now(), 'payment_deadline' => now()->addDay(),
            'objection_deadline' => now()->addDay(), 'settlement_status' => 'on_hold',
        ]);
        $this->actingAs($customer)->post(route('instant.object', $booking), ['reason' => 'الخدمة غير مكتملة'])->assertRedirect();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.instant.decide', $booking), [
            'decision' => 'accepted', 'reason' => 'مراجعة الأدلة',
        ])->assertRedirect();
        $this->assertTrue($booking->refresh()->payment_deadline->equalTo($booking->objection_deadline));
        $finance = app(InstantFinance::class);
        $finance->run();
        $this->assertTrue($finance->hasDebt($customer->id));
        $this->post(route('admin.instant.fund', $booking))->assertConflict();
        $this->travel(24)->hours();
        $finance->run();
        $finance->run();
        $this->assertTrue($finance->hasDebt($customer->id));
        $this->assertSame(1, $booking->walletTransactions()->count());
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'debt', 'amount' => '0.303']);
        $this->actingAs($customer)->post(route('instant.pay', $booking))->assertRedirect();
        $this->assertFalse($finance->hasDebt($customer->id));
        $finance->run();
        $this->assertDatabaseHas('payments', ['booking_id' => $booking->id, 'paid_amount' => '0.303']);
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'payout', 'amount' => '0.152']);
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'commission', 'amount' => '0.151']);
        $this->assertDatabaseMissing('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'refund']);
    }

    public function test_manual_advance_spends_verified_commission_and_repayment_recovers_it_once(): void
    {
        [$customer, , $service] = $this->fixture();
        $admin = User::factory()->admin()->create();
        $finance = app(InstantFinance::class);
        $attributes = [
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'completed',
            'completed_at' => now()->subDays(2), 'payment_deadline' => now()->subDay(),
            'objection_deadline' => now()->subDay(), 'settlement_status' => 'on_hold',
        ];
        $unpaid = Booking::factory()->for($service)->create($attributes + ['agreed_price' => '100.000']);
        $finance->run();
        $this->assertSame(0, $finance->availableFils());
        $this->actingAs($customer)->post(route('admin.instant.fund', $unpaid))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.instant.fund', $unpaid))->assertSessionHas('error');
        $this->assertSame('insufficient_funds', $unpaid->refresh()->settlement_status);
        $this->assertFalse($unpaid->walletTransactions()->where('transaction_type', 'payout')->exists());

        $paid = Booking::factory()->for($service)->create($attributes + ['agreed_price' => '666.667']);
        $finance->collect($paid, $customer->id);
        $this->assertSame(0, $finance->availableFils());
        $finance->run();
        $this->assertSame(100000, $finance->availableFils());
        $this->post(route('admin.instant.fund', $unpaid))->assertSessionHas('success');
        $this->post(route('admin.instant.fund', $unpaid))->assertSessionHas('success');
        $this->assertSame(15000, $finance->availableFils());
        $this->assertSame('advanced', $unpaid->refresh()->settlement_status);

        $finance->collect($unpaid, $customer->id);
        $finance->collect($unpaid, $customer->id);
        $finance->run();
        $finance->run();
        $this->assertSame(115000, $finance->availableFils());
        $this->assertFalse($finance->hasDebt($customer->id));
        $this->assertSame(1, $unpaid->walletTransactions()->where('transaction_type', 'payout')->count());
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $unpaid->id, 'transaction_type' => 'advance_recovery', 'amount' => '85.000']);
        $this->delete(route('admin.users.destroy', $customer))->assertRedirect();
        $this->assertDatabaseMissing('bookings', ['id' => $unpaid->id]);
        $this->assertSame(115000, $finance->availableFils());
    }

    public function test_departed_customer_cancellation_funds_compensation_only_after_a_day_and_recovers_on_repayment(): void
    {
        [$customer, $vendor, $service] = $this->fixture();
        $admin = User::factory()->admin()->create();
        $finance = app(InstantFinance::class);
        $earned = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'completed', 'agreed_price' => '100.000',
            'completed_at' => now()->subDays(2), 'payment_deadline' => now()->subDay(),
            'objection_deadline' => now()->subDay(), 'settlement_status' => 'on_hold',
        ]);
        $finance->collect($earned, $customer->id);
        $finance->run();
        $booking = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'on_the_way',
            'agreed_price' => '100.000', 'expected_arrival_at' => now()->addMinutes(15),
        ]);
        $this->actingAs($customer)->post(route('instant.transition', $booking), ['action' => 'cancel'])->assertRedirect();
        $this->assertDatabaseHas('refund_requests', ['booking_id' => $booking->id, 'beneficiary_id' => $vendor->id, 'compensation_amount' => '15.000']);
        $this->assertTrue($finance->hasDebt($customer->id));
        $this->actingAs($admin)->post(route('admin.instant.fund-compensation', $booking))->assertConflict();
        $this->travel(24)->hours();
        $this->post(route('admin.instant.fund-compensation', $booking))->assertSessionHas('success');
        $this->post(route('admin.instant.fund-compensation', $booking))->assertSessionHas('success');
        $this->assertSame(0, $finance->availableFils());
        $this->actingAs($customer)->post(route('instant.pay', $booking))->assertRedirect();
        $this->post(route('instant.pay', $booking))->assertRedirect();
        $this->assertSame(15000, $finance->availableFils());
        $this->assertFalse($finance->hasDebt($customer->id));
        $this->assertSame(1, $booking->walletTransactions()->where('transaction_type', 'compensation')->count());
        $this->assertFalse($booking->walletTransactions()->where('transaction_type', 'commission')->exists());
    }

    public function test_no_show_requires_wait_and_gps_and_only_an_admin_decision_creates_debt(): void
    {
        [$customer, $vendor, $service] = $this->fixture();
        $booking = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'on_the_way',
            'agreed_price' => '100.000', 'expected_arrival_at' => now()->addMinutes(5),
        ]);
        $this->actingAs($vendor)->post(route('instant.transition', $booking), [
            'action' => 'arrive', 'latitude' => 31.95, 'longitude' => 35.91,
        ])->assertRedirect();
        $this->post(route('instant.no-show', $booking), ['reason' => 'لم يحضر العميل'])->assertConflict();
        $this->travel(10)->minutes();
        $this->post(route('instant.no-show', $booking), ['reason' => 'لم يحضر العميل'])->assertRedirect();
        $this->assertFalse(app(InstantFinance::class)->hasDebt($customer->id));
        $this->post(route('instant.no-show', $booking), ['reason' => 'طلب مكرر'])->assertConflict();
        $decision = ['decision' => 'accepted', 'reason' => 'تمت مراجعة البلاغ والأدلة'];
        $this->actingAs($customer)->post(route('admin.instant.operational-decision', $booking), $decision)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.instant.operational-decision', $booking), $decision)->assertRedirect();
        $this->post(route('admin.instant.operational-decision', $booking), $decision)->assertNotFound();
        $this->assertSame('cancelled', $booking->refresh()->status);
        $this->assertTrue(app(InstantFinance::class)->hasDebt($customer->id));
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'debt', 'amount' => '15.000']);
    }

    public function test_vendor_cancellation_debt_is_recovered_in_parts_from_future_instant_earnings(): void
    {
        [$customer, $vendor, $service] = $this->fixture();
        $cancelled = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'confirmed', 'agreed_price' => '100.000',
        ]);
        $this->actingAs($vendor)->post(route('instant.transition', $cancelled), ['action' => 'cancel'])->assertRedirect();
        $this->assertDatabaseHas('wallet_transactions', [
            'booking_id' => $cancelled->id, 'transaction_type' => 'debt', 'account_type' => 'vendor', 'user_id' => $vendor->id, 'amount' => '15.000',
        ]);
        $finance = app(InstantFinance::class);
        foreach (['outstanding' => '10.000', 'repaid' => '20.000'] as $status => $price) {
            $earned = Booking::factory()->for($service)->create([
                'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'completed', 'agreed_price' => $price,
                'completed_at' => now()->subDays(2), 'payment_deadline' => now()->subDay(),
                'objection_deadline' => now()->subDay(), 'settlement_status' => 'on_hold',
            ]);
            $finance->collect($earned, $customer->id);
            $finance->run();
            $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $cancelled->id, 'transaction_type' => 'debt', 'status' => $status]);
        }
        $this->assertSame(2, $cancelled->walletTransactions()->where('transaction_type', 'debt_repayment')->count());
        $this->assertEquals('15.000', $cancelled->walletTransactions()->where('transaction_type', 'compensation')->sum('amount'));
        $this->assertFalse($cancelled->walletTransactions()->where('transaction_type', 'commission')->exists());
        $this->assertSame(4500, $finance->availableFils());
    }

    public function test_instant_overnight_interval_blocks_overlap_but_not_adjacent_slots(): void
    {
        [$customer, , $service] = $this->fixture();
        $start = now(Booking::SCHEDULE_TIMEZONE)->setTime(23, 45);
        $booking = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'confirmed',
            'booking_date' => $start->toDateString(), 'start_time' => '23:45:00', 'end_time' => '00:30:00',
        ]);
        $query = Booking::whereKey($booking->id)->blockingSlot();
        $this->assertTrue((clone $query)->overlappingInterval($start->copy()->addMinutes(25), $start->copy()->addHour())->exists());
        $this->assertFalse((clone $query)->overlappingInterval($start->copy()->addMinutes(45), $start->copy()->addHour())->exists());
        $this->assertFalse((clone $query)->overlappingInterval($start->copy()->subMinutes(30), $start)->exists());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('objectionDecisions')]
    public function test_unpaid_objection_debt_can_be_paid_immediately_without_releasing_the_hold(
        string $decision, string $amount, string $entitlement,
    ): void {
        [$customer, , $service] = $this->fixture();
        $booking = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'completed', 'agreed_price' => '100.000',
            'completed_at' => now(), 'payment_deadline' => now()->addDay(), 'objection_deadline' => now()->addDay(),
            'settlement_status' => 'on_hold',
        ]);
        $this->actingAs($customer)->post(route('instant.object', $booking), ['reason' => 'أعترض على نتيجة الخدمة'])->assertRedirect();
        $this->post(route('instant.pay', $booking))->assertConflict();
        $this->travel(6)->hours();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.instant.decide', $booking), [
            'decision' => $decision, 'reason' => 'تمت مراجعة الطلب',
        ])->assertRedirect();
        $this->assertDatabaseHas('wallet_transactions', [
            'booking_id' => $booking->id, 'transaction_type' => 'debt', 'amount' => $amount,
            'status' => 'outstanding', 'due_at' => now()->toDateTimeString(),
        ]);
        $this->actingAs($customer)->post(route('instant.pay', $booking))->assertRedirect();
        $finance = app(InstantFinance::class);
        $this->assertFalse($finance->hasDebt($customer->id));
        $finance->run();
        $this->assertFalse($booking->walletTransactions()->where('transaction_type', 'payout')->exists());
        $this->travelTo($booking->objection_deadline);
        $finance->run();
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'payout', 'amount' => $entitlement]);
        $this->assertSame(15000, $finance->availableFils());
    }

    public static function objectionDecisions(): array
    {
        return [
            'accepted' => ['accepted', '30.000', '15.000'],
            'rejected' => ['rejected', '100.000', '85.000'],
        ];
    }

    public function test_multiple_cases_hold_payment_and_settlement_until_each_is_resolved(): void
    {
        [$customer, $vendor, $service] = $this->fixture();
        $booking = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'completed', 'agreed_price' => '100.000',
            'completed_at' => now(), 'payment_deadline' => now()->addDay(), 'objection_deadline' => now()->addDay(), 'settlement_status' => 'on_hold',
        ]);
        $this->actingAs($customer)->post(route('instant.pay', $booking))->assertRedirect();
        $this->post(route('instant.object', $booking), ['reason' => 'طلب مراجعة الخدمة'])->assertRedirect();
        $this->post(route('instant.object', $booking), ['reason' => 'اعتراض مكرر'])->assertConflict();
        $this->actingAs($vendor)->post(route('instant.cases', $booking), ['case_type' => 'suspected_fraud', 'reason' => 'البلاغ الأول'])->assertRedirect();
        $this->post(route('instant.cases', $booking), ['case_type' => 'suspected_fraud', 'reason' => 'البلاغ الأول'])->assertConflict();
        $this->post(route('instant.cases', $booking), ['case_type' => 'suspected_fraud', 'reason' => 'دليل آخر'])->assertRedirect();
        $this->assertCount(3, $booking->cases);
        $this->assertSame('objection', $booking->refundRequest->case_type);
        $this->travel(24)->hours();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.instant.decide', $booking), ['decision' => 'rejected', 'reason' => 'تمت المراجعة'])->assertRedirect();
        foreach ($booking->pendingCases()->get() as $case) {
            app(InstantFinance::class)->run();
            $this->assertFalse($booking->walletTransactions()->where('transaction_type', 'payout')->exists());
            $this->post(route('admin.instant.operational-decision', $booking), [
                'case_id' => $case->id, 'decision' => 'rejected', 'reason' => 'لا يوجد دليل كافٍ',
            ])->assertRedirect();
        }
        app(InstantFinance::class)->run();
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'transaction_type' => 'payout', 'amount' => '85.000']);
        $this->delete(route('admin.users.destroy', $vendor))->assertRedirect();
        $this->assertDatabaseMissing('refund_requests', ['booking_id' => $booking->id]);
    }

    public function test_platform_commission_correction_is_bounded_append_only_and_idempotent(): void
    {
        [$customer, , $service] = $this->fixture();
        $booking = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'completed', 'agreed_price' => '100.000',
            'completed_at' => now()->subDays(2), 'payment_deadline' => now()->subDay(),
            'objection_deadline' => now()->subDay(), 'settlement_status' => 'on_hold',
        ]);
        $finance = app(InstantFinance::class);
        $finance->collect($booking, $customer->id);
        $finance->run();
        $original = \App\Models\WalletTransaction::whereNull('booking_id')->where('transaction_type', 'commission')->sole();
        $route = route('admin.instant.correct-commission', $original->id);
        $data = ['amount' => '5.000', 'reason' => 'تصحيح موثق', 'request_id' => (string) \Illuminate\Support\Str::uuid(), 'confirm' => '1'];
        $this->actingAs($customer)->post($route, $data)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->post($route, $data)->assertRedirect();
        $this->post($route, $data)->assertRedirect();
        $this->post($route, array_replace($data, ['amount' => '6.000']))->assertConflict();
        $this->post($route, array_replace($data, ['amount' => '10.001', 'request_id' => (string) \Illuminate\Support\Str::uuid()]))->assertConflict();
        $this->assertSame('15.000', $original->fresh()->amount);
        $this->assertSame(1, $original->corrections()->count());
        $this->assertSame(10000, $finance->availableFils());
    }

    public function test_scheduled_entitlement_recovers_vendor_debt_without_repricing_the_booking(): void
    {
        [$customer, $vendor, $service] = $this->fixture();
        $cancelled = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'confirmed', 'agreed_price' => '100.000',
        ]);
        $this->actingAs($vendor)->post(route('instant.transition', $cancelled), ['action' => 'cancel'])->assertRedirect();
        $scheduled = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'status' => 'completed', 'original_price' => '10.000',
            'completed_at' => now()->subDays(2), 'objection_deadline' => now()->subDay(),
        ]);
        \App\Models\Payment::factory()->for($scheduled)->create(['amount' => '10.000', 'status' => 'paid']);
        $this->artisan('wallet:release')->assertSuccessful();
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $scheduled->id, 'transaction_type' => 'credit', 'amount' => '0.000']);
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $cancelled->id, 'transaction_type' => 'compensation', 'amount' => '8.500']);
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $cancelled->id, 'transaction_type' => 'debt', 'status' => 'outstanding']);
        $this->assertSame('10.000', $scheduled->payment->amount);

        $instant = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'completed', 'agreed_price' => '10.000',
            'completed_at' => now()->subDays(2), 'objection_deadline' => now()->subDay(),
            'payment_deadline' => now()->subDay(), 'settlement_status' => 'on_hold',
        ]);
        $finance = app(InstantFinance::class);
        $finance->collect($instant, $customer->id);
        $finance->run();
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $instant->id, 'transaction_type' => 'payout', 'amount' => '2.000']);
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $cancelled->id, 'transaction_type' => 'debt', 'status' => 'repaid']);
        $this->assertEquals('15.000', $cancelled->walletTransactions()->where('transaction_type', 'compensation')->sum('amount'));
    }

    public function test_admin_can_close_a_reported_unpaid_non_execution_without_creating_debt(): void
    {
        [$customer, $vendor, $service] = $this->fixture();
        $booking = Booking::factory()->for($service)->create([
            'customer_id' => $customer->id, 'booking_type' => 'instant', 'status' => 'in_progress', 'agreed_price' => '100.000',
        ]);
        $this->actingAs($customer)->post(route('instant.cases', $booking), [
            'case_type' => 'non_execution', 'reason' => 'الخدمة لم تنفذ',
        ])->assertRedirect();
        $case = $booking->cases()->sole();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.instant.operational-decision', $booking), [
            'case_id' => $case->id, 'decision' => 'accepted', 'reason' => 'راجعت أدلة الطرفين ولم تنفذ الخدمة',
            'close_without_charge' => '1', 'responsibility' => 'vendor',
        ])->assertRedirect();
        $this->assertSame('cancelled', $booking->refresh()->status);
        $this->assertDatabaseHas('refund_requests', [
            'id' => $case->id, 'reviewed_by' => $admin->id, 'submitted_by' => $customer->id,
            'approved_service_amount' => '0.000', 'responsible_user_id' => $vendor->id,
        ]);
        $this->assertSame(0, $booking->walletTransactions()->count());
    }
}
