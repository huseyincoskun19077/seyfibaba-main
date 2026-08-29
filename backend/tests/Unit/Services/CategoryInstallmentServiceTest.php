<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Services\CategoryInstallmentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class CategoryInstallmentServiceTest extends TestCase
{
    use UsesInMemorySqlite;

    private CategoryInstallmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();
        $this->service = new CategoryInstallmentService();

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->unsignedTinyInteger('max_installment')->nullable();
            $table->timestamps();
        });

        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->unsignedTinyInteger('max_installment')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('sub_category_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('sub_categories');
        Schema::dropIfExists('categories');
        parent::tearDown();
    }

    public function test_kozmetik_category_returns_single_installment(): void
    {
        $cat = Category::query()->create([
            'name' => 'Kozmetik',
            'slug' => 'kozmetik',
            'max_installment' => 1,
        ]);

        $product = Product::query()->create([
            'name' => 'Saç Kremi',
            'category_id' => $cat->id,
        ]);

        $this->assertSame(1, $this->service->maxInstallmentForProduct($product));
        $this->assertSame([1], $this->service->enabledInstallmentsForCart([
            ['product_id' => $product->id, 'qty' => 1],
        ]));
    }

    public function test_kuafor_equipment_returns_nine_installments(): void
    {
        $cat = Category::query()->create([
            'name' => 'Kuaför Ekipmanları',
            'slug' => 'kuafor-ekipmanlari',
            'max_installment' => 9,
        ]);

        $product = Product::query()->create([
            'name' => 'Berber Koltuğu',
            'category_id' => $cat->id,
        ]);

        $this->assertSame(9, $this->service->maxInstallmentForProduct($product));
        $enabled = $this->service->enabledInstallmentsForCart([
            ['product_id' => $product->id, 'qty' => 1],
        ]);
        $this->assertSame([1, 2, 3, 6, 9], $enabled);
    }

    public function test_twelve_installments_uses_iyzico_valid_set(): void
    {
        $cat = Category::query()->create([
            'name' => 'Dayanıklı',
            'slug' => 'dayanikli',
            'max_installment' => 12,
        ]);

        $product = Product::query()->create([
            'name' => 'Ürün',
            'category_id' => $cat->id,
        ]);

        $enabled = $this->service->enabledInstallmentsForCart([
            ['product_id' => $product->id, 'qty' => 1],
        ]);
        $this->assertSame([1, 2, 3, 6, 9, 12], $enabled);
    }

    public function test_mixed_cart_uses_most_restrictive_installment(): void
    {
        $kozmetik = Category::query()->create(['name' => 'Kozmetik', 'max_installment' => 1]);
        $ekipman = Category::query()->create(['name' => 'Kuaför Ekipmanları', 'max_installment' => 9]);

        $p1 = Product::query()->create(['name' => 'Şampuan', 'category_id' => $kozmetik->id]);
        $p2 = Product::query()->create(['name' => 'Koltuk', 'category_id' => $ekipman->id]);

        $enabled = $this->service->enabledInstallmentsForCart([
            ['product_id' => $p1->id, 'qty' => 1],
            ['product_id' => $p2->id, 'qty' => 1],
        ]);

        $this->assertSame([1], $enabled);
    }

    public function test_sub_category_overrides_main_category(): void
    {
        $cat = Category::query()->create(['name' => 'Elektronik', 'max_installment' => 9]);
        $sub = new SubCategory();
        $sub->category_id = $cat->id;
        $sub->name = 'Tablet';
        $sub->slug = 'tablet';
        $sub->max_installment = 6;
        $sub->save();

        $product = Product::query()->create([
            'name' => 'Android Tablet',
            'category_id' => $cat->id,
            'sub_category_id' => $sub->id,
        ]);

        $this->assertSame(6, $this->service->maxInstallmentForProduct($product));
    }

    public function test_null_max_installment_defaults_to_single_payment(): void
    {
        $cat = Category::query()->create(['name' => 'Diğer', 'max_installment' => null]);
        $product = Product::query()->create(['name' => 'Ürün', 'category_id' => $cat->id]);

        $this->assertSame(1, $this->service->maxInstallmentForProduct($product));
    }

    public function test_resolve_iyzico_category_for_kozmetik(): void
    {
        $cat = Category::query()->create(['name' => 'Kozmetik', 'max_installment' => 1]);
        $product = Product::query()->create(['name' => 'Krem', 'category_id' => $cat->id]);

        $resolved = $this->service->resolveIyzicoCategory($product);

        $this->assertSame('Kozmetik', $resolved['category_1']);
        $this->assertSame('Kisisel Bakim', $resolved['category_2']);
    }

    public function test_resolve_iyzico_category_for_kuafor_equipment(): void
    {
        $cat = Category::query()->create(['name' => 'Kuaför Malzemeleri', 'max_installment' => 9]);
        $product = Product::query()->create(['name' => 'Makas', 'category_id' => $cat->id]);

        $resolved = $this->service->resolveIyzicoCategory($product);

        $this->assertSame('Kucuk Ev Aletleri', $resolved['category_1']);
        $this->assertSame('Kuafor Ekipmanlari', $resolved['category_2']);
    }

    public function test_mobilya_category_returns_twelve_installments(): void
    {
        $cat = Category::query()->create([
            'name' => 'Kuaför Mobilyaları',
            'slug' => 'kuafor-mobilyalari',
            'max_installment' => 12,
        ]);

        $product = Product::query()->create([
            'name' => 'Berber Koltuğu',
            'category_id' => $cat->id,
        ]);

        $this->assertSame(12, $this->service->maxInstallmentForProduct($product));
        $enabled = $this->service->enabledInstallmentsForCart([
            ['product_id' => $product->id, 'qty' => 1],
        ]);
        $this->assertSame([1, 2, 3, 6, 9, 12], $enabled);
    }

    public function test_resolve_iyzico_category_for_mobilya(): void
    {
        $cat = Category::query()->create(['name' => 'Kuaför Mobilyaları', 'max_installment' => 12]);
        $product = Product::query()->create(['name' => 'Salon Koltuğu', 'category_id' => $cat->id]);

        $resolved = $this->service->resolveIyzicoCategory($product);

        $this->assertSame('Mobilya', $resolved['category_1']);
        $this->assertSame('Ev Mobilyalari', $resolved['category_2']);
    }

    public function test_mobilya_parent_with_ekipman_subcategory_stays_mobilya(): void
    {
        $cat = Category::query()->create(['name' => 'Kuaför Mobilyaları', 'max_installment' => 12]);
        $sub = new SubCategory();
        $sub->category_id = $cat->id;
        $sub->name = 'Salon Ekipmanları';
        $sub->slug = 'salon-ekipmanlari';
        $sub->max_installment = 12;
        $sub->save();

        $product = Product::query()->create([
            'name' => 'Tezgah',
            'category_id' => $cat->id,
            'sub_category_id' => $sub->id,
        ]);

        $resolved = $this->service->resolveIyzicoCategory($product);

        $this->assertSame('Mobilya', $resolved['category_1']);
        $this->assertSame('Ev Mobilyalari', $resolved['category_2']);
    }

    public function test_yedek_parca_category_returns_nine_installments(): void
    {
        $cat = Category::query()->create([
            'name' => 'Kuaför Yedek Parçaları',
            'slug' => 'kuafor-yedek-parcalari',
            'max_installment' => 9,
        ]);

        $product = Product::query()->create([
            'name' => 'Motor Parçası',
            'category_id' => $cat->id,
        ]);

        $this->assertSame(9, $this->service->maxInstallmentForProduct($product));
        $this->assertSame('dayanikli_kucuk_ev', $this->service->classifyRule($cat->name, $cat->slug));
    }

    public function test_sub_category_zero_max_installment_falls_back_to_parent(): void
    {
        $cat = Category::query()->create(['name' => 'Kuaför Mobilyaları', 'max_installment' => 12]);
        $sub = new SubCategory();
        $sub->category_id = $cat->id;
        $sub->name = 'Berber Koltukları';
        $sub->slug = 'berber-koltuklari';
        $sub->max_installment = 0;
        $sub->save();

        $product = Product::query()->create([
            'name' => 'Koltuk',
            'category_id' => $cat->id,
            'sub_category_id' => $sub->id,
        ]);

        $this->assertSame(12, $this->service->maxInstallmentForProduct($product));
    }
}
