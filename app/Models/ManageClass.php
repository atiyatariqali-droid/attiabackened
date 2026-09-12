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
        'class_group_id', // NEW: links this subject-offering to its shared physical class
        'teacher_id',
        'subject',
        'students_count',
        'status',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // The physical class (e.g. "BS Zoology") this subject-offering belongs
    // to. Multiple ManageClass rows (different subjects/teachers) can share
    // one ClassGroup — and therefore share one roster of students.
    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class, 'class_group_id');
    }

    // NOTE: students are no longer linked directly to a ManageClass row.
    // They belong to the shared ClassGroup instead. To get this
    // offering's roster, go through classGroup: $this->classGroup->students
    // (see ManageClassController@list / StudentsController for examples).
}