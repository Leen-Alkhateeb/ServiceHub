<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\User;
use App\Models\VendorService;
use Illuminate\Database\Eloquent\Factories\Factory;




class VendorServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vendor_id' => User::factory()->vendor(),
            'service_id' => Service::factory(),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 15, 150),
            'image' => '',
            'status' => 'active',
        ];
    }
}
