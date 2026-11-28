<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Table_Test;

class TableTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
                Table_Test::factory()->count(10)->create();

    }
}
