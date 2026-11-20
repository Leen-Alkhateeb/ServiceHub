
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $foreignKeys = [
        'bookings' => [
            'customer_id' => 'users',
            'availability_id' => 'recurring_availability',
            'vendor_service_id' => 'vendor_services',
        ],
        'contact_messages' => [
            'sender_id' => 'users',
        ],
        'payments' => [
            'booking_id' => 'bookings',
        ],
        'portfolio_projects' => [
            'vendor_id' => 'users',
        ],
        'recurring_availability' => [
            'vendor_id' => 'users',
        ],
        'refund_requests' => [
            'booking_id' => 'bookings',
            'customer_id' => 'users',
        ],
        'reviews' => [
            'booking_id' => 'bookings',
            'reviewed_user_id' => 'users',
            'reviewer_id' => 'users',
        ],
        'vendor_profiles' => [
            'user_id' => 'users',
        ],
        'vendor_services' => [
            'vendor_id' => 'users',
        ],
        'wallet_transactions' => [
            'booking_id' => 'bookings',
            'vendor_id' => 'users',
        ],
    ];

    public function up(): void
    {
        foreach ($this->foreignKeys as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column => $references) {
                    $table->dropForeign([$column]);

                    $table->foreign($column)
                        ->references('id')
                        ->on($references)
                        ->cascadeOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->foreignKeys as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column => $references) {
                    $table->dropForeign([$column]);

                    $table->foreign($column)
                        ->references('id')
                        ->on($references)
                        ->restrictOnDelete();
                }
            });
        }
    }
};
