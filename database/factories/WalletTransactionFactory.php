<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->paid(),
            'vendor_id' => fn (array $attributes) => Booking::findOrFail($attributes['booking_id'])->vendorService->vendor_id,
            'transaction_type' => 'credit',
            'amount' => fn (array $attributes) => Booking::findOrFail($attributes['booking_id'])->original_price,
            'description' => 'تحرير كامل مبلغ الحجز',
            'created_at' => fn (array $attributes) => Booking::findOrFail($attributes['booking_id'])->objection_deadline,
        ];
    }
}
