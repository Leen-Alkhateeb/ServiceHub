<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $wallet = DB::table('wallet_transactions')->whereNotNull('vendor_id')
                ->where(fn ($rows) => $rows->where('account_type', '!=', 'vendor')
                    ->orWhere(fn ($rows) => $rows->whereNotNull('user_id')->whereColumn('user_id', '!=', 'vendor_id')))
                ->pluck('id');
            $cases = DB::table('refund_requests')->where('case_type', '!=', 'objection')->whereNull('submitted_by')->pluck('id');
            $objections = DB::table('refund_requests as cases')->join('bookings', 'bookings.id', '=', 'cases.booking_id')
                ->where('case_type', 'objection')->where(fn ($rows) => $rows->whereColumn('cases.customer_id', '!=', 'bookings.customer_id')
                    ->orWhereColumn('cases.submitted_by', '!=', 'bookings.customer_id'))->pluck('cases.id');
            if ($wallet->isNotEmpty() || $cases->isNotEmpty() || $objections->isNotEmpty()) {
                throw new RuntimeException("Ambiguous wallet IDs: {$wallet}; case IDs: {$cases}; objection IDs: {$objections}.");
            }
            DB::table('wallet_transactions')->where('account_type', 'vendor')->whereNull('user_id')->whereNotNull('vendor_id')
                ->update(['user_id' => DB::raw('vendor_id')]);
            DB::table('refund_requests')->where('case_type', 'objection')->whereNull('submitted_by')->whereNotNull('customer_id')
                ->update(['submitted_by' => DB::raw('customer_id')]);
            $unowned = DB::table('wallet_transactions')->whereIn('account_type', ['customer', 'vendor'])->whereNull('user_id')->pluck('id');
            $unsubmitted = DB::table('refund_requests')->whereNull('submitted_by')->pluck('id');
            if ($unowned->isNotEmpty() || $unsubmitted->isNotEmpty()) {
                throw new RuntimeException("Missing ownership wallet IDs: {$unowned}; case IDs: {$unsubmitted}.");
            }
        });
        Schema::table('refund_requests', fn (Blueprint $table) => $table->unsignedBigInteger('customer_id')->nullable()->change());
    }
};
