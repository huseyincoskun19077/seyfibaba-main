<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ProductVariant extends Model
{
    use HasFactory;

    public function variantItems()
    {
        return $this->hasMany(ProductVariantItem::class);
    }

    public function activeVariantItems()
    {
        $columns = ['product_variant_id', 'name', 'price', 'id'];
        if (Schema::hasColumn('product_variant_items', 'image')) {
            $columns[] = 'image';
        }

        return $this->hasMany(ProductVariantItem::class)->select($columns);
    }
}
