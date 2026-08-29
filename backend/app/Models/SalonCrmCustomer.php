<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalonCrmCustomer extends Model
{
    protected $table = 'salon_crm_customers';

    protected $fillable = [
        'salon_id',
        'staff_id',
        'name',
        'phone',
        'password',
        'api_token',
        'fcm_token',
        'notes',
    ];

    protected $hidden = [
        'password',
        'api_token',
        'fcm_token',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(SalonCrmSalon::class, 'salon_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(SalonCrmAppointment::class, 'customer_id');
    }
}
