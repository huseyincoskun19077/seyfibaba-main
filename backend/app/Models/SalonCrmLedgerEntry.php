<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalonCrmLedgerEntry extends Model
{
    protected $table = 'salon_crm_ledger_entries';

    protected $fillable = [
        'salon_id',
        'staff_id',
        'appointment_id',
        'type',
        'category',
        'payment_method',
        'title',
        'amount',
        'staff_share',
        'owner_share',
        'entry_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'staff_share' => 'decimal:2',
        'owner_share' => 'decimal:2',
        'entry_date' => 'date',
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
