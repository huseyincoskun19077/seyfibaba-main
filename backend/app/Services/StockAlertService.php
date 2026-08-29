<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;

class StockAlertService
{
    public static function relativeThreshold(Product $product, ?Setting $setting = null): int
    {
        $setting = $setting ?? Setting::query()->first();
        $percent = (int) ($setting->low_stock_relative_percent ?? 20);
        $minQty = (int) ($setting->low_stock_min_qty ?? 1);
        $initialQty = (int) ($product->initial_qty ?? $product->qty ?? 0);

        if ($initialQty <= 0) {
            return $minQty;
        }

        return max($minQty, (int) floor($initialQty * $percent / 100));
    }
}
