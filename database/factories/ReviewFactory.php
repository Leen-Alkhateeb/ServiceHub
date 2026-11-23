<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;




class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'reviewer_id' => fn (array $attributes) => Booking::findOrFail($attributes['booking_id'])->customer_id,
            'reviewed_user_id' => fn (array $attributes) => Booking::findOrFail($attributes['booking_id'])->vendorService->vendor_id,
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->sentence(),
            'created_at' => fn (array $attributes) => Carbon::parse(Booking::findOrFail($attributes['booking_id'])->completed_at)->addDay(),
        ];
    }

    public function fromVendor(): static
    {
        return $this->state([
            'reviewer_id' => fn (array $attributes) => Booking::findOrFail($attributes['booking_id'])->vendorService->vendor_id,
            'reviewed_user_id' => fn (array $attributes) => Booking::findOrFail($attributes['booking_id'])->customer_id,
        ]);
    }
}
