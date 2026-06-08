<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('teacher_id')->after('id');
            $table->unsignedBigInteger('class_id')->after('teacher_id');
            $table->timestamp('start_time')->after('class_id');
            $table->timestamp('end_time')->nullable()->after('start_time');
            $table->decimal('latitude', 10, 8)->after('end_time');
            $table->decimal('longitude', 11, 8)->after('latitude');
            $table->string('status')->default('active')->after('longitude');
        });
    }

    public function down()
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn(['teacher_id', 'class_id', 'start_time', 'end_time', 'latitude', 'longitude', 'status']);
        });
    }
};