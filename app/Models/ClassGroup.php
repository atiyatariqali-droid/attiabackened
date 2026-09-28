<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassGroup extends Model
{
    protected $table = 'class_groups';

    protected $fillable = [
        'name',
        'status',
    ];

    public function offerings()
    {
        return $this->hasMany(ManageClass::class, 'class_group_id');
    }

    public function students()
    {
        return $this->hasMany(User::class, 'class_id');
    }
}