<?php

namespace App\Support;

use App\Models\Product;

/**
 * Satıcı ürünü panelden/AI ile "siler": vitrinden kalkar, admin kaydı görür (soft delete).
 */
class SellerProductSoftDelete
{
    public function hide(Product $product): void
    {
        $product->status = 0;
        $product->save();
        $product->delete();
    }
}
