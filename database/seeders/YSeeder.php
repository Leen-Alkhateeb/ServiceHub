<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Y;
use Illuminate\Database\Seeder;

class YSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $start = now(Booking::SCHEDULE_TIMEZONE)->startOfDay();
        foreach (range(0, 29) as $day) {
            $date = $start->copy()->addDays($day);
            Y::factory()->create(['created_at' => $date, 'updated_at' => $date]);
        }
    }
}
