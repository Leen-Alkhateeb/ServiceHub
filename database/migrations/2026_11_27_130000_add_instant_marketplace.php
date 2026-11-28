<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->boolean('instant_available')->default(false);
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->dateTime('location_updated_at')->nullable();
            $table->dateTime('last_seen_at')->nullable();
        });
        Schema::table('services', fn (Blueprint $table) => $table->boolean('supports_instant')->default(false));
        Schema::table('vendor_services', fn (Blueprint $table) => $table->boolean('instant_enabled')->default(false));
        Schema::create('instant_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->string('address');
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->enum('status', ['searching', 'assigned', 'expired', 'cancelled']);
            $table->dateTime('expires_at');
            $table->timestamps();
            $table->index(['customer_id', 'status']);
            $table->index(['service_id', 'status', 'expires_at']);
        });
        Schema::create('instant_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instant_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_service_id')->constrained()->cascadeOnDelete();
            $table->decimal('offered_price', 10, 3);
            $table->unsignedInteger('arrival_minutes');
            $table->unsignedInteger('duration_minutes');
            $table->enum('status', ['pending', 'accepted', 'withdrawn', 'expired', 'invalidated']);
            $table->timestamps();
            $table->unique(['instant_request_id', 'vendor_service_id']);
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('availability_id')->nullable()->change();
            $table->enum('booking_type', ['scheduled', 'instant'])->default('scheduled');
            $table->foreignId('instant_offer_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->decimal('agreed_price', 10, 3)->nullable();
            foreach (['expected_arrival_at', 'departed_at', 'arrived_at', 'arrival_verified_at', 'arrival_code_expires_at', 'arrival_code_locked_until', 'started_at', 'settled_at'] as $column) {
                $table->dateTime($column)->nullable();
            }
            $table->string('arrival_code_hash')->nullable();
            $table->text('arrival_code_encrypted')->nullable();
            $table->unsignedTinyInteger('arrival_code_failed_attempts')->default(0);
            $table->enum('settlement_status', ['pending', 'on_hold', 'awaiting_admin', 'insufficient_funds', 'settled'])->nullable();
            $table->enum('status', ['pending', 'approved', 'awaiting_payment', 'paid', 'in_progress', 'completed', 'cancelled', 'refunded', 'objection_pending', 'confirmed', 'rejected', 'expired', 'closed', 'on_the_way', 'arrived'])->default('pending')->change();
            $table->index(['booking_type', 'status', 'customer_id']);
        });
        Schema::create('booking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('event_type', ['status_change', 'price_adjustment', 'arrival_recorded', 'arrival_verified', 'admin_decision', 'settlement_change', 'cancellation']);
            $table->enum('status_type', ['operational', 'settlement'])->nullable();
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->decimal('previous_price', 10, 3)->nullable();
            $table->decimal('proposed_price', 10, 3)->nullable();
            $table->enum('adjustment_status', ['pending', 'accepted', 'rejected', 'withdrawn'])->nullable();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->text('description')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['booking_id', 'event_type', 'adjustment_status']);
        });
    }
};
