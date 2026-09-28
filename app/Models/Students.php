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