<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Students extends Model
{
    protected $table = "users";

    protected $fillable = [
        'username',
        'email',
        'password',
        'phone',
        'role',
        'status',
        'class_id',
        'roll_no',
    ];

    protected $with = ['classGroup'];

    protected $appends = ['class', 'class_name'];

    // class_id now points at class_groups.id (the physical class), NOT a
    // specific manage_classes (subject-offering) row. This is what makes a
    // student's roster shared across every subject of their class.
    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class, 'class_id');
    }

    public function getClassNameAttribute()
    {
        return $this->classGroup ? $this->classGroup->name : null;
    }

    public function getClassAttribute()
    {
        return $this->classGroup ? $this->classGroup->name : null;
    }
}