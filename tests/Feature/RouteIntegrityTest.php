<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_pages_and_forbidden_pages_keep_their_http_status(): void
    {
        $this->get('/missing-page-example')->assertNotFound()->assertSee('الصفحة مش موجودة');
        $customer = User::factory()->create();
        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden()->assertSee('ما عندك صلاحية');
        $this->get(route('market.show', 9999))->assertNotFound()->assertSee('الصفحة مش موجودة');
        $this->getJson('/missing-json-example')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_public_and_role_navigation_pages_render_with_demo_records(): void
    {
        $this->seed();
        foreach (['home', 'about', 'contact', 'how-it-works', 'faq', 'market.index', 'categories.index', 'vendors.index', 'login', 'register', 'password.request', 'vendor.apply'] as $name) {
            $this->get(route($name))->assertOk();
        }

        $customer = User::whereEmail('customer.seed@servicehub.example')->firstOrFail();
        $vendor = User::whereEmail('plumbing.seed@servicehub.example')->firstOrFail();
        $admin = User::whereEmail('admin.seed@servicehub.example')->firstOrFail();
        $this->actingAs($customer);
        foreach (['account.profile', 'customer.dashboard', 'customer.bookings', 'customer.favorites', 'customer.notifications', 'customer.settings', 'customer.reviews', 'customer.messages', 'notifications.index'] as $name) {
            $this->get(route($name))->assertOk();
        }
        $this->post(route('contact.store'), ['subject' => 'Support question', 'message' => 'Help with my account.'])->assertRedirect(route('contact'));
        $this->get(route('customer.messages'))->assertOk()->assertSee('Support question');

        $this->actingAs($vendor);
        foreach (['vendor.profile', 'vendor.dashboard', 'vendor.availability.index', 'vendor.vendor-bookings.index', 'vendor.wallet', 'vendor.reviews'] as $name) {
            $this->get(route($name))->assertOk();
        }
        $this->get('/vendor-portfolio-form')->assertRedirect('/vendor-profile?tab=portfolio');

        $this->actingAs($admin);
        foreach (['admin.dashboard', 'admin.admin.Users', 'admin.categories', 'admin.admin-services', 'admin.admin-applications',
            'admin.service-requests.index', 'admin.bookings.index', 'admin.cancellations.index', 'admin.payments.index',
            'admin.wallet.index', 'admin.refunds.index', 'admin.reviews.index', 'admin.messages.index'] as $name) {
            $this->get(route($name))->assertOk();
        }
        $this->get(route('admin.messages.show', ContactMessage::firstOrFail()))->assertOk();
        $this->get(route('admin.payments.show', Payment::firstOrFail()))->assertOk();
        $this->get(route('admin.bookings.show', Booking::firstOrFail()))->assertOk();
    }
}
