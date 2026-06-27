<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('confirmation_requests', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('session_id');
        $table->enum('status', ['pending', 'closed'])->default('pending');
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();

        $table->foreign('session_id')
              ->references('id')
              ->on('attendance_sessions')
              ->onDelete('cascade');
    });
}

    public function down(): void
    {
        Schema::dropIfExists('confirmation_requests'); {
            $table->id();
            $table->timestamps();
        }
    }
};