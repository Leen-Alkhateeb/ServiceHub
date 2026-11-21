<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PortfolioProject;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorService;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_dashboard_chart_counts_only_own_bookings_and_nav_has_one_dashboard_link(): void
    {
        $vendor = VendorProfile::factory()->create()->user;
        $offer = VendorService::factory()->for($vendor, 'vendor')->create();
        Booking::factory()->for($offer)->pending()->create();
        Booking::factory()->for($offer)->create();
        Booking::factory()->create();

        $response = $this->actingAs($vendor)->get(route('vendor.dashboard'))->assertOk()
            ->assertSee('توزيع حالات الحجوزات')
            ->assertSee('id="bookingsChart"', false)->assertSee('id="statusChart"', false)
            ->assertViewHas('bookingChart', fn ($chart) => count($chart['labels']) === 7 && array_sum($chart['values']) === 1)
            ->assertViewHas('bookingStatusCounts', fn ($counts) => $counts->count() === 2
                && (int) $counts['pending'] === 1 && (int) $counts['completed'] === 1);

        $navbar = str($response->getContent())->betweenFirst('<header class="topbar">', '</header>');
        $this->assertSame(1, substr_count((string) $navbar, 'href="'.route('vendor.dashboard').'"'));
    }

    public function test_visitors_switch_between_services_and_portfolio_without_seeing_private_tabs(): void
    {
        $vendor = VendorProfile::factory()->create()->user;
        $offer = VendorService::factory()->for($vendor, 'vendor')->create();
        $project = PortfolioProject::factory()->for($vendor, 'vendor')->create(['name' => 'Portfolio sample']);
        $public = ['vendor' => $vendor->id];
        $portfolio = ['tab' => 'portfolio'] + $public;

        $this->actingAs(User::factory()->create())->get(route('vendor.profile', $public))->assertOk()
            ->assertSee($offer->service->name)->assertDontSee($project->name)->assertSee(route('vendor.profile', $portfolio));
        $this->get(route('vendor.profile', $portfolio))->assertOk()
            ->assertSee($project->name)->assertDontSee($offer->service->name)->assertDontSee('مدفوعاتي');
        $this->get(route('vendor.profile', ['tab' => 'income'] + $public))->assertNotFound();
        $this->get(route('vendor.portfolio.show', $project))->assertOk()->assertSee(route('vendor.profile', $portfolio).'#portfolio');
    }

    public function test_demo_seed_has_at_least_fifteen_rows_per_business_table_and_can_be_rerun(): void
    {
        $tables = [
            'users', 'vendor_profiles', 'categories', 'services', 'vendor_services', 'recurring_availability',
            'portfolio_projects', 'portfolio_images', 'bookings', 'payments', 'wallet_transactions',
            'refund_requests', 'reviews', 'favorites', 'notifications', 'contact_messages',
        ];
        $this->seed();
        $counts = array_map(fn ($table) => DB::table($table)->count(), $tables);
        $this->seed();
        foreach ($tables as $index => $table) {
            $this->assertGreaterThanOrEqual(15, $counts[$index], $table);
            $this->assertDatabaseCount($table, $counts[$index]);
        }

        $vendor = User::whereEmail('plumbing.seed@servicehub.example')->firstOrFail();
        $offer = $vendor->vendorServices()->firstOrFail();
        $this->getJson(route('vendor.service.reviews', $offer))
            ->assertOk()->assertJsonCount(4, 'data')->assertJsonPath('next_page_url', null);

        $this->actingAs($vendor)->get(route('home'))->assertOk()->assertSee(route('account.profile'))
            ->assertSee(route('logout'))->assertDontSee(route('register'))
            ->assertSee('notification-dropdown')->assertSee('عرض المزيد')->assertSee('تم تحرير مبلغ الحجز');
        $this->get(route('account.profile'))->assertRedirect(route('vendor.profile'));
        $this->get(route('vendor.profile'))->assertOk()->assertSee(route('vendor.service.reviews', $offer))
            ->assertSeeInOrder([$vendor->first_name.' '.$vendor->last_name, $vendor->vendorProfile->work_type])
            ->assertSee('vendor-profile-layout')->assertSee(route('vendor.profile', ['tab' => 'portfolio']).'#portfolio');
        $this->get(route('vendor.wallet', ['status' => 'released', 'q' => $offer->service->name, 'to' => now()->toDateString()]))
            ->assertOk()->assertSee('40.00')->assertSee('حالة الدفع')
            ->assertSee('balance-cards')->assertSee('dialog-date-time')
            ->assertViewHas('bookings', fn ($bookings) => $bookings->total() === 1)
            ->assertDontSee('تاريخ الموافقة')->assertDontSee('انتهاء مهلة الدفع');
        $this->get(route('vendor.reviews'))->assertOk()->assertSee('تقييم العميل')->assertSee('rating-dialog')->assertSee('type="radio"', false);
    }

    public function test_vendor_can_replace_an_avatar_and_only_public_vendors_share_it(): void
    {
        $disk = Storage::fake('local');
        $profile = VendorProfile::factory()->create();
        $vendor = $profile->user;
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+Xk4QAAAAASUVORK5CYII=');
        $photo = fn () => ['profile_image' => UploadedFile::fake()->createWithContent('photo.png', $png)];
        $update = route('account.picture.update');
        $show = route('account.picture.show', $vendor);

        $this->post($update, $photo())->assertRedirect(route('login'));
        $this->actingAs($vendor)->get(route('vendor.profile'))->assertOk()->assertSee('تغيير الصورة الشخصية');
        $this->post($update, $photo())->assertRedirect(route('vendor.profile'));
        $old = $vendor->fresh()->profile_image;
        $disk->assertExists($old);
        $this->post($update, $photo())->assertRedirect(route('vendor.profile'));
        $new = $vendor->fresh()->profile_image;
        $disk->assertMissing($old);
        $disk->assertExists($new);
        $this->get(route('vendor.profile'))->assertOk()->assertSee($show);

        $other = User::factory()->create();
        $this->actingAs($other)->get($show)->assertOk()->assertHeader('Content-Type', 'image/png');
        $profile->update(['application_status' => 'pending']);
        $this->get($show)->assertNotFound();
        $this->actingAs($vendor->fresh())->get($show)->assertOk();
        $this->get(route('vendor.profile'))->assertOk()->assertSee('حذف الصورة');
        $this->delete(route('account.picture.destroy'))->assertRedirect(route('vendor.profile'))
            ->assertSessionHas('picture_status', 'تم حذف الصورة الشخصية.');
        $disk->assertMissing($new);
        $this->assertNull($vendor->fresh()->profile_image);
        $this->actingAs($vendor->fresh())->get(route('vendor.profile'))->assertOk()->assertDontSee($show)
            ->assertSee(mb_substr($vendor->first_name, 0, 1).'&nbsp;'.mb_substr($vendor->last_name, 0, 1), false);
    }

    public function test_landing_previews_link_to_directories_and_category_details(): void
    {
        $this->seed();
        $this->get(route('home'))->assertOk()
            ->assertViewHas('categories', fn ($categories) => $categories->count() === 5)
            ->assertViewHas('vendors', fn ($vendors) => $vendors->count() === 3)
            ->assertSee('عرض المزيد')->assertSee('الأسئلة الأكثر تكرارًا');
        $this->get(route('categories.index'))->assertOk()->assertSee('صفحة 2');
        $this->get(route('vendors.index'))->assertOk()->assertSee('صفحة 2');
        $service = Service::where('approval_status', 'approved')->firstOrFail();
        $market = route('market.index', ['category' => $service->category_id]);
        $this->get(route('categories.show', $service->category))->assertRedirect($market);
        $this->get($market)->assertOk()->assertSee($service->name);
        $customer = User::factory()->create();
        $this->actingAs($customer)->get(route('account.profile'))->assertOk()->assertSee($customer->email)->assertSee('&nbsp;', false);
    }

    public function test_wallet_releases_only_after_the_hold_without_an_open_complaint_and_notifies(): void
    {
        $this->travelTo(now()->startOfMinute());
        $ready = Booking::factory()->paid()->create(['completed_at' => now()->subHours(24)]);
        $held = Booking::factory()->paid()->create(['completed_at' => now()->subHours(23)]);
        $disputed = Booking::factory()->paid()->create(['completed_at' => now()->subHours(25)]);
        RefundRequest::factory()->for($disputed)->create();
        $vendor = $ready->vendorService->vendor;

        $this->get(route('vendor.wallet'))->assertRedirect(route('login'));
        $this->actingAs($ready->customer)->get(route('vendor.wallet'))->assertForbidden();
        $this->actingAs($vendor)->get(route('vendor.wallet'))->assertOk()->assertSee('جاهز للتحرير');
        $this->artisan('wallet:release')->expectsOutput('1 bookings credited.')->assertSuccessful();
        $this->artisan('wallet:release')->expectsOutput('0 bookings credited.')->assertSuccessful();
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $ready->id, 'amount' => $ready->payment->amount]);
        $this->assertDatabaseMissing('wallet_transactions', ['booking_id' => $held->id]);
        $this->assertDatabaseMissing('wallet_transactions', ['booking_id' => $disputed->id]);
        $notification = $vendor->notifications()->latest('id')->firstOrFail();
        $this->get(route('notifications.index'))->assertOk()->assertSee($notification->title);
        $this->post(route('notifications.read', $notification))->assertRedirect();
        $this->assertTrue((bool) $notification->fresh()->is_read);

        $payment = Payment::where('booking_id', $held->id)->firstOrFail();
        $before = Notification::where('user_id', $held->vendorService->vendor_id)->count();
        $payment->update(['status' => 'failed']);
        $this->assertSame($before + 1, Notification::where('user_id', $held->vendorService->vendor_id)->count());
    }

    public function test_only_the_assigned_vendor_can_rate_the_customer_once_after_completion(): void
    {
        $booking = Booking::factory()->create();
        $pending = Booking::factory()->pending()->for($booking->vendorService)->create();
        $vendor = $booking->vendorService->vendor;
        $action = route('vendor.reviews.store', $booking);
        $this->actingAs(User::factory()->vendor()->create())->post($action, ['rating' => 5])->assertNotFound();
        $this->actingAs($vendor)->post(route('vendor.reviews.store', $pending), ['rating' => 5])->assertForbidden();
        $this->post($action, ['rating' => 4, 'comment' => 'Appointment went well.'])->assertRedirect(route('vendor.reviews'));
        $this->post($action, ['rating' => 1])->assertRedirect(route('vendor.reviews'));
        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', [
            'booking_id' => $booking->id, 'reviewer_id' => $vendor->id,
            'reviewed_user_id' => $booking->customer_id, 'rating' => 4,
        ]);
        $this->assertSame(0, Review::where('reviewed_user_id', $vendor->id)->count());
    }

    public function test_vendor_can_publish_a_project_with_multiple_images(): void
    {
        $disk = Storage::fake('public');
        $this->seed();
        $vendor = User::whereEmail('plumbing.seed@servicehub.example')->firstOrFail();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+Xk4QAAAAASUVORK5CYII=');
        $this->actingAs($vendor)->post(route('vendor.portfolio.store'), [
            'name' => 'Newest portfolio project',
            'description' => 'Repaired a damaged pipe.',
            'project_price' => '87.50',
            'completion_date' => now()->toDateString(),
            'images' => [
                UploadedFile::fake()->createWithContent('before.png', $png),
                UploadedFile::fake()->createWithContent('after.png', $png),
            ],
        ])->assertRedirect(route('vendor.profile', ['tab' => 'portfolio']).'#portfolio');

        $project = PortfolioProject::latest('id')->firstOrFail();
        $this->assertSame($vendor->id, $project->vendor_id);
        $this->assertCount(2, $project->images);
        $this->get(route('vendor.profile', ['tab' => 'portfolio']))->assertSeeInOrder(['Newest portfolio project', 'مشروع كشف وإصلاح تسريبات المياه'])
            ->assertSee('عرض التفاصيل')->assertSee(route('vendor.portfolio.show', $project));
        $this->get(route('vendor.portfolio.show', $project))->assertOk()->assertSee($project->description)
            ->assertSee('87.50')->assertSee('project-thumbnails');
        foreach ($project->images as $image) {
            $disk->assertExists($image->image);
            $this->get(route('vendor.portfolio.image', $image))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }

    public function test_vendor_can_replace_a_project_image_at_the_eight_image_limit(): void
    {
        Storage::fake('public');
        $vendor = VendorProfile::factory()->create()->user;
        $project = PortfolioProject::factory()->for($vendor, 'vendor')->create();
        $other = PortfolioProject::factory()->for($vendor, 'vendor')->create();
        $existing = $project->images()->createMany(array_map(fn ($i) => ['image' => "portfolio/photo-$i.png"], range(1, 8)));
        $unrelated = $other->images()->create(['image' => 'portfolio/unrelated.png']);
        $url = route('vendor.portfolio.update', $project);
        $data = ['name' => $project->name, 'description' => $project->description];
        $image = fn () => UploadedFile::fake()->createWithContent('new.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+Xk4QAAAAASUVORK5CYII='));

        $this->actingAs($vendor)->get(route('vendor.portfolio.show', $project))->assertOk()->assertSee('إضافة حقل صورة');
        $this->patch($url, $data + ['images' => [$image()]])->assertSessionHasErrorsIn('portfolioEdit', 'images');
        $this->patch($url, $data + ['remove_images' => [$unrelated->id]])->assertSessionHasErrorsIn('portfolioEdit', 'remove_images');
        $this->patch($url, $data + ['remove_images' => [$existing->first()->id], 'images' => [$image()]])
            ->assertRedirect(route('vendor.portfolio.show', $project));
        $this->assertDatabaseHas('portfolio_images', ['id' => $existing->first()->id, 'is_active' => false]);
        $this->assertDatabaseHas('portfolio_images', ['id' => $unrelated->id, 'is_active' => true]);
        $this->assertSame(8, $project->images()->where('is_active', true)->count());
        Storage::disk('public')->assertExists($project->images()->latest('id')->firstOrFail()->image);
    }

    public function test_catalog_offer_publishes_directly_and_uses_only_its_linked_image(): void
    {
        $disk = Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+Xk4QAAAAASUVORK5CYII=');
        $disk->put('services/inspection.png', $png);
        $vendor = VendorProfile::factory()->create()->user;
        $withImage = Service::factory()->create(['image' => 'services/inspection.png']);
        $withoutImage = Service::factory()->create();
        $data = ['price' => '54.25', 'description' => 'Inspection and report.', 'duration_minutes' => 60];

        $this->actingAs($vendor)->get(route('vendor.profile'))->assertOk()->assertSee('إضافة خدمة')->assertSee('اقتراح خدمة');
        $this->post(route('vendor.service-requests.store'), $data + ['service_id' => $withImage->id])
            ->assertRedirect(route('vendor.profile').'#services');
        $this->post(route('vendor.service-requests.store'), $data + ['service_id' => $withoutImage->id])->assertRedirect();
        $offer = $vendor->vendorServices()->where('service_id', $withImage->id)->firstOrFail();
        $other = $vendor->vendorServices()->where('service_id', $withoutImage->id)->firstOrFail();
        $this->assertSame('active', $offer->status);
        $this->assertSame($withImage->image, $offer->image);
        $this->assertSame('', $other->image);
        $this->get(route('vendor.profile'))->assertOk()->assertSee(route('vendor.service.image', $offer))
            ->assertDontSee(route('vendor.service.image', $other));
        $this->get(route('vendor.service.image', $offer))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->post(route('vendor.service-requests.store'), $data + ['service_id' => $withImage->id])
            ->assertSessionHasErrorsIn('offer', 'service_id');
    }

    public function test_new_service_suggestion_requires_admin_decision_before_publication(): void
    {
        $profile = VendorProfile::factory()->create();
        $vendor = $profile->user;
        $existing = Service::factory()->create();
        $data = [
            'category_id' => $existing->category_id, 'name' => 'Thermal scan', 'price' => '54.25', 'description' => 'Inspection and report.',
            'duration_minutes' => 60, 'status' => 'active',
        ];
        $publicUrl = route('vendor.profile', ['vendor' => $vendor->id]);
        $this->actingAs($vendor)->post(route('vendor.service-suggestions.store'), $data)
            ->assertRedirect(route('vendor.profile').'#services');
        $offer = $vendor->vendorServices()->firstOrFail();
        $this->assertSame('pending', $offer->status);
        $this->assertSame('pending', $offer->service->approval_status);
        $this->assertSame($existing->category_id, $offer->service->category_id);
        $this->get($publicUrl)->assertOk()->assertSee('قيد المراجعة');
        $this->actingAs(User::factory()->create())->get($publicUrl)->assertOk()->assertDontSee('Inspection and report.');
        $this->actingAs($vendor);
        $decision = route('admin.service-requests.decide', $offer);
        $this->post($decision, ['decision' => 'active'])->assertForbidden();
        $this->post(route('vendor.service-suggestions.store'), $data)->assertSessionHasErrorsIn('suggestion', 'name');

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.service-requests.index'))->assertOk()->assertSee('Inspection and report.');
        $this->post($decision, ['decision' => 'rejected', 'rejection_reason' => 'Please clarify the coverage.'])
            ->assertRedirect(route('admin.service-requests.index'));
        $this->assertSame('rejected', $offer->fresh()->status);
        $this->assertSame('rejected', $offer->service->fresh()->approval_status);
        $this->assertDatabaseHas('notifications', ['user_id' => $vendor->id, 'type' => 'service']);

        $this->actingAs($vendor)->post(route('vendor.service-suggestions.store'), $data)->assertRedirect();
        $newOffer = $vendor->vendorServices()->latest('id')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.service-requests.decide', $newOffer), ['decision' => 'active'])->assertRedirect();
        $this->assertSame('approved', $newOffer->service->fresh()->approval_status);
        $this->get($publicUrl)->assertOk()->assertSee('Inspection and report.')->assertSee('54.25');
    }

    public function test_vendor_can_edit_archive_and_rerate_the_same_booking(): void
    {
        $booking = Booking::factory()->create();
        $vendor = $booking->vendorService->vendor;
        $this->actingAs($vendor)->post(route('vendor.reviews.store', $booking), ['rating' => 2])->assertRedirect();
        $this->get(route('vendor.reviews'))->assertOk()->assertSee('تعديل التقييم')->assertSee('تأكيد الحذف');
        $update = route('vendor.reviews.update', $booking);
        $this->actingAs(User::factory()->vendor()->create())->patch($update, ['rating' => 5])->assertNotFound();
        $this->actingAs($vendor)->patch($update, ['rating' => 4, 'comment' => 'Corrected'])->assertRedirect();
        $this->assertDatabaseHas('reviews', ['booking_id' => $booking->id, 'rating' => 4, 'comment' => 'Corrected', 'is_active' => true]);
        $this->delete(route('vendor.reviews.destroy', $booking))->assertRedirect();
        $this->assertDatabaseHas('reviews', ['booking_id' => $booking->id, 'is_active' => false]);
        $this->get(route('vendor.reviews'))->assertOk()->assertDontSee('Corrected');
        $this->post(route('vendor.reviews.store', $booking), ['rating' => 5])->assertRedirect();
        $this->assertDatabaseHas('reviews', ['booking_id' => $booking->id, 'rating' => 5, 'is_active' => true]);
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_vendor_can_update_details_and_archive_owned_offers_and_portfolio_items(): void
    {
        $profile = VendorProfile::factory()->create();
        $vendor = $profile->user;
        $project = PortfolioProject::factory()->for($vendor, 'vendor')->create();
        $photo = $project->images()->create(['image' => 'demo/portfolio/plumbing.jpg']);
        $offer = VendorService::factory()->for($vendor, 'vendor')->create();
        $this->actingAs($vendor)->patch(route('account.details.update'), [
            'first_name' => 'Updated', 'last_name' => 'Vendor', 'phone' => '0700000000', 'location' => 'Amman',
            'work_type' => 'Plumbing', 'bio' => 'Updated profile', 'experience_years' => 7,
        ])->assertRedirect(route('vendor.profile'));
        $this->assertSame('Updated', $vendor->fresh()->first_name);
        $this->assertEquals(7, $profile->fresh()->experience_years);
        $this->patch(route('vendor.service-requests.update', $offer), ['price' => '53.70', 'description' => 'Updated offer'])->assertRedirect();
        $this->assertEquals('53.70', $offer->fresh()->price);
        $this->delete(route('vendor.service-requests.destroy', $offer))->assertRedirect();
        $this->assertSame('inactive', $offer->fresh()->status);
        $this->get(route('vendor.service.reviews', $offer))->assertNotFound();

        $this->patch(route('vendor.portfolio.update', $project), ['name' => 'Updated project', 'description' => 'Updated work'])->assertRedirect();
        $this->get(route('vendor.portfolio.show', $project))->assertOk()->assertSee('Updated project')->assertSee('plumbing.jpg');
        $this->delete(route('vendor.portfolio.image.destroy', $photo))->assertRedirect();
        $this->assertDatabaseHas('portfolio_images', ['id' => $photo->id, 'is_active' => false]);
        $this->get(route('vendor.portfolio.show', $project))->assertOk()->assertDontSee('plumbing.jpg');
        $this->delete(route('vendor.portfolio.destroy', $project))->assertRedirect();
        $this->assertDatabaseHas('portfolio_projects', ['id' => $project->id, 'is_active' => false]);
        $this->get(route('vendor.portfolio.show', $project))->assertNotFound();
    }

    public function test_assistant_handles_missing_configuration_and_accepts_a_provider_response(): void
    {
        $plumbing = Category::factory()->create(['name' => 'السباكة']);
        config(['services.gemini.key' => 'fake-key', 'services.gemini.model' => 'test-model']);
        Http::fake(['https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode(['category_id' => $plumbing->id])]]]]],
        ])]);
        $url = route('categories.show', $plumbing);
        $this->postJson(route('maintenance.assistant'), ['problem' => 'الحنفية بتسرّب مي'])
            ->assertOk()->assertJsonPath('category_url', $url);
        $this->postJson(route('maintenance.assistant'), ['problem' => 'اكتب كود PHP لتصليح المشروع'])
            ->assertOk()->assertJsonMissingPath('category_url');
        Http::assertNothingSent();
        $this->postJson(route('maintenance.assistant'), ['problem' => 'في رطوبة داخل الجدار تحت المغسلة'])
            ->assertOk()->assertJsonPath('category_url', $url);
        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'fake-key'));
    }

    public function test_vendor_registration_creates_a_pending_profile_and_stores_the_permit_privately(): void
    {
        $disk = Storage::fake('local');
        $this->get(route('register'))->assertOk()->assertSee('بيانات المهنة')->assertSee('account_type');
        $data = [
            'account_type' => 'vendor', 'first_name' => 'New', 'last_name' => 'Technician',
            'email' => 'new-technician@example.test', 'phone' => '0700000000', 'location' => 'Amman',
            'gender' => 'male', 'password' => 'servicehub-test-password', 'password_confirmation' => 'servicehub-test-password',
            'work_type' => 'Plumbing', 'experience_years' => 5, 'bio' => 'Home plumbing and inspection.',
            'permit_expiry_date' => now()->addYear()->format('Y-m-d'),
        ];
        $this->post(route('register'), $data)->assertSessionHasErrors('work_permit');
        $this->assertDatabaseMissing('users', ['email' => $data['email']]);
        $this->post(route('register'), $data + [
            'work_permit' => UploadedFile::fake()->create('permit.pdf', 10, 'application/pdf'),
        ])->assertRedirect(route('vendor.dashboard'));
        $vendor = User::whereEmail($data['email'])->firstOrFail();
        $this->assertAuthenticatedAs($vendor);
        $this->assertSame('vendor', $vendor->role);
        $this->assertSame('pending', $vendor->vendorProfile->application_status);
        $disk->assertExists($vendor->vendorProfile->work_permit_path);
        $this->get(route('vendor.dashboard'))->assertOk()->assertSee('حالة طلب الانضمام');
    }

    public function test_booking_is_restricted_to_customers_and_each_profile_shows_its_own_history(): void
    {
        $seller = VendorProfile::factory()->create()->user;
        $buyer = VendorProfile::factory()->create()->user;
        $offer = VendorService::factory()->for($seller, 'vendor')->create();
        $tomorrow = now()->addDay()->startOfDay();
        $window = $seller->recurringAvailabilities()->create([
            'day_of_week' => $tomorrow->dayOfWeek, 'start_time' => '09:00:00', 'end_time' => '12:00:00',
        ]);
        $slot = fn (string $hour) => ['slot' => $tomorrow->format('Y-m-d').'|'.$hour.'|'.$window->id];
        $store = route('booking.store', $offer);
        $this->actingAs($seller)->post($store, $slot('09:00'))->assertForbidden();
        $this->actingAs($buyer)->get(route('market.show', $offer))->assertOk()->assertDontSee('احجز الآن');
        $this->post($store, $slot('09:00'))->assertForbidden();
        $ownBooking = Booking::factory()->for($offer)->create([
            'customer_id' => $buyer->id,
            'booking_date' => $tomorrow->toDateString(),
        ]);
        Payment::factory()->for($ownBooking)->create(['amount' => 77.55, 'paid_at' => now()->subDay()]);
        $this->get(route('vendor.profile', ['tab' => 'payments']))->assertOk()
            ->assertSee('77.55')->assertDontSee('طلب إضافة خدمة');
        $this->get(route('vendor.profile', ['tab' => 'bookings']))->assertOk()->assertSee($ownBooking->service_name);
        $this->get(route('vendor.profile', [
            'tab' => 'bookings', 'q' => $ownBooking->service_name, 'category' => $offer->service->category_id,
            'from' => $tomorrow->toDateString(), 'to' => $tomorrow->toDateString(),
        ]))->assertOk()->assertSee('تفاصيل الطلب')->assertSee('data-open-dialog="history-'.$ownBooking->id.'"', false);
        $this->get(route('vendor.profile', [
            'tab' => 'payments', 'q' => $seller->first_name.' '.$seller->last_name,
            'from' => now()->subDay()->toDateString(), 'to' => now()->subDay()->toDateString(),
        ]))->assertOk()->assertSee('تفاصيل الدفعة')->assertSee('77.55')->assertDontSee('الفاتورة');
        $this->get(route('vendor.profile', ['tab' => 'payments', 'from' => $tomorrow->toDateString()]))
            ->assertOk()->assertDontSee('77.55');
        $incoming = Booking::factory()->for(VendorService::factory()->for($buyer, 'vendor'))->create();
        WalletTransaction::factory()->for($incoming)->create(['amount' => 46.25]);
        $this->get(route('vendor.profile', ['tab' => 'income']))->assertOk()->assertSee('46.25')->assertDontSee('77.55');
        $this->get(route('home'))->assertSee(route('vendor.dashboard'))->assertSee(route('account.profile'));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post($store, $slot('10:00'))->assertForbidden();
        $this->get(route('account.profile', ['tab' => 'payments']))->assertOk()->assertDontSee('77.55');
        $this->get(route('home'))->assertSee(route('admin.dashboard'));
        $this->actingAs(User::factory()->create())->get(route('home'))->assertDontSee('لوحة التحكم');
    }
}
