<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSentosCategoryMap extends Model
{
    protected $table = 'vendor_sentos_category_maps';

    protected $fillable = [
        'vendor_id',
        'sentos_category_key',
        'sentos_category_name',
        'category_id',
        'sub_category_id',
        'child_category_id',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
