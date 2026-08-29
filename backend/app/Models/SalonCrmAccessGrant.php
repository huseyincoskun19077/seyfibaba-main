<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalonCrmAccessGrant extends Model
{
    public const TYPE_IMMEDIATE_UNLOCK = 'immediate_unlock';
    public const TYPE_NEXT_MONTH_CREDIT = 'next_month_credit';

    protected $table = 'salon_crm_access_grants';

    protected $fillable = [
        'salon_id',
        'period',
        'type',
        'qualified_amount',
    ];

    protected $casts = [
        'qualified_amount' => 'decimal:2',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(SalonCrmSalon::class, 'salon_id');
    }
}
