<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('manage_classes', function (Blueprint $table) {
            if (!Schema::hasColumn('manage_classes', 'teacher_id')) {
                $table->unsignedBigInteger('teacher_id')->nullable()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manage_classes', function (Blueprint $table) {
            $table->dropColumn('teacher_id');
        });
    }
};
