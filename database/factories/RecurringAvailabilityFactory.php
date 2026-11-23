<?php

namespace Database\Factories;

use App\Models\RecurringAvailability;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;




class RecurringAvailabilityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vendor_id' => User::factory()->vendor(),
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'is_active' => true,
        ];
    }
}
