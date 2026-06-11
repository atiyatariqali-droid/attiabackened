<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'sessions';

    public $timestamps = false;

    protected $fillable = [
        'teacher_id',
        'class_id', 
        'start_time',
        'end_time',
        'latitude',
        'longitude',
        'status'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];
}