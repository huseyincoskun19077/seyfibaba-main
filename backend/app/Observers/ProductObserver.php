<?php

namespace App\Observers;

use App\Models\Admin;
use App\Models\Product;
use App\Models\Setting;
use App\Notifications\StockAlertNotification;
use App\Services\StockAlertService;

class ProductObserver
{
    public function created(Product $product): void
    {
        if ($product->initial_qty === null && $product->qty !== null) {
            $product->forceFill(['initial_qty' => (int) $product->qty])->saveQuietly();
        }
    }

    public function updated(Product $product): void
    {
        if (! $product->wasChanged('qty')) {
            return;
        }

        $setting = Setting::query()->first();
        if (! $setting || ! $setting->stock_alert_enabled) {
            return;
        }

        $threshold = StockAlertService::relativeThreshold($product, $setting);

        if ($product->qty > $threshold) {
            return;
        }

        $oldQty = $product->getOriginal('qty');
        if ($oldQty !== null && $oldQty <= $threshold) {
            return;
        }

        $vendor = $product->seller;
        if ($vendor && $vendor->user) {
            $vendor->user->notify(new StockAlertNotification($product, $threshold));
        }

        $admin = Admin::query()->first();
        if ($admin) {
            $admin->notify(new StockAlertNotification($product, $threshold));
        }
    }
}
