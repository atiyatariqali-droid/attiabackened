<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;
    protected $table = 'attendance';

    protected $fillable = [
        'student_id',
        'class_id',
        'attendance_date',
        'status',
        'session_id',
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