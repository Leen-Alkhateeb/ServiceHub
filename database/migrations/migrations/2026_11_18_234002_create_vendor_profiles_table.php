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
            $table->unsignedInteger('experience_years');
            $table->text('bio');
            $table->string('work_type');
            $table->string('work_permit_path');
            $table->date('permit_expiry_date');
            $table->enum('application_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->dateTime('applied_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_profiles');
    }
};
