<?php

namespace Database\Factories;

use App\Models\Y;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Y>
 */
class YFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'desc'=> fake()->sentence(),
        ];
    }
}
