<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable()->change();
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('settlement_status', ['pending', 'on_hold', 'awaiting_admin', 'insufficient_funds', 'advanced', 'settled'])->nullable()->change();
        });
    }
};
