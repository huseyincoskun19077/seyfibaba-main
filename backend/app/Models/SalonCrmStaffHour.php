<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalonCrmStaffHour extends Model
{
    protected $table = 'salon_crm_staff_hours';

    protected $fillable = [
        'salon_id',
        'staff_id',
        'weekday',
        'start_time',
        'end_time',
        'is_off',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'is_off' => 'boolean',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(SalonCrmSalon::class, 'salon_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(SalonCrmStaff::class, 'staff_id');
    }
}
