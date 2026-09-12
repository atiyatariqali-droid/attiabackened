<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each manage_classes row (a specific class+subject+teacher offering)
        // now points at the physical class_group it belongs to.
        Schema::table('manage_classes', function (Blueprint $table) {
            $table->foreignId('class_group_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('class_groups')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('manage_classes', function (Blueprint $table) {
            $table->dropForeign(['class_group_id']);
            $table->dropColumn('class_group_id');
        });
    }
};