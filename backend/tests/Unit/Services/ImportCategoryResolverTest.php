<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Vendor;
use App\Services\ImportCategoryResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class ImportCategoryResolverTest extends TestCase
{
    use UsesInMemorySqlite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('child_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sub_category_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('logo')->nullable();
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_type')->nullable();
            $table->boolean('is_admin_created')->default(false);
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->default(1);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('openai_enabled')->default(false);
            $table->boolean('claude_enabled')->default(false);
            $table->timestamps();
        });
        \Illuminate\Support\Facades\DB::table('settings')->insert([
            'openai_enabled' => false,
            'claude_enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $catId = Category::query()->insertGetId(['name' => 'Kuaför Ekipmanları', 'created_at' => now(), 'updated_at' => now()]);
        SubCategory::query()->insert(['category_id' => $catId, 'name' => 'Berber Koltukları', 'created_at' => now(), 'updated_at' => now()]);
        SubCategory::query()->insert(['category_id' => $catId, 'name' => 'Fön Makineleri', 'created_at' => now(), 'updated_at' => now()]);

        $waxCatId = Category::query()->insertGetId(['name' => 'Ağda ve Epilasyon Ürünleri', 'created_at' => now(), 'updated_at' => now()]);
        SubCategory::query()->insert(['category_id' => $waxCatId, 'name' => 'Kartuş Ağdalar', 'created_at' => now(), 'updated_at' => now()]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('child_categories');
        Schema::dropIfExists('sub_categories');
        Schema::dropIfExists('categories');
        parent::tearDown();
    }

    public function test_fuzzy_category_match_maps_berber_koltugu_to_kuafor_ekipmanlari(): void
    {
        $resolver = app(ImportCategoryResolver::class);

        $result = $resolver->resolve(
            'Profesyonel Erkek Berber Koltuğu',
            'berber koltugu',
        );

        $this->assertNotNull($result['category']);
        $this->assertSame('Kuaför Ekipmanları', $result['category']->name);
    }

    public function test_creates_brand_when_not_found_for_seller(): void
    {
        $vendor = Vendor::query()->create(['user_id' => 1]);
        $resolver = app(ImportCategoryResolver::class);

        $result = $resolver->resolve(
            'Profesyonel Makas',
            'Kuaför Ekipmanları',
            brandInput: 'OtomatikMarka',
            vendor: $vendor,
        );

        $this->assertNotNull($result['brand']);
        $this->assertSame('OtomatikMarka', $result['brand']->name);
        $this->assertSame($vendor->id, $result['brand']->created_by);
        $this->assertSame(Vendor::class, $result['brand']->created_by_type);
    }

    public function test_fuzzy_sub_category_match_under_main_category(): void
    {
        $resolver = app(ImportCategoryResolver::class);

        $result = $resolver->resolve(
            'Depilissima Azulen Kartuş Ağda',
            'Ağda ve Epilasyon Ürünleri',
            'Kartus Agdalar',
        );

        $this->assertNotNull($result['category']);
        $this->assertSame('Ağda ve Epilasyon Ürünleri', $result['category']->name);
        $this->assertNotNull($result['sub_category']);
        $this->assertSame('Kartuş Ağdalar', $result['sub_category']->name);
    }

    public function test_child_category_input_can_match_sub_category_when_sub_empty(): void
    {
        $resolver = app(ImportCategoryResolver::class);

        $result = $resolver->resolve(
            'Salon Berber Koltuğu',
            'Kuaför Ekipmanları',
            null,
            'Berber Koltukları',
        );

        $this->assertNotNull($result['sub_category']);
        $this->assertSame('Berber Koltukları', $result['sub_category']->name);
    }

    public function test_global_sub_category_match_can_fix_main_category(): void
    {
        $resolver = app(ImportCategoryResolver::class);

        $result = $resolver->resolve(
            'Depilissima Azulen Kartuş Ağda',
            'Kuaför Ekipmanları',
            'Kartuş Ağdalar',
        );

        $this->assertNotNull($result['sub_category']);
        $this->assertSame('Kartuş Ağdalar', $result['sub_category']->name);
        $this->assertSame('Ağda ve Epilasyon Ürünleri', $result['category']->name);
    }
}
