<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasApiTokens;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'latitude',
        'longitude',
        'status',
        'class_id',
        'roll_no',
        'phone',
        'device_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['class', 'class_name'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    protected function redirectTo($request)
    {
        return null;
    }

    /**
     * Get the class the student belongs to
     */
    public function manageClass()
    {
        return $this->belongsTo(ManageClass::class, 'class_id');
    }

    /**
     * Get the class name for frontend compatibility
     */
    public function getClassNameAttribute()
    {
        return $this->manageClass ? $this->manageClass->name : null;
    }

    /**
     * Get the class name for legacy frontend compatibility which maps json['class']
     */
    public function getClassAttribute()
    {
        return $this->manageClass ? $this->manageClass->name : null;
    }
}
