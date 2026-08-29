<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalonCrmStaff extends Model
{
    protected $table = 'salon_crm_staff';

    protected $fillable = [
        'salon_id',
        'name',
        'photo',
        'show_photo_to_customers',
        'username',
        'password',
        'api_token',
        'fcm_token',
        'is_active',
        'commission_percent',
        'pay_type',
        'pay_period',
        'salary_amount',
    ];

    protected $hidden = [
        'password',
        'api_token',
        'fcm_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_photo_to_customers' => 'boolean',
        'commission_percent' => 'decimal:2',
        'salary_amount' => 'decimal:2',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(SalonCrmSalon::class, 'salon_id');
    }

    public function staffServices(): HasMany
    {
        return $this->hasMany(SalonCrmStaffService::class, 'staff_id');
    }

    public function salaryPayments(): HasMany
    {
        return $this->hasMany(SalonCrmSalaryPayment::class, 'staff_id');
    }

    public function hours(): HasMany
    {
        return $this->hasMany(SalonCrmStaffHour::class, 'staff_id');
    }
}
