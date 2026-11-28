<?php

namespace Database\Seeders;

use App\Models\Y;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class YSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Y::factory()->count(10)->create();
    }
}
