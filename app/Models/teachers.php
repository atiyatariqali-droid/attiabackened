<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class teachers extends Model
{
    protected $table = "users";

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
    ];
}
