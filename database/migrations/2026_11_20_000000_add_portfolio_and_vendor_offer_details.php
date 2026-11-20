<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_projects', function (Blueprint $table) {
            $table->decimal('project_price', 10, 2)->nullable();
        });

        Schema::table('vendor_services', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('rejection_reason', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_services', function (Blueprint $table) {
            $table->dropColumn(['duration_minutes', 'rejection_reason']);
        });

        Schema::table('portfolio_projects', function (Blueprint $table) {
            $table->dropColumn('project_price');
        });
    }
};
