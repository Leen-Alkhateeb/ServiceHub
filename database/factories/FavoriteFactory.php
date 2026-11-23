<?php

namespace Database\Factories;

use App\Models\Favorite;
use App\Models\User;
use App\Models\VendorService;
use Illuminate\Database\Eloquent\Factories\Factory;




class FavoriteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'vendor_service_id' => VendorService::factory(),
        ];
    }
}
