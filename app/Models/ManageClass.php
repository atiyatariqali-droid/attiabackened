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
        'students_count',
        'subject',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students()
    {
        return $this->hasMany(User::class, 'class_id');
    }
}