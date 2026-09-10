<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSofttrProductMap extends Model
{
    protected $table = 'vendor_softtr_product_maps';

    protected $fillable = [
        'vendor_id',
        'softtr_product_id',
        'product_id',
        'softtr_sku',
        'softtr_barcode',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
