<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('pending_students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('class');
            $table->string('roll_no');
            $table->enum('student_status', ['Active','Inactive','Struck Out','On Leave']);
            $table->unsignedBigInteger('teacher_id');
            $table->enum('approval_status', ['pending','approved','rejected'])->default('pending');
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('pending_students');
    }
};