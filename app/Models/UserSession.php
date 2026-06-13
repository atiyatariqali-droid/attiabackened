<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\UserSession;
class UserSession extends Model
{
      protected $table = 'user_sessions';

    protected $fillable = [
        'user_id',
        'device_id',
        'ip_address',
        'latitude',
        'longitude',
        'login_time',
        'logout_time',
        'is_active'
    ];
}
