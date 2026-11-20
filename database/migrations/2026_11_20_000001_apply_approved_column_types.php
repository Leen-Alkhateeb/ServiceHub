<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    public function up(): void
    {
        foreach (self::DAYS as $number => $day) {
            DB::table('recurring_availability')->whereRaw('LOWER(TRIM(day_of_week)) = ?', [$day])
                ->update(['day_of_week' => (string) $number]);
        }

        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->enum('application_status', ['pending', 'approved', 'rejected'])->change();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'withdrawn'])->change();
        });

        Schema::table('vendor_services', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive', 'pending', 'rejected'])->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->change();
            $table->enum('payment_method', ['simulated', 'card', 'cash'])->nullable()->change();
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->enum('transaction_type', ['payout', 'penalty', 'refund_share', 'adjustment', 'credit'])->change();
        });

        Schema::table('refund_requests', function (Blueprint $table) {
            $table->enum('status', ['pending', 'accepted', 'rejected'])->change();
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->enum('status', ['pending', 'reviewed'])->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('gender', ['male', 'female'])->change();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', ['booking', 'payment', 'refund', 'review', 'vendor_application', 'system', 'wallet', 'service'])->change();
        });

        // The existing bookings.status migration already changes that column to ENUM.
        // Do not repeat it here.
        Schema::table('recurring_availability', function (Blueprint $table) {
            $table->unsignedTinyInteger('day_of_week')->change();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `recurring_availability` ADD CONSTRAINT `chk_recurring_availability_day_of_week` CHECK (`day_of_week` BETWEEN 0 AND 6)');
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $drop = DB::connection()->isMaria() ? 'DROP CONSTRAINT' : 'DROP CHECK';
            DB::statement('ALTER TABLE `recurring_availability` '.$drop.' `chk_recurring_availability_day_of_week`');
        }

        Schema::table('recurring_availability', function (Blueprint $table) {
            $table->string('day_of_week')->change();
        });

        foreach (self::DAYS as $number => $day) {
            DB::table('recurring_availability')->where('day_of_week', (string) $number)->update(['day_of_week' => $day]);
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('type')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('gender')->change();
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('status')->change();
        });

        Schema::table('refund_requests', function (Blueprint $table) {
            $table->string('status')->change();
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('transaction_type')->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('status')->change();
            $table->string('payment_method')->nullable()->change();
        });

        Schema::table('vendor_services', function (Blueprint $table) {
            $table->string('status')->change();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('approval_status')->change();
        });

        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->string('application_status')->change();
        });
    }
};
