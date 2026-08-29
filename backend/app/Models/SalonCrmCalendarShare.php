<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalonCrmCalendarShare extends Model
{
    public const HORIZON_TODAY_TOMORROW = 'today_tomorrow';
    public const HORIZON_WEEK = 'week';
    public const HORIZON_MONTH = 'month';

    protected $table = 'salon_crm_calendar_shares';

    protected $fillable = [
        'salon_id',
        'staff_id',
        'token',
        'horizon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
