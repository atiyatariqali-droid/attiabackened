<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            // Ye check lagao: sirf tab add karo jab column na ho
            if (!Schema::hasColumn('attendance', 'session_id')) {
                $table->unsignedBigInteger('session_id')->nullable()->after('status');
                $table->foreign('session_id')
                    ->references('id')
                    ->on('attendance_sessions')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            // Foreign key pehle drop hogi, phir column
            $table->dropForeign(['session_id']); 
            $table->dropColumn('session_id');
        });
    }
};