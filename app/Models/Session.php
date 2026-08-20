<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Session extends Model
{
    use HasFactory;
    protected $table = 'attendance_sessions';

    public $timestamps = false;

    protected $fillable = [
        'teacher_id',
        'class_id', 
        'start_time',
        'end_time',
        'latitude',
        'longitude',
        'radius',
        'status'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];
    public function teacher()
{
    return $this->belongsTo(Teachers::class, 'teacher_id');
}
}