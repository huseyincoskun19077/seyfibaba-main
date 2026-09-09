<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSentosOrderMap extends Model
{
    protected $table = 'vendor_sentos_order_maps';

    protected $fillable = [
        'vendor_id',
        'order_id',
        'sentos_external_order_id',
        'sentos_order_id',
        'sentos_order_code',
        'last_sentos_status',
        'last_sync_status',
        'last_sync_message',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
