<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ManageClass extends Model
{
    use HasFactory;
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