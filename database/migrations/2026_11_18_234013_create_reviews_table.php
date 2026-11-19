<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->restrictOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('rating'); // Validate 1..5 in Laravel request rules.
            $table->text('comment')->nullable();
            $table->string('image')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->index(['reviewed_user_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
