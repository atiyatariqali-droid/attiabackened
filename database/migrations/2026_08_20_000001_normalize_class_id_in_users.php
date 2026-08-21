<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add class_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('class_id')->nullable()->after('status');
            $table->foreign('class_id')->references('id')->on('manage_classes')->nullOnDelete();
        });

        // 2. Map existing students to class_id
        $users = DB::table('users')->where('role', 'student')->whereNotNull('class')->get();
        foreach ($users as $user) {
            // Find class by name or class_name
            $classId = DB::table('manage_classes')
                ->where('name', $user->class)
                ->orWhere('class_name', $user->class)
                ->value('id');
            
            if ($classId) {
                DB::table('users')->where('id', $user->id)->update(['class_id' => $classId]);
            }
        }

        // 3. Drop 'class' and 'teacher_id' from users
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'class')) {
                $table->dropColumn('class');
            }
            if (Schema::hasColumn('users', 'teacher_id')) {
                $table->dropForeign(['teacher_id']);
                $table->dropColumn('teacher_id');
            }
        });

        // 4. Drop redundant 'class_name' from manage_classes
        // Note: Make sure to map data if 'name' was empty.
        $classes = DB::table('manage_classes')->get();
        foreach ($classes as $c) {
            if (empty($c->name) && !empty($c->class_name)) {
                DB::table('manage_classes')->where('id', $c->id)->update(['name' => $c->class_name]);
            }
        }
        
        Schema::table('manage_classes', function (Blueprint $table) {
            if (Schema::hasColumn('manage_classes', 'class_name')) {
                $table->dropColumn('class_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manage_classes', function (Blueprint $table) {
            $table->string('class_name')->nullable()->after('name');
        });
        
        // restore class_name from name
        DB::statement('UPDATE manage_classes SET class_name = name');

        Schema::table('users', function (Blueprint $table) {
            $table->string('class')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
        });

        $users = DB::table('users')->whereNotNull('class_id')->get();
        foreach ($users as $user) {
            $className = DB::table('manage_classes')->where('id', $user->class_id)->value('name');
            DB::table('users')->where('id', $user->id)->update(['class' => $className]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropColumn('class_id');
        });
    }
};
