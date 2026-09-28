<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorShippingTier extends Model
{
    protected $table = 'vendor_shipping_tiers';

    protected $guarded = [];

    protected $casts = [
        'min_amount' => 'float',
        'max_amount' => 'float',
        'shipping_fee' => 'float',
        'sort_order' => 'integer',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}
