<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('portfolio_images')) {
            $missingPortfolio = DB::scalar("SELECT COUNT(*) FROM portfolio_images p WHERE NOT EXISTS (
                SELECT 1 FROM images i WHERE i.kind = 'portfolio' AND i.legacy_portfolio_id = p.id
                AND i.project_id = p.project_id AND BINARY i.path = BINARY p.image
                AND i.is_active = p.is_active AND i.created_at <=> p.created_at)");
            if ($missingPortfolio) throw new RuntimeException('Portfolio image migration differs from its source.');
        }
        if (Schema::hasColumn('reviews', 'image')) {
            $missingReviews = DB::scalar("SELECT COUNT(*) FROM reviews r WHERE r.image IS NOT NULL AND r.image <> '' AND NOT EXISTS (
                SELECT 1 FROM images i WHERE i.kind = 'review' AND i.review_id = r.id
                AND BINARY i.path = BINARY r.image AND i.is_active = r.is_active AND i.created_at <=> r.created_at)");
            if ($missingReviews) throw new RuntimeException('Review image migration differs from its source.');
            Schema::table('reviews', fn (Blueprint $table) => $table->dropColumn('image'));
        }
        Schema::dropIfExists('portfolio_images');
    }
};
