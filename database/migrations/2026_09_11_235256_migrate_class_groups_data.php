<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── STEP 1: One class_groups row per DISTINCT class name. ──
        // This is the key step: two manage_classes rows that share the same
        // "name" (e.g. two "BS Zoology" rows for different subjects) collapse
        // into ONE class_group — meaning they'll share ONE student roster.
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

        // ── STEP 2: Point every manage_classes row at its class_group. ──
        $groupIdsByName = DB::table('class_groups')->pluck('id', 'name');
        foreach ($groupIdsByName as $name => $groupId) {
            DB::table('manage_classes')
                ->where('name', $name)
                ->update(['class_group_id' => $groupId]);
        }

        // ── STEP 3: Re-point students.class_id. ──
        // Previously students.class_id pointed at a specific manage_classes
        // row (one class+subject+teacher). Now it must point at the shared
        // class_groups.id instead, so the student shows up under every
        // subject-offering of their physical class.
        $offerings = DB::table('manage_classes')->select('id', 'class_group_id')->get();
        foreach ($offerings as $row) {
            DB::table('users')
                ->where('role', 'student')
                ->where('class_id', $row->id)
                ->update(['class_id' => $row->class_group_id]);
        }
    }

    public function down(): void
    {
        // Best-effort revert: send each student back to the FIRST
        // manage_classes row under their class_group, then clear the
        // class_group_id links. (If you added brand-new subject rows
        // after running up(), reverting won't perfectly undo those.)
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
    }
};