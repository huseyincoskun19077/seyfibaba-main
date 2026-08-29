<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Services\StockAlertService;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    public function test_relative_stock_threshold_calculation(): void
    {
        $setting = new Setting([
            'low_stock_relative_percent' => 20,
            'low_stock_min_qty' => 1,
        ]);

        $product = new Product([
            'qty' => 2,
            'initial_qty' => 10,
        ]);

        $this->assertSame(2, StockAlertService::relativeThreshold($product, $setting));
    }
}
