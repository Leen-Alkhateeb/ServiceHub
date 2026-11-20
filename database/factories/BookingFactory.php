<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\RecurringAvailability;
use App\Models\User;
use App\Models\VendorService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'vendor_service_id' => VendorService::factory(),
            'availability_id' => fn (array $attributes) => RecurringAvailability::factory()
                ->create(['vendor_id' => $this->offer($attributes)->vendor_id])
                ->getKey(),
            'booking_date' => now()->subWeeks(2)->startOfWeek(Carbon::MONDAY)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'service_name' => fn (array $attributes) => $this->offer($attributes)->service->name,
            'service_description' => fn (array $attributes) => $this->offer($attributes)->description ?? $this->offer($attributes)->service->description,
            'original_price' => fn (array $attributes) => $this->offer($attributes)->price,
            'status' => 'completed',
            'created_at' => fn (array $attributes) => Carbon::parse($attributes['booking_date'])->subDays(2)->setTime(10, 0),
            'approved_at' => fn (array $attributes) => Carbon::parse($attributes['created_at'])->addHour(),
            'payment_deadline' => fn (array $attributes) => Carbon::parse($attributes['approved_at'])->addMinutes(30),
            'completed_at' => fn (array $attributes) => Carbon::parse($attributes['booking_date'].' '.$attributes['end_time']),
            'objection_deadline' => fn (array $attributes) => Carbon::parse($attributes['completed_at'])->addHours(24),
            'updated_at' => fn (array $attributes) => $attributes['completed_at'],
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'booking_date' => now()->addWeek()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'status' => 'pending',
            'created_at' => now(),
            'approved_at' => null,
            'payment_deadline' => null,
            'completed_at' => null,
            'objection_deadline' => null,
            'updated_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->afterCreating(function (Booking $booking): void {
            Payment::factory()->for($booking)->create();
        });
    }

    private function offer(array $attributes): VendorService
    {
        return VendorService::findOrFail($attributes['vendor_service_id']);
    }
}
