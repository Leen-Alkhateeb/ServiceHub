<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class YFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'desc'=> fake()->sentence(),
        ];
    }
}
