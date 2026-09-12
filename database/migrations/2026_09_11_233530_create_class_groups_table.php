<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // class_groups = the PHYSICAL class (e.g. "BS Zoology"). Students
        // enroll here. A class group can have multiple subject-offerings
        // (manage_classes rows) under it — each with its own teacher/subject —
        // but they all share this ONE roster of students.
        Schema::create('class_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_groups');
    }
};