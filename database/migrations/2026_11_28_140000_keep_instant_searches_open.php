<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instant_requests', fn (Blueprint $table) => $table->dateTime('expires_at')->nullable()->change());
        DB::table('instant_requests')->where('status', 'searching')->update(['expires_at' => null]);
    }
};
