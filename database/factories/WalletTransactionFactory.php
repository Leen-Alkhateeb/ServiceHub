<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\WalletTransaction;
use App\Services\VendorSettlement;
use Illuminate\Database\Eloquent\Factories\Factory;




class WalletTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->paid(),
            'user_id' => fn (array $attributes) => Booking::findOrFail($attributes['booking_id'])->vendorService->vendor_id,
            'transaction_type' => 'credit',
            'amount' => fn (array $attributes) => VendorSettlement::vendorShare((string) Booking::findOrFail($attributes['booking_id'])->payment->amount),
            'description' => 'تحرير حصة الفني (85٪) بعد انتهاء مهلة الاعتراض',
            'created_at' => fn (array $attributes) => Booking::findOrFail($attributes['booking_id'])->objection_deadline,
        ];
    }
}
