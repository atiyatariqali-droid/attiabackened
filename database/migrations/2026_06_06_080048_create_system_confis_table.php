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
        Schema::create('system_confis', function (Blueprint $table) {
            $table->id();
            $table->decimal('longitude', 10, 7);
            $table->decimal('latitude', 10, 7);
            $table->string('school_name');
            $table->text('school_address');
            $table->string('school_contact');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_confis');
    }
};
