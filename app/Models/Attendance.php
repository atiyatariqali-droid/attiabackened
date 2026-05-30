<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
     protected $table = 'attendance';

    protected $fillable = [

        'student_id',
        'class_id',
        'attendance_date',
        'status'
    ];
}
