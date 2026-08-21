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

    protected $with = ['manageClass'];

    protected $appends = ['class', 'class_name'];

    public function manageClass()
    {
        return $this->belongsTo(ManageClass::class, 'class_id');
    }

    public function getClassNameAttribute()
    {
        return $this->manageClass ? $this->manageClass->name : null;
    }

    public function getClassAttribute()
    {
        return $this->manageClass ? $this->manageClass->name : null;
    }
}
