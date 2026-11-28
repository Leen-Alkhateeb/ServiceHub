<?php

namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Seeder;
use App\Models\Table_Test;

class TableTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $start = now(Booking::SCHEDULE_TIMEZONE)->startOfDay();
        foreach (range(0, 29) as $day) {
            $date = $start->copy()->addDays($day);
            Table_Test::factory()->create(['created_at' => $date, 'updated_at' => $date]);
        }
    }
}
