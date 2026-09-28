<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class ManageClass extends Model
{
    use HasFactory;
    protected $table = "manage_classes";

    protected $fillable = [
        'name',
        'class_group_id',
        'teacher_id',
        'subject',
        'students_count',
        'status',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class, 'class_group_id');
    }

}