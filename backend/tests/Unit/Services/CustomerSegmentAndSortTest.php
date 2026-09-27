<?php

namespace Tests\Unit\Services;

use App\Services\CustomerSegmentService;
use App\Support\ProductFilterHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;
use App\Models\Product;

class CustomerSegmentAndSortTest extends TestCase
{
    use UsesInMemorySqlite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->default(0);
            $table->string('name')->nullable();
            $table->integer('status')->default(1);
            $table->integer('approve_by_admin')->default(1);
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('offer_price', 10, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function test_hair_care_maps_to_both_women_and_men(): void
    {
        $svc = new CustomerSegmentService();
        $codes = $svc->suggestCodesForNames('sac bakimi', 'kozmetik');
        $this->assertContains('women_salon', $codes);
        $this->assertContains('men_barber', $codes);
    }

    public function test_nail_maps_to_nail_and_beauty_not_locked(): void
    {
        $svc = new CustomerSegmentService();
        $codes = $svc->suggestCodesForNames('tirnak', 'kozmetik');
        $this->assertContains('nail', $codes);
        $this->assertContains('beauty_salon', $codes);
    }

    public function test_furniture_berber_chair_maps_men_only(): void
    {
        $svc = new CustomerSegmentService();
        $codes = $svc->suggestCodesForNames('', 'kuafor mobilyalari', 'profesyonel erkek berber koltugu');
        $this->assertSame(['men_barber'], $codes);
    }

    public function test_furniture_ambiguous_returns_empty(): void
    {
        $svc = new CustomerSegmentService();
        $codes = $svc->suggestCodesForNames('', 'kuafor mobilyalari', 'kuafor koltugu hidrolik');
        $this->assertSame([], $codes);
    }

    public function test_furniture_sepha_typo_maps_shared_not_bulk_sub(): void
    {
        $svc = new CustomerSegmentService();
        $this->assertSame(['shared'], $svc->suggestCodesForFurnitureName('sepha m5520'));
        $this->assertSame(['shared'], $svc->suggestCodesForFurnitureName('etejer e728'));
        $this->assertSame(['shared'], $svc->suggestCodesForFurnitureName('komidin m7001'));
        // Üst kategori mobilya + ürün adı yok → alt otomatik boş (admin)
        $this->assertSame([], $svc->suggestCodesForNames('etejerler', 'kuafor mobilyalari', null));
        $review = $svc->reviewSubMapping('Etejerler', 'Kuaför Mobilyaları', 10);
        $this->assertSame('admin_review', $review['confidence']);
    }

    public function test_recommended_sort_interleaves_vendors_across_pages(): void
    {
        for ($i = 1; $i <= 100; $i++) {
            Product::query()->create([
                'vendor_id' => 1,
                'name' => 'A'.$i,
                'status' => 1,
                'approve_by_admin' => 1,
                'price' => 10,
            ]);
        }
        for ($i = 1; $i <= 40; $i++) {
            Product::query()->create([
                'vendor_id' => 2,
                'name' => 'B'.$i,
                'status' => 1,
                'approve_by_admin' => 1,
                'price' => 10,
            ]);
        }
        for ($i = 1; $i <= 20; $i++) {
            Product::query()->create([
                'vendor_id' => 3,
                'name' => 'C'.$i,
                'status' => 1,
                'approve_by_admin' => 1,
                'price' => 10,
            ]);
        }

        $query = Product::query()->where('status', 1);
        ProductFilterHelper::applySorting($query, '');
        $this->assertStringContainsString('ROW_NUMBER()', $query->toSql());

        $ids = $query->pluck('id')->all();
        $page1 = array_slice($ids, 0, 24);
        $page2 = array_slice($ids, 24, 24);
        $page3 = array_slice($ids, 48, 24);

        $this->assertSame([], array_intersect($page1, $page2));
        $this->assertSame([], array_intersect($page2, $page3));
        $this->assertSame([], array_intersect($page1, $page3));

        $v1count = Product::query()->whereIn('id', $page1)->where('vendor_id', 1)->count();
        $this->assertLessThan(24, $v1count, 'İlk sayfa yalnızca tek satıcıdan oluşmamalı');
        $this->assertGreaterThan(0, Product::query()->whereIn('id', $page1)->where('vendor_id', 2)->count());
        $this->assertGreaterThan(0, Product::query()->whereIn('id', $page1)->where('vendor_id', 3)->count());

        // Offset kararlılığı (aynı pluck sırası)
        $q2 = Product::query()->where('status', 1);
        ProductFilterHelper::applySorting($q2, '');
        $this->assertSame($ids, $q2->pluck('id')->all());

        $priceQ = Product::query()->where('status', 1);
        ProductFilterHelper::applySorting($priceQ, '2');
        $this->assertStringContainsString('offer_price', $priceQ->toSql());
        $this->assertStringNotContainsString('ROW_NUMBER()', $priceQ->toSql());
    }

    public function test_recommended_sort_with_three_vendors_in_filtered_category(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable();
        });

        for ($v = 1; $v <= 3; $v++) {
            for ($i = 1; $i <= 30; $i++) {
                Product::query()->create([
                    'vendor_id' => $v,
                    'category_id' => 1,
                    'name' => "F{$v}-{$i}",
                    'status' => 1,
                    'approve_by_admin' => 1,
                    'price' => 10 + $v,
                ]);
            }
        }
        Product::query()->create([
            'vendor_id' => 9,
            'category_id' => 99,
            'name' => 'Other',
            'status' => 1,
            'approve_by_admin' => 1,
            'price' => 1,
        ]);

        $q = Product::query()->where('status', 1)->where('category_id', 1);
        ProductFilterHelper::applySorting($q, '');
        $ids = $q->pluck('id')->all();
        $page1 = array_slice($ids, 0, 24);
        $page2 = array_slice($ids, 24, 24);
        $page3 = array_slice($ids, 48, 24);
        $this->assertSame([], array_intersect($page1, $page2));
        $this->assertSame([], array_intersect($page2, $page3));
        $vendors = Product::query()->whereIn('id', $page1)->pluck('vendor_id')->unique()->sort()->values()->all();
        $this->assertSame([1, 2, 3], $vendors);

        $q1 = Product::query()->where('vendor_id', 1);
        ProductFilterHelper::applySorting($q1, '');
        $only = $q1->limit(24)->pluck('vendor_id')->unique()->all();
        $this->assertSame([1], $only);

        // Fiyat artan — filtreli
        $priceQ = Product::query()->where('category_id', 1);
        ProductFilterHelper::applySorting($priceQ, '2');
        $prices = $priceQ->limit(20)->get()->map(function ($p) {
            $o = (float) $p->offer_price;

            return $o > 0 ? $o : (float) $p->price;
        })->all();
        $sorted = $prices;
        sort($sorted, SORT_NUMERIC);
        $this->assertSame($sorted, $prices);
    }
}
