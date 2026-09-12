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

    // All subject-offerings (manage_classes rows) taught under this
    // physical class — e.g. "BS Zoology (Botony)" and "BS Zoology (ICT)"
    // both belong to the same ClassGroup.
    public function offerings()
    {
        return $this->hasMany(ManageClass::class, 'class_group_id');
    }

    // All students enrolled in this physical class — shared across
    // every subject-offering above.
    public function students()
    {
        return $this->hasMany(User::class, 'class_id');
    }
}