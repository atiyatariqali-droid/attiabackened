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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('session_id');
            $table->text('message');
            $table->string('type')->default('attendance_marked');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->foreign('student_id')
              ->references('id')
              ->on('users')
              ->onDelete('cascade');

            $table->foreign('session_id')
              ->references('id')
              ->on('attendance_sessions')
              ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};