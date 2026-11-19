<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->restrictOnDelete();
            $table->enum('day_of_week', [
                'sunday', 'monday', 'tuesday', 'wednesday',
                'thursday', 'friday', 'saturday',
            ]);
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->index(['vendor_id', 'day_of_week']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_availability');
    }
};
