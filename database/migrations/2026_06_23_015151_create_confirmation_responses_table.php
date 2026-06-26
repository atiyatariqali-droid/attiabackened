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
        Schema::create('confirmation_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('student_id');
            $table->enum('response', ['yes', 'no']);
            $table->timestamps();
           
            $table->unique(['request_id', 'student_id']); // one response per student
        $table->foreign('request_id')
              ->references('id')
              ->on('confirmation_requests')
              ->onDelete('cascade');
        $table->foreign('student_id')
              ->references('id')
              ->on('users')
              ->onDelete('cascade');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confirmation_responses');
    }
};
