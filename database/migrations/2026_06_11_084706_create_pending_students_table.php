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
         Schema::create('pending_students', function (Blueprint $table) {
             $table->id();
             $table->string('name');
             $table->string('class');
             $table->string('roll_no');
             $table->string('student_status')->default('Active');
             $table->unsignedBigInteger('teacher_id')->nullable();
             $table->string('approval_status')->default('pending');
             $table->timestamps();
         });
     }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_students');
    }
};
