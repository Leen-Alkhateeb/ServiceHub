<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('experience_years')->nullable();
            $table->text('bio')->nullable();
            $table->string('work_type')->nullable();
            $table->string('work_permit_path')->nullable();
            $table->date('permit_expiry_date')->nullable();
            $table->enum('application_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->dateTime('applied_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_profiles');
    }
};
