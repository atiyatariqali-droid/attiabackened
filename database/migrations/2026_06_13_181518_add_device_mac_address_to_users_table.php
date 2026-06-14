<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX: up() was completely empty — device_mac_address column was never
     *      added to the users table. Every add/update request that sent
     *      this field was silently ignored or caused a DB column error.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only add the column if it doesn't already exist
            if (!Schema::hasColumn('users', 'device_mac_address')) {
                $table->string('device_mac_address')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'device_mac_address')) {
                $table->dropColumn('device_mac_address');
            }
        });
    }
};