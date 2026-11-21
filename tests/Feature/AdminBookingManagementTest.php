<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Services\AccountHistory;
use App\Services\VendorSettlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminBookingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_active_admin_can_search_filter_paginate_and_view_bookings(): void
    {
        $booking = Booking::factory()->pending()->create([
            'service_name' => 'Panel maintenance',
            'booking_date' => '2026-12-05',
        ]);
        Booking::factory()->pending()->create(['service_name' => 'Panel outside date', 'booking_date' => '2026-12-06']);
        Booking::factory()->create(['service_name' => 'Panel wrong status', 'booking_date' => '2026-12-05']);
        Booking::factory()->pending()->count(15)->create();

        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.bookings.index'))->assertOk()
            ->assertViewHas('bookings', fn ($bookings) => $bookings->total() === 18 && $bookings->hasMorePages());

        $this->get(route('admin.bookings.index', [
            'q' => 'Panel', 'status' => 'pending', 'from' => '2026-12-05', 'to' => '2026-12-05',
        ]))->assertOk()->assertSee('Panel maintenance')->assertDontSee('Panel outside date')->assertDontSee('Panel wrong status');

        $this->get(route('admin.bookings.index', ['q' => $booking->id]))->assertOk()
            ->assertViewHas('bookings', fn ($bookings) => $bookings->total() === 1 && $bookings->first()->is($booking));

        $this->get(route('admin.bookings.show', $booking))->assertOk()
            ->assertSee('Panel maintenance')->assertSee('data-archive-action', false)->assertSee('id="archive-dialog"', false);
    }

    public function test_an_admin_can_archive_bookings_at_any_stage_and_preserve_paid_history(): void
    {
        $paid = Booking::factory()->paid()->create();
        $payment = $paid->payment;
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $pending = Booking::factory()->pending()->create(['service_name' => 'Pending archive target']);
        foreach ([$pending, Booking::factory()->create(['status' => 'approved']), Booking::factory()->create(['status' => 'rejected']), $paid] as $booking) {
            $this->delete(route('admin.bookings.destroy', $booking))->assertRedirect(route('admin.bookings.index'));
            $this->assertSoftDeleted($booking);
            $this->assertSame($booking->status, $booking->fresh()->status);
        }

        $this->get(route('admin.bookings.index'))->assertOk()->assertDontSee('Pending archive target');
        $this->get(route('admin.bookings.index', ['archived' => 1]))->assertOk()->assertSee('Pending archive target');
        $this->get(route('admin.bookings.show', $paid))->assertOk()->assertSee('مدفوعة')->assertDontSee('data-archive-action', false);
        $this->assertTrue(Payment::findOrFail($payment->id)->booking->is($paid));
        $this->assertSame(1, app(VendorSettlement::class)->release());
        $this->assertSame($payment->amount, $paid->walletTransactions()->sole()->amount);

        $this->actingAs($paid->customer);
        $history = app(AccountHistory::class)->forTab($paid->customer, 'payments', Request::create('/my-profile'));
        $this->assertTrue($history['entries']->sole()->is($paid));
        $this->get(route('payment.invoice', $paid))->assertOk();
        $this->get(route('payment.checkout', $paid))->assertNotFound();
    }

    public function test_admin_can_search_bookings_by_customer_name_and_sees_archive_message(): void
    {
        $customer = User::factory()->create(['first_name' => 'Rania', 'last_name' => 'Haddad']);
        $booking = Booking::factory()->pending()->create(['customer_id' => $customer->id]);
        Booking::factory()->pending()->count(2)->create();

        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.bookings.index', ['q' => 'Rania']))->assertOk()
            ->assertViewHas('bookings', function ($bookings) use ($booking) {
                return $bookings->total() === 1 && $bookings->first()->is($booking);
            });

        $this->followingRedirects()
            ->delete(route('admin.bookings.destroy', $booking))
            ->assertOk()
            ->assertSee('تم نقل الحجز #' . $booking->id . ' إلى الأرشيف');
    }

    public function test_guests_and_users_without_active_admin_access_cannot_manage_bookings(): void
    {
        $booking = Booking::factory()->pending()->create();
        $index = route('admin.bookings.index');
        $show = route('admin.bookings.show', $booking);
        $destroy = route('admin.bookings.destroy', $booking);

        $this->get($index)->assertRedirect(route('login'));
        $this->get($show)->assertRedirect(route('login'));
        $this->delete($destroy)->assertRedirect(route('login'));

        foreach ([User::factory()->create(), User::factory()->vendor()->create(), User::factory()->admin()->create(['status' => 'suspended'])] as $user) {
            $this->actingAs($user);
            $this->get($index)->assertForbidden();
            $this->get($show)->assertForbidden();
            $this->delete($destroy)->assertForbidden();
        }

        $this->assertNotSoftDeleted($booking);
    }
}
