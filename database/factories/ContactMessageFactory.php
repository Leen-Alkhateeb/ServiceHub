<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;




class ContactMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'subject' => fake()->sentence(5),
            'message' => fake()->paragraph(),
            'status' => 'pending',
        ];
    }
}
