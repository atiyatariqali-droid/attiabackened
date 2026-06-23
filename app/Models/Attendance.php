<?php

namespace App\Models;
use App\Models\Students;


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
    public function student()
{
    return $this->belongsTo(Students::class, 'student_id');
}
public function session()
{
    return $this->belongsTo(\App\Models\Session::class, 'session_id');
}
}
