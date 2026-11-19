<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['card', 'bank_transfer', 'e_wallet', 'cash'])->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded', 'partially_refunded'])->default('pending');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
