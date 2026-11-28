<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->index(['booking_id', 'status'], 'refund_booking_status_index');
            $table->enum('case_type', [
                'objection', 'customer_no_show', 'vendor_late', 'arrival_verification_issue', 'completion_issue',
                'partial_service', 'stuck_booking', 'exceptional_cancellation', 'suspected_fraud', 'non_execution',
            ])->default('objection')->change();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        });
        DB::statement("ALTER TABLE refund_requests
            ADD COLUMN ordinary_objection_booking_id BIGINT UNSIGNED
                GENERATED ALWAYS AS (CASE WHEN case_type = 'objection' THEN booking_id ELSE NULL END) STORED,
            ADD UNIQUE INDEX ordinary_objection_per_booking (ordinary_objection_booking_id),
            DROP INDEX refund_requests_booking_id_unique");
    }
};
