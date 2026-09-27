<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSegmentProduct extends Model
{
    protected $fillable = [
        'customer_segment_id',
        'product_id',
        'serial',
        'is_featured',
        'is_forced',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_forced' => 'boolean',
        'serial' => 'integer',
        'product_id' => 'integer',
    ];

    public function segment(): BelongsTo
    {
        return $this->belongsTo(CustomerSegment::class, 'customer_segment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
