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
    ];
}
