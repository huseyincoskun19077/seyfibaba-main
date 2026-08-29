<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'otp_code',
        'purpose',
        'attempts',
        'max_attempts',
        'expires_at',
        'verified_at',
        'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
    ];

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    public function isVerified()
    {
        return !is_null($this->verified_at);
    }

    public function hasExceededAttempts()
    {
        return $this->attempts >= $this->max_attempts;
    }

    public function hasAttemptsRemaining()
    {
        return $this->attempts < $this->max_attempts;
    }

    public function markVerified()
    {
        $this->verified_at = now();
        $this->save();
    }

    /**
     * Scope a query to only include active (not expired, not verified) OTPs.
     */
    public function scopeActive($query, string $phone, string $purpose)
    {
        return $query->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now());
    }
}
