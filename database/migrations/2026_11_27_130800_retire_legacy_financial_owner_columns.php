<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $missingWallet = DB::table('wallet_transactions')->whereIn('account_type', ['customer', 'vendor'])->whereNull('user_id')->pluck('id');
        $missingCases = DB::table('refund_requests')->whereNull('submitted_by')->pluck('id');
        if ($missingWallet->isNotEmpty() || $missingCases->isNotEmpty()) {
            throw new RuntimeException("Unresolved account ownership: {$missingWallet}; unresolved case submitters: {$missingCases}.");
        }

        DB::statement('ALTER TABLE refund_requests
            DROP FOREIGN KEY refund_requests_customer_id_foreign,
            DROP FOREIGN KEY refund_requests_submitted_by_foreign,
            MODIFY submitted_by BIGINT UNSIGNED NOT NULL,
            ADD CONSTRAINT refund_requests_case_submitter_fk FOREIGN KEY (submitted_by) REFERENCES users (id) ON DELETE CASCADE,
            DROP COLUMN customer_id');
        DB::statement('ALTER TABLE wallet_transactions DROP FOREIGN KEY wallet_transactions_vendor_id_foreign, DROP COLUMN vendor_id');
    }
};
