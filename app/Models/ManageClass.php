<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManageClass extends Model
{
    protected $table = "manage_classes";

    protected $fillable = [
        'name',
        'status',
    ];

}
