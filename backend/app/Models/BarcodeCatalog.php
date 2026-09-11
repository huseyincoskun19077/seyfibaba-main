<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Durable barcode master catalog. Independent from seller products —
 * seller delete never removes these rows.
 */
class BarcodeCatalog extends Model
{
    protected $table = 'barcode_catalog';

    protected $guarded = [];

    protected $casts = [
        'weight' => 'float',
        'usage_count' => 'integer',
        'category_id' => 'integer',
        'sub_category_id' => 'integer',
        'child_category_id' => 'integer',
        'brand_id' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    public function childCategory()
    {
        return $this->belongsTo(ChildCategory::class, 'child_category_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }
}
