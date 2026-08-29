<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalonCrmStaffService extends Model
{
    protected $table = 'salon_crm_staff_services';

    protected $fillable = [
        'salon_id',
        'staff_id',
        'service_id',
        'price',
        'duration_minutes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(SalonCrmSalon::class, 'salon_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(SalonCrmStaff::class, 'staff_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(SalonCrmService::class, 'service_id');
    }
}
