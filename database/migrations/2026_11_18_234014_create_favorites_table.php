<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vendor_service_id')->constrained('vendor_services')->cascadeOnDelete();
            $table->dateTime('created_at')->useCurrent();
            // A customer should not bookmark the same service twice.
            $table->unique(['customer_id', 'vendor_service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
