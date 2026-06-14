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
        'device_mac_address',
    ];

    // ✅ Hide sensitive fields from API responses
    protected $hidden = [
        'password',
    ];

    // ✅ Cast status to integer for consistency
    protected $casts = [
        'status' => 'integer',
    ];
}