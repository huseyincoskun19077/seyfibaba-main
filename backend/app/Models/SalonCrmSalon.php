<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalonCrmSalon extends Model
{
    protected $table = 'salon_crm_salons';

    protected $fillable = [
        'user_id',
        'name',
        'owner_name',
        'owner_username',
        'join_code',
        'owner_password',
        'api_token',
        'fcm_token',
        'type',
        'phone',
        'logo_image',
        'cover_image',
        'profile_text',
        'show_profile_to_customers',
        'open_hour',
        'close_hour',
        'trial_ends_at',
        'threshold_amount',
        'admin_free_until',
        'admin_notes',
    ];

    protected $hidden = [
        'owner_password',
        'api_token',
        'fcm_token',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'admin_free_until' => 'datetime',
        'threshold_amount' => 'integer',
        'show_profile_to_customers' => 'boolean',
        'open_hour' => 'integer',
        'close_hour' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grants(): HasMany
    {
        return $this->hasMany(SalonCrmAccessGrant::class, 'salon_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(SalonCrmStaff::class, 'salon_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(SalonCrmService::class, 'salon_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(SalonCrmAppointment::class, 'salon_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(SalonCrmCustomer::class, 'salon_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(SalonCrmLedgerEntry::class, 'salon_id');
    }
}
