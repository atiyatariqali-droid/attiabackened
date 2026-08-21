<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teachers extends Model
{
    protected $table = "users";

    protected $fillable = [
        'username',
        'email',
        'password',
        'phone',
        'role',
        'status',
        'device_id',
    ];

    //  Hide sensitive fields from API responses
    protected $hidden = [
        'password',
    ];

    //  Cast status to integer for consistency
    protected $casts = [
        'status' => 'integer',
    ];

    protected $with = ['managedClasses'];
    protected $appends = ['class_name', 'department', 'active_classes'];

    public function managedClasses()
    {
        return $this->hasMany(ManageClass::class, 'teacher_id');
    }

    public function getClassNameAttribute()
    {
        return $this->managedClasses->pluck('name')->implode(', ');
    }

    public function getDepartmentAttribute()
    {
        // Frontend expects department or class names to show here
        $classes = $this->getClassNameAttribute();
        return empty($classes) ? 'N/A' : $classes;
    }

    public function getActiveClassesAttribute()
    {
        return $this->managedClasses->count();
    }
}