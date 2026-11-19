<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('vendor_service_id')->constrained('vendor_services')->restrictOnDelete();
            $table->foreignId('availability_id')->constrained('recurring_availability')->restrictOnDelete();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('service_name');
            $table->text('service_description');
            $table->decimal('original_price', 10, 2);
            $table->string('status');
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('payment_deadline')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('objection_deadline')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('cancellation_penalty', 10, 2)->default(0);

            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
