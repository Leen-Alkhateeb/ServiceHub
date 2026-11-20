<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(['female', 'male']);

        return [
            'first_name' => fake()->firstName($gender),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->numerify('07########'),
            'location' => fake()->randomElement(['عمّان', 'الزرقاء', 'إربد', 'مادبا']),
            'gender' => $gender,
            'role' => 'customer',
            'status' => 'active',
        ];
    }

    public function vendor(): static
    {
        return $this->state(['role' => 'vendor']);
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }
}
