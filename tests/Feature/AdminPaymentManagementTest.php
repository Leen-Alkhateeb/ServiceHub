<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_filter_and_search_payments(): void
    {
        $customer = User::factory()->create(['first_name' => 'Omar', 'last_name' => 'Khaled']);
        $booking = Booking::factory()->create(['customer_id' => $customer->id, 'service_name' => 'AC repair']);
        Payment::factory()->create(['booking_id' => $booking->id, 'amount' => 40, 'status' => 'paid', 'payment_method' => 'card']);
        Payment::factory()->create(['amount' => 25, 'status' => 'refunded', 'payment_method' => 'card']);
        Payment::factory()->create(['amount' => 10, 'status' => 'pending', 'payment_method' => 'cash', 'paid_at' => null]);

        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.payments.index'))->assertOk()
            ->assertViewHas('payments', function ($payments) {
                return $payments->total() === 3;
            })
            ->assertViewHas('paidTotal', function ($total) {
                return (float) $total === 40.0;
            })
            ->assertViewHas('refundedTotal', function ($total) {
                return (float) $total === 25.0;
            });

        $this->get(route('admin.payments.index', ['q' => 'Omar']))->assertOk()
            ->assertViewHas('payments', function ($payments) use ($booking) {
                return $payments->total() === 1 && $payments->first()->booking_id === $booking->id;
            });

        $this->get(route('admin.payments.index', ['status' => 'pending', 'method' => 'cash']))->assertOk()
            ->assertViewHas('payments', function ($payments) {
                return $payments->total() === 1 && (float) $payments->first()->amount === 10.0;
            });

        $this->get(route('admin.payments.index', ['status' => 'unknown']))->assertSessionHasErrors('status');
    }

    public function test_payment_of_archived_booking_stays_visible_with_details(): void
    {
        $booking = Booking::factory()->create(['service_name' => 'Archived plumbing job', 'original_price' => 100]);
        $payment = Payment::factory()->create(['booking_id' => $booking->id, 'amount' => 50, 'status' => 'paid']);
        $booking->delete();

        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.payments.index'))->assertOk()->assertSee('Archived plumbing job');

        $this->get(route('admin.payments.show', $payment))->assertOk()
            ->assertSee('Archived plumbing job')
            ->assertSee('مؤرشف')
            ->assertViewHas('remainingAmount', 50.0);
    }

    public function test_non_admins_cannot_view_payments(): void
    {
        $payment = Payment::factory()->create();

        $this->get(route('admin.payments.index'))->assertRedirect(route('login'));

        foreach ([User::factory()->create(), User::factory()->vendor()->create()] as $user) {
            $this->actingAs($user);
            $this->get(route('admin.payments.index'))->assertForbidden();
            $this->get(route('admin.payments.show', $payment))->assertForbidden();
        }
    }
}
