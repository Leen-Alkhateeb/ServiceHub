<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('completion_description')->nullable();
        });

        Schema::create('booking_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('path')->unique();
            $table->index(['booking_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_evidence');
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('completion_description'));
    }
};
