<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'amount' => fn (array $attributes) => Booking::findOrFail($attributes['booking_id'])->original_price,
            'payment_method' => 'card',
            'status' => 'paid',
            'paid_at' => fn (array $attributes) => Carbon::parse(Booking::findOrFail($attributes['booking_id'])->approved_at)->addMinutes(10),
            'created_at' => fn (array $attributes) => $attributes['paid_at'],
        ];
    }
}
