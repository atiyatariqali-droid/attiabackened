<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfi extends Model
{
    protected $fillable = [
        'longitude',
        'latitude',
        'school_name',
        'school_address',
        'school_contact'
    ];
}
