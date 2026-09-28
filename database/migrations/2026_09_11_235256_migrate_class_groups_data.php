<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_class_id_foreign');
        });
        $distinctNames = DB::table('manage_classes')
            ->select('name')
            ->distinct()
            ->pluck('name');

        foreach ($distinctNames as $name) {
            DB::table('class_groups')->updateOrInsert(
                ['name' => $name],
                ['status' => 'active', 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $groupIdsByName = DB::table('class_groups')->pluck('id', 'name');
        foreach ($groupIdsByName as $name => $groupId) {
            DB::table('manage_classes')
                ->where('name', $name)
                ->update(['class_group_id' => $groupId]);
        }

        $offerings = DB::table('manage_classes')->select('id', 'class_group_id')->get();
        foreach ($offerings as $row) {
            DB::table('users')
                ->where('role', 'student')
                ->where('class_id', $row->id)
                ->update(['class_id' => $row->class_group_id]);
        }

        DB::statement(
            'UPDATE users SET class_id = NULL
             WHERE class_id IS NOT NULL
               AND class_id NOT IN (SELECT id FROM class_groups)'
        );

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('class_id')
                  ->references('id')->on('class_groups')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_class_id_foreign');
        });

        $offeringsByGroup = DB::table('manage_classes')
            ->select('id', 'class_group_id')
            ->get()
            ->groupBy('class_group_id');

        foreach ($offeringsByGroup as $groupId => $rows) {
            if ($groupId === null) {
                continue;
            }
            $firstManageClassId = $rows->first()->id;
            DB::table('users')
                ->where('role', 'student')
                ->where('class_id', $groupId)
                ->update(['class_id' => $firstManageClassId]);
        }

        DB::table('manage_classes')->update(['class_group_id' => null]);

        // Restore the original foreign key back to manage_classes.
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('class_id')
                  ->references('id')->on('manage_classes')
                  ->onDelete('set null');
        });
    }
};