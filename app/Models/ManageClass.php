<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManageClass extends Model
{
    protected $table = "manage_classes";

    protected $fillable = [
        'name',
        'teacher_id', // NEW: proper foreign key to the assigned teacher
        'status',
        'class_name',
        'students_count',
    ];


    public function teacher()
    {
        return $this->belongsTo(Teachers::class, 'teacher_id');
    }
}