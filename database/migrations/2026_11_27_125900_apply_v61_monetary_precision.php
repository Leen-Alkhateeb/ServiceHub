<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'vendor_services' => ['price' => 'required'],
        'portfolio_projects' => ['project_price' => 'nullable'],
        'bookings' => ['original_price' => 'required', 'cancellation_penalty' => 'zero', 'cancellation_refund_amount' => 'nullable', 'cancellation_platform_share' => 'nullable'],
        'payments' => ['amount' => 'required'],
        'wallet_transactions' => ['amount' => 'required'],
        'refund_requests' => ['refund_amount' => 'nullable'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column => $type) {
                if (DB::table($table)->where(fn ($rows) => $rows->where($column, '>', '9999999.999')->orWhere($column, '<', '-9999999.999'))->exists()) {
                    throw new RuntimeException("V6.1 precision cannot preserve every value in {$table}.{$column}.");
                }
            }
        }
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column => $type) {
                    $definition = $blueprint->decimal($column, 10, 3);
                    if ($type === 'nullable') $definition->nullable();
                    if ($type === 'zero') $definition->default(0);
                    $definition->change();
                }
            });
        }
    }
};
