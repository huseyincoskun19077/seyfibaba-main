<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Admin extends Authenticatable implements JWTSubject
{
    use Notifiable;

    public const TYPE_ADMIN = 0;

    public const TYPE_SUPER = 1;

    public const TYPE_CALL_CENTER = 2;

    protected $fillable = [
        'name', 'email', 'password', 'forget_password_token', 'image', 'status', 'admin_type', 'slug', 'about_us',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'admin_type' => 'integer',
        'status' => 'integer',
    ];

    public function isSuperAdmin(): bool
    {
        return (int) $this->admin_type === self::TYPE_SUPER;
    }

    public function isCallCenterAgent(): bool
    {
        return (int) $this->admin_type === self::TYPE_CALL_CENTER;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
