<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->restrictOnDelete();
            $table->enum('transaction_type', ['credit', 'debit', 'payout', 'penalty', 'adjustment']);
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
