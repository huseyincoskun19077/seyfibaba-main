<?php

namespace Tests\Unit\Services;

use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use App\Services\CartPriceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class CartPriceServiceTest extends TestCase
{
    use UsesInMemorySqlite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('flash_sale_products');
        Schema::dropIfExists('flash_sales');
        Schema::dropIfExists('product_variant_items');
        Schema::dropIfExists('products');
        parent::tearDown();
    }

    public function test_uses_current_product_price_not_stale_snapshot(): void
    {
        $productId = $this->insertProduct(100, 80);

        $service = new CartPriceService();
        $result = $service->refreshCartItems([
            [
                'product_id' => $productId,
                'qty' => 2,
                'variant_item_ids' => [],
                'previous_unit_price' => 80,
            ],
        ]);

        $this->assertFalse($result['has_price_changes']);
        $this->assertSame(160.0, $result['subtotal']);
        $this->assertSame(80.0, $result['items'][0]['unit_price']);
        $this->assertSame(160.0, $result['items'][0]['line_total']);
    }

    public function test_detects_price_change(): void
    {
        $productId = $this->insertProduct(200, null);

        $service = new CartPriceService();
        $result = $service->refreshCartItems([
            [
                'product_id' => $productId,
                'qty' => 1,
                'variant_item_ids' => [],
                'previous_unit_price' => 100,
            ],
        ]);

        $this->assertTrue($result['has_price_changes']);
        $this->assertSame(200.0, $result['items'][0]['unit_price']);
    }

    protected function insertProduct(float $price, ?float $offerPrice): int
    {
        return (int) \DB::table('products')->insertGetId([
            'name' => 'Test',
            'slug' => 'test',
            'price' => $price,
            'offer_price' => $offerPrice,
            'thumb_image' => 'uploads/test.jpg',
            'vendor_id' => 1,
            'qty' => 10,
            'status' => 1,
            'approve_by_admin' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createTables(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('offer_price', 12, 2)->nullable();
            $table->string('thumb_image')->nullable();
            $table->unsignedBigInteger('vendor_id')->default(0);
            $table->integer('qty')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('approve_by_admin')->default(1);
            $table->timestamps();
        });

        Schema::create('product_variant_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->string('product_variant_name')->nullable();
            $table->string('name')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->integer('status')->default(1);
            $table->decimal('offer', 8, 2)->default(0);
            $table->timestamp('end_time')->nullable();
            $table->timestamps();
        });

        Schema::create('flash_sale_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }
}
