<?php

namespace Tests\Unit\Support;

use App\Support\OrderQuantityHelper;
use Tests\TestCase;

class OrderQuantityHelperTest extends TestCase
{
    public function test_from_cart_products_sums_line_quantities(): void
    {
        $total = OrderQuantityHelper::fromCartProducts([
            ['product_id' => 1, 'qty' => 2],
            ['product_id' => 2, 'qty' => 3],
        ]);

        $this->assertSame(5, $total);
    }

    public function test_from_cart_products_defaults_missing_qty_to_one(): void
    {
        $total = OrderQuantityHelper::fromCartProducts([
            ['product_id' => 1],
            ['product_id' => 2, 'qty' => 2],
        ]);

        $this->assertSame(3, $total);
    }

    public function test_from_order_products_sums_quantities(): void
    {
        $total = OrderQuantityHelper::fromOrderProducts([
            (object) ['qty' => 1],
            (object) ['qty' => 4],
        ]);

        $this->assertSame(5, $total);
    }
}
