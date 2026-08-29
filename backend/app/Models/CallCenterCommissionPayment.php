<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallCenterCommissionPayment extends Model
{
    protected $fillable = [
        'vendor_id',
        'admin_id',
        'amount',
        'paid_by_admin_id',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'paid_by_admin_id');
    }
}
