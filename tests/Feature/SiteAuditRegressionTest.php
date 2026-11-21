<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorService;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteAuditRegressionTest extends TestCase
{
    use RefreshDatabase;

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
        $this->post(route('booking.store', $offer), ['slot' => $slot])->assertRedirect();
        $booking = $customer->bookingsAsCustomer()->firstOrFail();
        $checkout = route('payment.checkout', $booking);
        $this->get(route('customer.bookings'))->assertOk()->assertSee($checkout);
        $this->get($checkout)->assertOk()->assertSee('الدفع تجريبي');

        $this->actingAs(User::factory()->create())->get($servicePage)->assertOk()->assertDontSee($slotValue, false);
        $this->get($checkout)->assertForbidden();
        $this->from(route('market.show', $offer))->post(route('booking.store', $offer), ['slot' => $slot])
            ->assertSessionHasErrors('slot');

        $payment = ['payment_method' => 'card', 'card_name' => 'Demo', 'card_number' => '4242 4242 4242 4242'];
        $this->actingAs($customer)->post(route('payment.store', $booking), $payment)
            ->assertRedirect(route('payment.invoice', $booking));
        $this->get(route('payment.invoice', $booking))->assertOk();
        $this->post(route('booking.cancel', $booking))->assertForbidden();
        $this->assertDatabaseHas('payments', ['booking_id' => $booking->id, 'amount' => 30, 'status' => 'paid']);
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
        $this->assertModelMissing($selected);
        $this->assertModelExists($admin);
        $this->assertModelExists($other);
        $this->assertDatabaseHas('services', ['id' => $suggestion->id, 'suggested_by' => null]);
    }

    public function test_admin_approval_promotes_customer_once(): void
    {
        $customer = User::factory()->create();
        $application = VendorProfile::factory()->for($customer)->create(['application_status' => 'pending']);
        $url = route('admin.admin-applications.approved', $application);

        $this->actingAs($customer)->put($url)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->put($url)->assertRedirect(route('admin.admin-applications'));
        $this->assertSame('vendor', $customer->fresh()->role);
        $this->assertSame('approved', $application->fresh()->application_status);
        $this->put($url)->assertNotFound();
        $this->assertSame(1, $customer->notifications()->where('type', 'vendor_application')->count());
    }
}
