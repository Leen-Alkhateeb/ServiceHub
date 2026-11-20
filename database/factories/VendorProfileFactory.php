<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorProfile>
 */
class VendorProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->vendor(),
            'experience_years' => fake()->numberBetween(1, 25),
            'bio' => fake()->paragraph(),
            'work_type' => fake()->randomElement(['السباكة', 'الكهرباء', 'صيانة المكيفات']),
            'work_permit_path' => 'vendor-permits/demo-placeholder.pdf',
            'permit_expiry_date' => now()->addYear()->toDateString(),
            'application_status' => 'approved',
            'applied_at' => now()->subMonth(),
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'user_id' => User::factory(),
            'application_status' => 'pending',
        ]);
    }
}
