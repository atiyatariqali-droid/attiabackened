<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use app\Models\SystemSetting;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'description'];
}