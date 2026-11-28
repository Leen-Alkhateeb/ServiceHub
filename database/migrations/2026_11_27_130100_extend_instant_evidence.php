<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('booking_evidence', 'images');
        Schema::table('images', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable()->change();
            $table->foreignId('project_id')->nullable()->constrained('portfolio_projects')->cascadeOnDelete();
            $table->foreignId('review_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('instant_request_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('refund_request_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at')->nullable();
        });
        DB::statement("ALTER TABLE images ADD CONSTRAINT images_primary_parent_check CHECK (
            ((project_id IS NOT NULL) + (review_id IS NOT NULL) + (instant_request_id IS NOT NULL) +
             (booking_id IS NOT NULL) + (refund_request_id IS NOT NULL)) = 1
            AND ((project_id IS NOT NULL AND kind = 'portfolio') OR (review_id IS NOT NULL AND kind = 'review')
              OR (instant_request_id IS NOT NULL AND kind = 'problem')
              OR (booking_id IS NOT NULL AND kind IN ('completion', 'objection', 'before', 'after'))
              OR (refund_request_id IS NOT NULL AND kind IN ('objection', 'exceptional_case')))
        )");
    }
};
