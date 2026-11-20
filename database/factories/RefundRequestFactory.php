<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\RefundRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<RefundRequest>
 */
class RefundRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->paid(),
            'customer_id' => fn (array $attributes) => Booking::findOrFail($attributes['booking_id'])->customer_id,
            'reason' => fake()->sentence(),
            'status' => 'pending',
            'created_at' => fn (array $attributes) => Carbon::parse(Booking::findOrFail($attributes['booking_id'])->completed_at)->addHour(),
        ];
    }
}
