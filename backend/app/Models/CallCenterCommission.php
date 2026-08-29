<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallCenterCommission extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    protected $fillable = [
        'vendor_id',
        'admin_id',
        'product_count',
        'calculated_total',
        'approved_amount',
        'paid_total',
        'status',
        'breakdown',
        'agent_approved_at',
    ];

    protected $casts = [
        'calculated_total' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'paid_total' => 'decimal:2',
        'breakdown' => 'array',
        'agent_approved_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CallCenterCommissionPayment::class, 'vendor_id', 'vendor_id');
    }

    public function pendingApprovalAmount(): float
    {
        return max(0, (float) $this->calculated_total - (float) $this->paid_total);
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === self::STATUS_AWAITING_PAYMENT
            && $this->approved_amount !== null
            && (float) $this->approved_amount > 0;
    }
}
