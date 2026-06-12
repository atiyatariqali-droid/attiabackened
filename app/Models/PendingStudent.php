<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingStudent extends Model
{
    protected $table = 'pending_stesters';
    protected $table = 'pending_students';

    protected $fillable = [
        'name',
        'class',
        'roll_no',
        'student_status',
        'teacher_id',
        'approval_status',
    ];

    // ✅ Yeh add kar do taake created_at auto manage ho
    public $timestamps = true;

    // Optional: date format ke liye
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}