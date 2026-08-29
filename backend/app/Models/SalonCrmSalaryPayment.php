<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalonCrmSalaryPayment extends Model
{
    protected $table = 'salon_crm_salary_payments';

    protected $fillable = [
        'salon_id',
        'staff_id',
        'pay_type',
        'pay_period',
        'period_key',
        'suggested_amount',
        'amount',
        'status',
        'owner_confirmed_at',
        'staff_confirmed_at',
        'paid_at',
        'ledger_entry_id',
        'notes',
    ];

    protected $casts = [
        'suggested_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'owner_confirmed_at' => 'datetime',
        'staff_confirmed_at' => 'datetime',
        'paid_at' => 'datetime',
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
