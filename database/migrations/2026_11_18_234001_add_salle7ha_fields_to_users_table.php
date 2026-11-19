<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Laravel's default users table already has id, email, password and timestamps.
            // Nullable fields keep this migration safe for existing user records.
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('location')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->enum('role', ['customer', 'vendor', 'admin'])->default('customer');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('profile_image')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name', 'phone', 'location',
                'gender', 'role', 'status', 'profile_image',
            ]);
        });
    }
};
