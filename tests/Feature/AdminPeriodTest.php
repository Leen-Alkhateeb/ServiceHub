<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorService;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_persists_across_dashboard_lists_and_uses_each_record_date(): void
    {
        $byDate = [];
        foreach (['2026-03-14 23:59:59', '2026-03-15 23:59:59', '2026-03-16 00:00:00'] as $at) {
            $vendor = VendorProfile::factory()->create()->user;
            $customer = User::factory()->create(['created_at' => $at]);
            $category = Category::factory()->create(['created_at' => $at]);
            $service = Service::factory()->for($category)->create(['suggested_by' => $vendor->id, 'created_at' => $at]);
            $offer = VendorService::factory()->for($vendor, 'vendor')->for($service)->create(['created_at' => $at]);
            $booking = Booking::factory()->for($offer)->create(['customer_id' => $customer->id, 'created_at' => $at, 'cancelled_at' => $at, 'status' => 'cancelled']);
            $payment = Payment::factory()->for($booking)->create(['created_at' => $at, 'amount' => 40, 'status' => 'paid']);
            $byDate[] = [
                'admin.admin.Users' => ['users', $customer],
                'admin.categories' => ['categories', $category],
                'admin.admin-services' => ['services', $service],
                'admin.service-requests.index' => ['requests', $offer],
                'admin.bookings.index' => ['bookings', $booking],
                'admin.cancellations.index' => ['cancellations', $booking],
                'admin.payments.index' => ['payments', $payment],
                'admin.admin-applications' => ['applications', VendorProfile::factory()->pending()->create(['applied_at' => $at])],
                'admin.refunds.index' => ['refunds', RefundRequest::factory()->for($booking)->create(['created_at' => $at])],
                'admin.reviews.index' => ['reviews', Review::factory()->for($booking)->create(['created_at' => $at])],
                'admin.messages.index' => ['messages', ContactMessage::factory()->create(['created_at' => $at])],
                'admin.wallet.index' => ['transactions', WalletTransaction::factory()->for($booking)->create(['created_at' => $at])],
            ];
        }

        $this->actingAs(User::factory()->admin()->create());
        $this->post(route('admin.period'), ['period_from' => '2026-03-15', 'period_to' => '2026-03-15'])->assertRedirect(route('admin.dashboard'));
        foreach ($byDate[1] as $name => [$variable, $record]) {
            $this->get(route($name))->assertOk()->assertViewHas($variable, fn ($rows) => $rows->total() === 1 && $rows->first()->is($record));
        }
        $this->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['bookings'] === 1 && (float) $stats['paidAmount'] === 40.0)
            ->assertViewHas('bookingChart', ['labels' => ['2026-03-15'], 'values' => [1]]);
        $this->get(route('admin.bookings.show', $byDate[0]['admin.bookings.index'][1]))->assertNotFound();
        $this->get(route('admin.bookings.show', $byDate[1]['admin.bookings.index'][1]))->assertOk();

        $this->post(route('admin.period'), ['period_from' => '2026-03-15'])->assertRedirect();
        $this->get(route('admin.bookings.index'))->assertViewHas('bookings', fn ($rows) => $rows->total() === 2 && $rows->first()->is($byDate[2]['admin.bookings.index'][1]));
        $this->post(route('admin.period'), ['period_to' => '2026-03-15'])->assertRedirect();
        $this->get(route('admin.bookings.index'))->assertViewHas('bookings', fn ($rows) => $rows->total() === 2 && $rows->first()->is($byDate[1]['admin.bookings.index'][1]));
        $this->post(route('admin.period'), ['period_from' => '2026-03-16', 'period_to' => '2026-03-15'])->assertSessionHasErrors('period_to');
        $this->get(route('admin.bookings.index'))->assertViewHas('bookings', fn ($rows) => $rows->total() === 2);
        $this->post(route('admin.period'), [])->assertRedirect();
        $this->get(route('admin.bookings.index'))->assertViewHas('bookings', fn ($rows) => $rows->total() === 3);
    }
}
