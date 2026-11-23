<?php

namespace Database\Factories;

use App\Models\PortfolioProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;




class PortfolioProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vendor_id' => User::factory()->vendor(),
            'name' => ucfirst(fake()->words(4, true)),
            'description' => fake()->paragraph(),
            'completion_date' => now()->subMonth()->toDateString(),
        ];
    }
}
