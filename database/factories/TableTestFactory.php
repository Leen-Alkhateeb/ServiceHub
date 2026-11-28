<?php

namespace Database\Factories;

use App\Models\Table_Test;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Table_Test>
 */
class TableTestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
      protected $model = Table_Test::class;
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'desc'=> fake()->sentence(),
        ];
    }
}
