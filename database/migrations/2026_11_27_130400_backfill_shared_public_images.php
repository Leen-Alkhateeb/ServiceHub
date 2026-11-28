<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('images', 'legacy_portfolio_id')) {
            Schema::table('images', function (Blueprint $table) {
                $table->dropUnique('booking_evidence_path_unique');
                $table->unsignedBigInteger('legacy_portfolio_id')->nullable()->unique();
                $table->index(['project_id', 'kind', 'is_active', 'id']);
            });
        }

        DB::transaction(function () {
            DB::statement("INSERT INTO images (legacy_portfolio_id, project_id, kind, path, is_active, created_at)
                SELECT p.id, p.project_id, 'portfolio', p.image, p.is_active, p.created_at
                FROM portfolio_images p LEFT JOIN images i ON i.legacy_portfolio_id = p.id
                WHERE i.id IS NULL ORDER BY p.id");
            DB::statement("INSERT INTO images (review_id, kind, path, is_active, created_at)
                SELECT r.id, 'review', r.image, r.is_active, r.created_at
                FROM reviews r LEFT JOIN images i ON i.review_id = r.id AND i.kind = 'review'
                WHERE r.image IS NOT NULL AND r.image <> '' AND i.id IS NULL ORDER BY r.id");
        });
    }
};
