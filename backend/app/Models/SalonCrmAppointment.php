<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalonCrmAppointment extends Model
{
    protected $table = 'salon_crm_appointments';

    protected $fillable = [
        'salon_id',
        'customer_id',
        'staff_id',
        'service_id',
        'service_name',
        'customer_name',
        'customer_phone',
        'starts_at',
        'duration_minutes',
        'price',
        'status',
        'notes',
        'is_block',
        'block_type',
        'payment_method',
        'payment_status',
        'commission_percent',
        'staff_share',
        'owner_share',
        'reminder_sent_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'duration_minutes' => 'integer',
        'price' => 'decimal:2',
        'is_block' => 'boolean',
        'commission_percent' => 'decimal:2',
        'staff_share' => 'decimal:2',
        'owner_share' => 'decimal:2',
        'reminder_sent_at' => 'datetime',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(SalonCrmSalon::class, 'salon_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(SalonCrmCustomer::class, 'customer_id');
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
