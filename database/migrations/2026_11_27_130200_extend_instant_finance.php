<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('paid_amount', 10, 3)->nullable();
            $table->decimal('refunded_amount', 10, 3)->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'partially_refunded', 'refunded'])->change();
        });
        DB::table('payments')->where('status', 'paid')->update(['paid_amount' => DB::raw('amount')]);
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable()->change();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('related_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->foreignId('refund_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('account_type', ['customer', 'vendor', 'platform'])->default('vendor');
            $table->enum('direction', ['credit', 'debit'])->nullable();
            $table->enum('status', ['posted', 'outstanding', 'repaid'])->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->dateTime('due_at')->nullable();
            $table->index(['user_id', 'transaction_type', 'status']);
            $table->enum('transaction_type', ['payout', 'penalty', 'refund_share', 'adjustment', 'credit', 'service_payment', 'commission', 'compensation', 'refund', 'debt', 'debt_repayment', 'platform_advance', 'advance_recovery'])->change();
        });
        DB::table('wallet_transactions')->whereNotNull('vendor_id')->update(['user_id' => DB::raw('vendor_id')]);
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->foreignId('submitted_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('beneficiary_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('case_type', ['objection', 'customer_no_show', 'vendor_late', 'arrival_verification_issue', 'completion_issue', 'partial_service', 'stuck_booking', 'exceptional_cancellation', 'suspected_fraud'])->default('objection');
            $table->decimal('compensation_amount', 10, 3)->nullable();
            $table->decimal('approved_service_amount', 10, 3)->nullable();
            $table->enum('compensation_status', ['pending', 'approved', 'rejected', 'paid'])->nullable();
            $table->dateTime('compensation_due_at')->nullable();
        });
        DB::table('refund_requests')->update(['submitted_by' => DB::raw('customer_id')]);
    }
};
