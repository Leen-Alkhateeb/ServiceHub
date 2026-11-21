<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorService;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteAuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_replace_and_view_a_service_image(): void
    {
        $disk = Storage::fake('public');
        $category = \App\Models\Category::factory()->create(['is_deleted' => false]);
        $adminPage = route('admin.admin-services');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+Xk4QAAAAASUVORK5CYII=');
        $file = fn () => UploadedFile::fake()->createWithContent('service.png', $png);
        $fields = ['name' => 'Service image upload', 'description' => 'Image upload check', 'category_id' => $category->id];

        $this->actingAs(User::factory()->admin()->create())->from($adminPage)
            ->post(route('admin.addServices'), $fields + ['image' => $file()])
            ->assertRedirect($adminPage)->assertSessionHasNoErrors();
        $service = Service::where('name', $fields['name'])->firstOrFail();
        $original = $service->image;
        $imageUrl = route('admin.services.image', $service);
        $disk->assertExists($original);
        $this->get($adminPage)->assertOk()->assertSee($imageUrl)->assertSee('multipart/form-data')
            ->assertSee('تمت إضافة الخدمة بنجاح');
        $response = $this->get($imageUrl)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertSame($png, $response->streamedContent());

        $edit = route('admin.editServices', $service);
        $this->from($adminPage)->post($edit, $fields + ['_method' => 'PUT', 'image' => $file()])
            ->assertRedirect($adminPage)->assertSessionHasNoErrors();
        $disk->assertMissing($original);
        $replacement = $service->refresh()->image;
        $disk->assertExists($replacement);
        $this->get($adminPage)->assertOk()->assertSee('تم تعديل الخدمة بنجاح');
        $this->get($imageUrl)->assertOk();
        $this->from($adminPage)->post($edit, $fields + ['_method' => 'PUT'])
            ->assertRedirect($adminPage)->assertSessionHasNoErrors();
        $this->assertSame($replacement, $service->refresh()->image);
        $this->actingAs(User::factory()->create())->get($imageUrl)->assertForbidden();
    }

    public function test_admin_catalog_displays_messages_after_toggling_and_deleting_a_service(): void
    {
        $service = Service::factory()->create(['is_active' => true]);
        $page = route('admin.admin-services');
        $toggle = route('admin.toggleServices', $service);
        $this->actingAs(User::factory()->admin()->create());

        foreach (['تم تعطيل الخدمة بنجاح', 'تم تفعيل الخدمة بنجاح'] as $message) {
            $this->from($page)->post($toggle, ['_method' => 'PUT'])->assertRedirect($page);
            $this->get($page)->assertOk()->assertSee($message);
        }

        $this->from($page)->post(route('admin.deleteServices', $service), ['_method' => 'DELETE'])->assertRedirect($page);
        $this->get($page)->assertOk()->assertSee('تم حذف الخدمة بنجاح');
    }

    public function test_admin_overview_links_open_their_destination_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Booking::factory()->create();
        Booking::factory()->pending()->create();
        $overview = $this->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['bookings'] === 2)
            ->assertDontSee('href="#"', false);

        $destinations = [
            route('admin.payments.index'),
            route('admin.admin.Users'),
            route('admin.admin.Users', ['role' => 'vendor']),
            route('admin.bookings.index'),
            route('admin.admin-applications'),
            route('admin.refunds.index', ['status' => 'pending']),
            route('admin.refunds.index'),
            route('admin.reviews.index'),
        ];

        foreach ($destinations as $destination) {
            $overview->assertSee('href="'.$destination.'"', false);
            $this->get($destination)->assertOk();
        }
    }

    public function test_admin_application_list_links_only_to_available_permits(): void
    {
        $disk = Storage::fake('local');
        $missing = VendorProfile::factory()->create();
        $available = VendorProfile::factory()->create(['work_permit_path' => 'vendor-permits/test.pdf']);
        $disk->put($available->work_permit_path, 'Test permit');

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.admin-applications'))
            ->assertOk()
            ->assertSee('غير متوفر')
            ->assertDontSee(route('admin.applications.permit', $missing))
            ->assertSee(route('admin.applications.permit', $available));
        $this->get(route('admin.applications.permit', $available))->assertDownload('test.pdf');
    }

    public function test_vendor_availability_displays_saturday_first(): void
    {
        $vendor = VendorProfile::factory()->create()->user;
        foreach ([0, 6, 3] as $day) {
            $vendor->recurringAvailabilities()->create([
                'day_of_week' => $day,
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
            ]);
        }

        $this->actingAs($vendor)->get(route('vendor.availability.index'))->assertOk()
            ->assertViewHas('availabilities', fn ($slots) => $slots->pluck('day_of_week')->map(fn ($day) => (int) $day)->all() === [6, 0, 3]);
    }

    public function test_admin_can_search_full_names_and_invalid_filters_return_validation_errors(): void
    {
        User::factory()->create(['first_name' => 'Iman', 'last_name' => 'Salem']);
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.admin.Users', ['search' => 'Iman Salem']))
            ->assertOk()->assertViewHas('users', fn ($users) => $users->count() === 1 && $users->first()->first_name === 'Iman');

        $filters = [
            'market.index' => 'q',
            'admin.admin.Users' => 'search',
            'admin.admin-services' => 'category_id',
            'admin.service-requests.index' => 'search',
            'admin.categories' => 'q',
        ];

        foreach ($filters as $route => $field) {
            $this->getJson(route($route, [$field => ['invalid']]))->assertUnprocessable()->assertJsonValidationErrors($field);
        }
    }

    public function test_customer_can_book_and_pay_and_other_customers_cannot_access_the_checkout(): void
    {
        $vendor = VendorProfile::factory()->create()->user;
        $offer = VendorService::factory()->for($vendor, 'vendor')->create(['price' => 60, 'duration_minutes' => 60]);
        $customer = User::factory()->create();
        $tomorrow = now()->addDay();
        $window = $vendor->recurringAvailabilities()->create([
            'day_of_week' => $tomorrow->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);
        $slot = $tomorrow->toDateString().'|09:00|'.$window->id;

        $servicePage = route('market.show', $offer);
        $slotValue = 'value="'.$slot.'"';
        $this->actingAs($customer)->get($servicePage)->assertOk()->assertSee($slotValue, false);
        $this->post(route('booking.store', $offer), ['slot' => $slot])
            ->assertRedirect(route('customer.bookings'));
        $rejectedBooking = $customer->bookingsAsCustomer()->firstOrFail();
        $decision = 'vendor.vendor-bookings.update';
        $this->assertSame('pending', $rejectedBooking->status);
        $this->assertNull($rejectedBooking->payment_deadline);
        $this->get(route('payment.checkout', $rejectedBooking))->assertForbidden();
        $this->post(route('payment.store', $rejectedBooking))->assertForbidden();
        $this->actingAs($vendor)->get(route('vendor.vendor-bookings.index'))->assertOk()
            ->assertSee(route($decision, $rejectedBooking))->assertSee('قبول')->assertSee('رفض');
        $this->patch(route($decision, $rejectedBooking), ['status' => 'rejected'])->assertRedirect();
        $this->assertSame('rejected', $rejectedBooking->refresh()->status);
        $this->actingAs($customer)->get(route('payment.checkout', $rejectedBooking))->assertForbidden();

        $this->post(route('booking.store', $offer), ['slot' => $slot])
            ->assertRedirect(route('customer.bookings'));
        $booking = $customer->bookingsAsCustomer()->where('status', 'pending')->firstOrFail();
        $checkout = route('payment.checkout', $booking);
        $this->get(route('customer.bookings'))->assertOk()->assertSee('بانتظار موافقة الفني')->assertDontSee($checkout);
        $this->actingAs($vendor)->patch(route($decision, $booking), ['status' => 'approved'])->assertRedirect();
        $this->assertSame('approved', $booking->refresh()->status);
        $this->assertNotNull($booking->payment_deadline);
        $this->actingAs($customer);
        $this->get(route('customer.bookings'))->assertOk()->assertSee($checkout);
        $this->get($checkout)->assertOk()->assertSee('الدفع تجريبي');

        $this->actingAs(User::factory()->create())->get($servicePage)->assertOk()->assertDontSee($slotValue, false);
        $this->get($checkout)->assertForbidden();
        $this->from(route('market.show', $offer))->post(route('booking.store', $offer), ['slot' => $slot])
            ->assertSessionHasErrors('slot');

        $payment = ['payment_method' => 'card', 'card_name' => 'Demo', 'card_number' => '4242 4242 4242 4242'];
        $this->actingAs($customer)->post(route('payment.store', $booking), $payment)
            ->assertRedirect(route('payment.invoice', $booking));
        $this->get(route('payment.invoice', $booking))->assertOk()->assertSee('مدفوعة بالكامل')->assertSee('60.00');
        $this->actingAs(User::factory()->create())->post(route('booking.cancel', $booking))->assertForbidden();
        $this->assertDatabaseHas('payments', ['booking_id' => $booking->id, 'amount' => 60, 'status' => 'paid']);
        $this->assertSame('confirmed', $booking->refresh()->status);

        $completion = route('booking.completion', $booking);
        $customerReview = route('booking.review', $booking);
        $vendorReview = route('vendor.reviews.store', $booking);
        $this->actingAs(User::factory()->create())->post($completion)->assertForbidden();
        $this->actingAs($customer)->get(route('customer.bookings'))->assertSee($completion)->assertDontSee($customerReview);
        $this->post($completion)->assertRedirect();
        $this->post($completion)->assertRedirect();
        $this->post($customerReview, ['rating' => 5])->assertForbidden();
        $this->assertNull($booking->refresh()->completed_at);
        $this->actingAs($vendor)->post($vendorReview, ['rating' => 5])->assertForbidden();
        $this->get(route('vendor.vendor-bookings.index'))->assertSee($completion);
        $this->post($completion)->assertRedirect();
        $this->assertSame('completed', $booking->refresh()->status);
        $this->assertEquals($booking->completed_at->copy()->addHours(24), $booking->objection_deadline);
        $this->post($vendorReview, ['rating' => 5])->assertRedirect();
        $this->actingAs($customer)->get(route('account.profile', ['tab' => 'payments']))
            ->assertOk()
            ->assertSee('data-open-dialog="booking-rating-'.$booking->id.'"', false)
            ->assertSee('data-open-dialog="booking-objection-'.$booking->id.'"', false)
            ->assertSee('id="booking-rating-'.$booking->id.'"', false)
            ->assertSee('id="booking-objection-'.$booking->id.'"', false);
        $this->post($customerReview, ['rating' => 4])->assertRedirect();
        $this->assertSame(2, $booking->reviews()->count());
        $this->artisan('wallet:release')->assertSuccessful();
        $this->assertSame(0, $booking->walletTransactions()->count());

        $disputed = Booking::factory()->for($offer)->create([
            'customer_id' => $customer->id,
            'status' => 'completed',
            'completed_at' => now(),
            'objection_deadline' => now()->addHours(24),
        ]);
        \App\Models\Payment::factory()->for($disputed)->create(['status' => 'paid']);
        $this->post(route('booking.objection', $disputed), ['reason' => 'الخدمة بحاجة إلى متابعة'])->assertRedirect();
        $this->travel(25)->hours();
        $this->post(route('booking.objection', $booking), ['reason' => 'مهلة منتهية'])->assertForbidden();
        $this->artisan('wallet:release')->assertSuccessful();
        $this->artisan('wallet:release')->assertSuccessful();
        $this->assertSame(0, $disputed->walletTransactions()->count());
        $this->assertSame(1, $booking->walletTransactions()->count());
        $this->assertDatabaseHas('wallet_transactions', ['booking_id' => $booking->id, 'amount' => 60, 'transaction_type' => 'credit']);
    }

    public function test_booking_cancellations_record_refund_and_wallet_adjustments(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-22 10:00:00'));
        $vendor = VendorProfile::factory()->create()->user;
        $offer = VendorService::factory()->for($vendor, 'vendor')->create();
        $customer = User::factory()->create();
        $newBooking = function (string $start, int $minutesSincePayment) use ($offer, $customer) {
            $booking = Booking::factory()->for($offer)->create([
                'customer_id' => $customer->id, 'status' => 'confirmed', 'original_price' => 100,
                'booking_date' => now()->toDateString(), 'start_time' => $start, 'completed_at' => null,
            ]);
            \App\Models\Payment::factory()->for($booking)->create([
                'status' => 'paid', 'amount' => 100, 'paid_at' => now()->subMinutes($minutesSincePayment),
            ]);

            return $booking;
        };

        $customerCancellation = $newBooking('16:00:00', 120);
        $vendorCancellation = $newBooking('15:30:00', 60);
        $expiredCancellation = $newBooking('10:45:00', 121);
        $this->actingAs($vendor)->get(route('vendor.vendor-bookings.index'))->assertOk()->assertSee(route('booking.cancel', $vendorCancellation));
        $this->post(route('booking.cancel', $vendorCancellation))->assertRedirect();
        $wallet = route('vendor.wallet', ['q' => 'no matching booking']);
        $this->get($wallet)->assertOk()->assertViewHas('outstandingPenalty', 15.0);

        foreach ([10 => 5.0, 8 => 0] as $paid => $remainingPenalty) {
            $finished = Booking::factory()->for($offer)->create([
                'status' => 'completed',
                'completed_at' => now()->subHours(25),
                'objection_deadline' => now()->subHour(),
            ]);
            \App\Models\Payment::factory()->for($finished)->create(['status' => 'paid', 'amount' => $paid]);
            $this->artisan('wallet:release')->assertSuccessful();
            $this->get($wallet)->assertOk()->assertViewHas('outstandingPenalty', $remainingPenalty)
                ->assertViewHas('summary', fn ($summary) => (float) $summary['released']['amount'] === ($paid === 8 ? 3.0 : 0.0));
        }

        $this->actingAs($customer)->get(route('customer.bookings'))->assertOk()->assertSee(route('booking.cancel', $customerCancellation));
        $this->get(route('account.profile', ['tab' => 'bookings']))->assertOk()
            ->assertSee('data-open-dialog="cancel-booking-'.$customerCancellation->id.'"', false)
            ->assertSee('تأكيد إلغاء الحجز')->assertSee('ستُخصم غرامة 30٪');
        $this->post(route('booking.cancel', $customerCancellation))->assertRedirect();
        $this->post(route('booking.cancel', $customerCancellation))->assertForbidden();
        $this->get(route('customer.bookings'))->assertOk()->assertDontSee(route('booking.cancel', $expiredCancellation));
        $this->post(route('booking.cancel', $expiredCancellation))->assertForbidden();

        $this->assertDatabaseHas('bookings', [
            'id' => $customerCancellation->id, 'status' => 'cancelled', 'cancelled_by' => $customer->id,
            'cancellation_penalty' => 30, 'cancellation_refund_amount' => 70, 'cancellation_platform_share' => 15,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'booking_id' => $customerCancellation->id, 'vendor_id' => $vendor->id, 'transaction_type' => 'refund_share', 'amount' => 15,
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $vendorCancellation->id, 'cancelled_by' => $vendor->id,
            'cancellation_penalty' => 15, 'cancellation_refund_amount' => 100, 'cancellation_platform_share' => 0,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'booking_id' => $vendorCancellation->id, 'vendor_id' => $vendor->id, 'transaction_type' => 'penalty', 'amount' => -15,
        ]);
        $this->assertSame('confirmed', $expiredCancellation->refresh()->status);
        $this->assertSame('refunded', $vendorCancellation->payment()->firstOrFail()->status);
        $this->assertSame(1, $customerCancellation->walletTransactions()->count());
        $this->actingAs($vendor)->get(route('vendor.wallet'))->assertOk()->assertSee('تسويات الإلغاء')->assertSee('-15.00');
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.cancellations.index'))
            ->assertOk()->assertSee('استرداد العميل')->assertSee('حصة المنصة')->assertSee('70.00')->assertSee('-15.00');
    }

    public function test_admin_wallet_displays_archived_booking_transactions_and_rejects_other_roles(): void
    {
        $url = route('admin.wallet.index');
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk()->assertSee('لا توجد حركات محفظة');

        $booking = Booking::factory()->create();
        WalletTransaction::factory()->create([
            'booking_id' => $booking->id,
            'vendor_id' => $booking->vendorService->vendor_id,
            'transaction_type' => 'credit',
            'amount' => 42.75,
        ]);
        $booking->delete();

        $this->get($url)->assertOk()->assertSee('42.75')->assertSee(route('admin.bookings.show', $booking));
    }

    public function test_admin_can_permanently_delete_the_selected_vendor_with_a_suggested_service(): void
    {
        $admin = User::factory()->admin()->create();
        $selected = User::factory()->vendor()->create();
        $other = User::factory()->create();
        $suggestion = Service::factory()->create(['suggested_by' => $selected->id]);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertSessionHas('error');
        $this->delete(route('admin.users.destroy', $selected))->assertRedirect(route('admin.admin.Users'));
        $this->get(route('admin.admin.Users'))->assertOk()->assertSee('تم حذف المستخدم نهائيًا بنجاح.');
        $this->assertModelMissing($selected);
        $this->assertModelExists($admin);
        $this->assertModelExists($other);
        $this->assertDatabaseHas('services', ['id' => $suggestion->id, 'suggested_by' => null]);
    }

    public function test_admin_can_filter_join_requests_and_sees_pending_requests_first(): void
    {
        $pendingUser = User::factory()->create(['first_name' => 'Nora', 'last_name' => 'Saleh']);
        $reviewedUser = User::factory()->create(['first_name' => 'Sami', 'last_name' => 'Haddad']);
        VendorProfile::factory()->for($pendingUser)->create([
            'application_status' => 'pending', 'work_type' => 'Plumbing', 'experience_years' => 7,
            'applied_at' => '2026-10-05', 'permit_expiry_date' => '2027-03-20',
        ]);
        VendorProfile::factory()->for($reviewedUser)->create(['application_status' => 'approved']);
        $route = 'admin.admin-applications';
        $this->actingAs(User::factory()->admin()->create())->get(route($route))
            ->assertOk()->assertSeeInOrder(['Nora Saleh', 'Sami Haddad']);
        $this->get(route($route, ['status' => 'reviewed']))
            ->assertOk()->assertSee('Sami Haddad')->assertDontSee('Nora Saleh');
        $this->get(route($route, [
            'search' => 'Nora Saleh', 'status' => 'pending', 'work_type' => 'Plumbing',
            'experience_from' => 5, 'experience_to' => 8,
            'applied_from' => '2026-10-01', 'applied_to' => '2026-10-10',
            'permit_from' => '2027-03-01', 'permit_to' => '2027-03-31',
        ]))->assertOk()->assertSee('Nora Saleh')->assertDontSee('Sami Haddad');
        $this->getJson(route($route, ['experience_from' => 9, 'experience_to' => 3]))
            ->assertUnprocessable()->assertJsonValidationErrors('experience_to');
    }

    public function test_admin_approval_promotes_customer_once(): void
    {
        $customer = User::factory()->create();
        VendorProfile::factory()->count(5)->create();
        $application = VendorProfile::factory()->for($customer)->create(['application_status' => 'pending']);
        $url = route('admin.admin-applications.approved', $application);

        $this->actingAs($customer)->put($url)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.admin-applications'))->assertOk()
            ->assertSee('<article class="panel request-card">', false)
            ->assertSee('class="hub-pager"', false)
            ->assertSee('rel="next">التالي', false)
            ->assertSee($url)
            ->assertSee(route('admin.admin-applications.rejected', $application));
        $this->put($url)->assertRedirect(route('admin.admin-applications'));
        $this->assertSame('vendor', $customer->fresh()->role);
        $this->assertSame('approved', $application->fresh()->application_status);
        $this->put($url)->assertNotFound();
        $this->assertSame(1, $customer->notifications()->where('type', 'vendor_application')->count());
    }
}
