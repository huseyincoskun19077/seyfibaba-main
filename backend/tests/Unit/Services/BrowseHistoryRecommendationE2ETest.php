<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\User\ProductViewController;
use App\Models\GuestProductView;
use App\Models\Product;
use App\Models\RecommendationSetting;
use App\Models\User;
use App\Models\UserProductView;
use App\Services\PersonalizationProductService;
use App\Services\RecommendationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

/**
 * Gezinme geçmişi → Size Özel öneri akışı (uçtan uca, SQLite bellek).
 * Canlı DB kullanmaz.
 */
class BrowseHistoryRecommendationE2ETest extends TestCase
{
    use UsesInMemorySqlite;

    private RecommendationService $rec;

    private array $report = [];

    /** @var array<string, Product> */
    private array $catalog = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();
        $this->buildSchema();
        $this->seedCatalog();
        RecommendationSetting::current();
        $this->rec = new RecommendationService();
    }

    public function test_browse_history_recommendation_flow_end_to_end(): void
    {
        // 1) Giriş yapmış, işletme türü Erkek Kuaförü / Berber
        $user = User::query()->create([
            'name' => 'Berber Test Kullanıcı',
            'email' => 'berber-e2e@test.local',
            'password' => bcrypt('secret'),
        ]);
        $user->forceFill([
            'business_type' => 'male_hairdresser',
            'business_types' => ['male_hairdresser'],
            'personalization_enabled' => true,
        ])->save();
        $user->refresh();

        $this->assertSame(['male_hairdresser'], $this->rec->userBusinessTypeCodes($user));
        $this->assertTrue($this->rec->personalizationAllowed($user));

        // 2) İlk Size Özel — işletme türü etkili; tırnak popüleri ilk sırada olmamalı
        $beforeIds = $this->rec->recommendProductIds($user, null, null, null, null, false, 8);
        $beforeScores = $this->rec->recommendScoreMap($user);
        $beforeSnap = $this->snapshot($beforeIds, $beforeScores, 'ÖNCE (geçmiş yok, işletme türü berber)');
        $this->report[] = $beforeSnap;

        $this->assertNotEmpty($beforeIds, 'Başlangıç önerisi boş olmamalı');
        $top3Before = array_slice($beforeIds, 0, 3);
        $top3Cats = Product::query()->whereIn('id', $top3Before)->pluck('category_id')->map(fn ($c) => (int) $c)->all();
        foreach ($top3Cats as $cat) {
            $this->assertContains($cat, [10, 20, 40], 'İlk 3 öneri berber veya ortak salon kategorisinde olmalı');
        }
        $this->assertNotContains(30, $top3Cats, 'Popüler oje / tırnak ilk 3’te olmamalı');
        $this->assertNotSame(
            (int) $this->catalog['nail_popular']->id,
            (int) ($beforeIds[0] ?? 0),
            'İlk öneri Popüler Oje Seti olmamalı'
        );

        // Ortak salon ürünü listede yer alabilsin (erişim engeli yok)
        $this->assertTrue(
            in_array((int) $this->catalog['generic_a']->id, $beforeIds, true)
            || ((float) ($beforeScores[(int) $this->catalog['generic_a']->id] ?? 0) > 0),
            'Ortak salon ürünü skorlanabilmeli'
        );

        $beforeTopVendors = $this->vendorsOf($beforeIds);

        // 3) Berber koltuğu + tıraş makinesi görüntüle (2x, 31 dk arayla → sinyal)
        $chair = $this->catalog['chair_viewed'];
        $clipper = $this->catalog['clipper_viewed'];

        $this->viewAsUser($user, (int) $chair->id);
        $this->travel(31)->minutes();
        $this->viewAsUser($user, (int) $chair->id);
        $this->travel(31)->minutes();
        $this->viewAsUser($user, (int) $clipper->id);
        $this->travel(31)->minutes();
        $this->viewAsUser($user, (int) $clipper->id);

        $hist = UserProductView::query()->where('user_id', $user->id)->orderBy('product_id')->get();
        $this->assertCount(2, $hist);
        $this->assertSame(2, (int) $hist->firstWhere('product_id', $chair->id)->view_count);
        $this->assertSame(2, (int) $hist->firstWhere('product_id', $clipper->id)->view_count);

        // 4) Size Özel yeniden
        $afterIds = $this->rec->recommendProductIds($user, null, null, null, null, false, 8);
        $afterScores = $this->rec->recommendScoreMap($user);
        $afterSnap = $this->snapshot($afterIds, $afterScores, 'SONRA (koltuk+makine geçmişi)');
        $this->report[] = $afterSnap;

        // Görüntülenen ürünler tekrar etmesin
        $this->assertNotContains((int) $chair->id, $afterIds, 'Görüntülenen koltuk Size Özel’de tekrarlanmamalı');
        $this->assertNotContains((int) $clipper->id, $afterIds, 'Görüntülenen makine Size Özel’de tekrarlanmamalı');

        // Alternatif koltuk / makine skorları yükselsin
        $altChair = $this->catalog['chair_alt_v2'];
        $altClipper = $this->catalog['clipper_alt_v3'];
        $this->assertGreaterThan(
            (float) ($beforeScores[(int) $altChair->id] ?? 0),
            (float) ($afterScores[(int) $altChair->id] ?? 0),
            'Alternatif koltuk skoru yükselmeli'
        );
        $this->assertGreaterThan(
            (float) ($beforeScores[(int) $altClipper->id] ?? 0),
            (float) ($afterScores[(int) $altClipper->id] ?? 0),
            'Alternatif tıraş makinesi skoru yükselmeli'
        );

        // Üst önerilerde berber kategorileri hakim olsun
        $afterCats = Product::query()->whereIn('id', array_slice($afterIds, 0, 5))->pluck('category_id')->all();
        $berberCats = [10, 20]; // mobilya + alet
        $berberInTop = count(array_filter($afterCats, fn ($c) => in_array((int) $c, $berberCats, true)));
        $this->assertGreaterThanOrEqual(3, $berberInTop, 'İlk 5 önerinin çoğu berber ilgili kategoride olmalı');

        // Farklı satıcılar
        $afterVendors = $this->vendorsOf($afterIds);
        $this->assertGreaterThan(1, count(array_unique($afterVendors)), 'Önerilerde birden fazla satıcı olmalı');

        $this->report[] = [
            'label' => 'PUAN DEĞİŞİMİ (seçili)',
            'rows' => [
                [
                    'id' => (int) $altChair->id,
                    'name' => $altChair->name,
                    'before' => round((float) ($beforeScores[(int) $altChair->id] ?? 0), 3),
                    'after' => round((float) ($afterScores[(int) $altChair->id] ?? 0), 3),
                    'delta' => round((float) ($afterScores[(int) $altChair->id] ?? 0) - (float) ($beforeScores[(int) $altChair->id] ?? 0), 3),
                ],
                [
                    'id' => (int) $altClipper->id,
                    'name' => $altClipper->name,
                    'before' => round((float) ($beforeScores[(int) $altClipper->id] ?? 0), 3),
                    'after' => round((float) ($afterScores[(int) $altClipper->id] ?? 0), 3),
                    'delta' => round((float) ($afterScores[(int) $altClipper->id] ?? 0) - (float) ($beforeScores[(int) $altClipper->id] ?? 0), 3),
                ],
                [
                    'id' => (int) $this->catalog['nail_popular']->id,
                    'name' => $this->catalog['nail_popular']->name,
                    'before' => round((float) ($beforeScores[(int) $this->catalog['nail_popular']->id] ?? 0), 3),
                    'after' => round((float) ($afterScores[(int) $this->catalog['nail_popular']->id] ?? 0), 3),
                    'delta' => round((float) ($afterScores[(int) $this->catalog['nail_popular']->id] ?? 0) - (float) ($beforeScores[(int) $this->catalog['nail_popular']->id] ?? 0), 3),
                ],
            ],
        ];

        // 5) Tek ilgisiz tırnak tıklaması → ağırlıklı tırnak dönüşü olmasın
        $nail = $this->catalog['nail_click'];
        $this->travel(31)->minutes();
        $this->viewAsUser($user, (int) $nail->id); // view_count=1 < min_views=2

        $afterNailIds = $this->rec->recommendProductIds($user, null, null, null, null, false, 8);
        $afterNailScores = $this->rec->recommendScoreMap($user);
        $this->report[] = $this->snapshot($afterNailIds, $afterNailScores, 'TEK TIRNAK TIKLAMASI SONRASI');

        $nailView = UserProductView::query()
            ->where('user_id', $user->id)
            ->where('product_id', $nail->id)
            ->first();
        $this->assertSame(1, (int) $nailView->view_count);

        $nailAltScore = (float) ($afterNailScores[(int) $this->catalog['nail_alt']->id] ?? 0);
        $chairAltScore = (float) ($afterNailScores[(int) $altChair->id] ?? 0);
        $this->assertGreaterThan(
            $nailAltScore,
            $chairAltScore,
            'Tek tırnak tıklaması tırnak alternatiflerini berber alternatiflerinin üstüne çıkarmamalı'
        );

        $top5 = array_slice($afterNailIds, 0, 5);
        $nailCatCount = Product::query()->whereIn('id', $top5)->where('category_id', 30)->count();
        $this->assertLessThanOrEqual(1, $nailCatCount, 'İlk 5 öneri ağırlıklı tırnak olmamalı');

        // 6a) Geçmiş sil
        $deleted = $this->rec->clearUserHistory($user);
        $this->assertGreaterThan(0, $deleted);
        $this->assertSame(0, UserProductView::query()->where('user_id', $user->id)->count());

        $clearedIds = $this->rec->recommendProductIds($user, null, null, null, null, false, 8);
        $clearedScores = $this->rec->recommendScoreMap($user);
        $this->report[] = $this->snapshot($clearedIds, $clearedScores, 'GEÇMİŞ SİLİNDİKTEN SONRA');

        $this->assertEqualsWithDelta(
            (float) ($beforeScores[(int) $altChair->id] ?? 0),
            (float) ($clearedScores[(int) $altChair->id] ?? 0),
            0.01,
            'Geçmiş silinince alternatif koltuk skoru başlangıca dönmeli'
        );

        // 6b) Kişiselleştirmeyi kapat
        $user->forceFill(['personalization_enabled' => false])->save();
        $user->refresh();
        $this->assertFalse($this->rec->personalizationAllowed($user));

        // Kapalıyken yeni görüntüleme kaydedilmez
        $this->travel(31)->minutes();
        $this->viewAsUser($user, (int) $chair->id);
        $this->assertSame(0, UserProductView::query()->where('user_id', $user->id)->count());

        $disabledIds = $this->rec->recommendProductIds($user, null, null, null, null, false, 8);
        $disabledScores = $this->rec->recommendScoreMap($user);
        $this->report[] = $this->snapshot($disabledIds, $disabledScores, 'KİŞİSELLEŞTİRME KAPALI');

        // PersonalizationProductService → popüler fallback
        $payload = (new PersonalizationProductService())->productsForUser($user, 8, 'home');
        $this->assertSame('popular', $payload['source']);
        $this->assertSame('Popüler ürünler', $payload['title']);

        // 7) Misafir — izin yokken geçmiş kullanılmaz
        $user->forceFill(['personalization_enabled' => true])->save();
        $guestKey = 'guest_e2e_key_1';

        $anonBaseline = $this->rec->recommendScoreMap(null, null, null, null, null, false);
        $this->rec->recordGuestView($guestKey, (int) $chair->id, false); // consent yok
        $this->assertSame(0, GuestProductView::query()->count());

        $noConsentScores = $this->rec->recommendScoreMap(null, null, null, null, $guestKey, false);
        $this->assertEqualsWithDelta(
            (float) ($anonBaseline[(int) $altChair->id] ?? 0),
            (float) ($noConsentScores[(int) $altChair->id] ?? 0),
            0.01,
            'İzinsiz misafir geçmişi skora etki etmemeli'
        );

        // İzinli misafir geçmişi etki eder
        $this->rec->recordGuestView($guestKey, (int) $chair->id, true);
        $this->travel(31)->minutes();
        $this->rec->recordGuestView($guestKey, (int) $chair->id, true);
        $guestAfterScores = $this->rec->recommendScoreMap(null, null, null, null, $guestKey, true);
        $this->assertGreaterThan(
            (float) ($noConsentScores[(int) $altChair->id] ?? 0),
            (float) ($guestAfterScores[(int) $altChair->id] ?? 0)
        );
        $this->report[] = [
            'label' => 'MİSAFİR',
            'anon_alt_chair_score' => round((float) ($anonBaseline[(int) $altChair->id] ?? 0), 3),
            'consent_false_alt_chair_score' => round((float) ($noConsentScores[(int) $altChair->id] ?? 0), 3),
            'consent_true_alt_chair_score' => round((float) ($guestAfterScores[(int) $altChair->id] ?? 0), 3),
            'guest_rows' => GuestProductView::query()->get(['guest_key', 'product_id', 'view_count'])->toArray(),
        ];

        // API: görüntüleme oturum kullanıcısına bağlanır + yenileme şişirmez
        $this->assertApiViewBindingAndDedup();

        // Raporu stdout’a bas (phpunit çıktısında görünsün)
        fwrite(STDOUT, "\n===== SIZE ÖZEL E2E RAPOR =====\n");
        fwrite(STDOUT, json_encode($this->report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n");
        fwrite(STDOUT, "before_ids=".json_encode($beforeIds)."\n");
        fwrite(STDOUT, "after_ids=".json_encode($afterIds)."\n");
        fwrite(STDOUT, "after_nail_ids=".json_encode($afterNailIds)."\n");
        fwrite(STDOUT, "cleared_ids=".json_encode($clearedIds)."\n");
        fwrite(STDOUT, "before_vendors=".json_encode($beforeTopVendors)." after_vendors=".json_encode($afterVendors)."\n");
    }

    private function assertApiViewBindingAndDedup(): void
    {
        $user = User::query()->create([
            'name' => 'API Viewer',
            'email' => 'api-viewer@test.local',
            'password' => bcrypt('secret'),
        ]);
        $user->forceFill(['personalization_enabled' => true])->save();

        Auth::guard('api')->setUser($user);

        $product = $this->catalog['chair_alt_v2'];
        $controller = app(ProductViewController::class);

        $req1 = Request::create('/api/user/product-view', 'POST', ['product_id' => $product->id]);
        $req1->setUserResolver(fn () => $user);
        $res1 = $controller->store($req1, $this->rec);
        $this->assertSame(200, $res1->getStatusCode());

        $row = UserProductView::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();
        $this->assertNotNull($row, 'Görüntüleme API kaydı user_id ile bağlanmalı');
        $this->assertSame(1, (int) $row->view_count);

        // Aynı sayfa yenilemesi (30 dk içinde) → view_count artmaz
        $req2 = Request::create('/api/user/product-view', 'POST', ['product_id' => $product->id]);
        $req2->setUserResolver(fn () => $user);
        $controller->store($req2, $this->rec);
        $controller->store($req2, $this->rec);
        $row->refresh();
        $this->assertSame(1, (int) $row->view_count, 'Sayfa yenilemesi yapay yüksek ilgi üretmemeli');

        // Misafir consent=false API
        $guestReq = Request::create('/api/guest-product-view', 'POST', [
            'product_id' => $product->id,
            'guest_key' => 'g_no_consent',
            'consent' => false,
        ]);
        $guestRes = $controller->storeGuest($guestReq, $this->rec);
        $this->assertFalse($guestRes->getData(true)['success'] ?? true);
        $this->assertSame(
            0,
            GuestProductView::query()->where('guest_key', 'g_no_consent')->count()
        );

        $this->report[] = [
            'label' => 'API GÖRÜNTÜLEME',
            'user_id' => $user->id,
            'product_id' => $product->id,
            'view_count_after_3_posts_same_window' => (int) $row->view_count,
            'guest_no_consent_rows' => 0,
        ];
    }

    private function viewAsUser(User $user, int $productId): void
    {
        $this->rec->recordUserView($user, $productId);
    }

    /**
     * @param  list<int>  $ids
     * @param  array<int,float>  $scores
     */
    private function snapshot(array $ids, array $scores, string $label): array
    {
        $rows = [];
        foreach ($ids as $rank => $id) {
            $p = Product::query()->find($id);
            $rows[] = [
                'rank' => $rank + 1,
                'id' => (int) $id,
                'name' => $p?->name,
                'category_id' => (int) ($p?->category_id ?? 0),
                'sub_category_id' => (int) ($p?->sub_category_id ?? 0),
                'vendor_id' => (int) ($p?->vendor_id ?? 0),
                'score' => round((float) ($scores[(int) $id] ?? 0), 3),
            ];
        }

        return ['label' => $label, 'products' => $rows];
    }

    /** @param list<int> $ids @return list<int> */
    private function vendorsOf(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $map = Product::query()->whereIn('id', $ids)->pluck('vendor_id', 'id');
        $out = [];
        foreach ($ids as $id) {
            $out[] = (int) ($map[$id] ?? 0);
        }

        return $out;
    }

    private function buildSchema(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('business_type')->nullable();
            $table->text('business_types')->nullable();
            $table->boolean('personalization_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->default(0);
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('sub_category_id')->nullable();
            $table->unsignedBigInteger('child_category_id')->nullable();
            $table->string('name')->nullable();
            $table->integer('status')->default(1);
            $table->integer('approve_by_admin')->default(1);
            $table->integer('sold_qty')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('offer_price', 10, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('user_product_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('view_count')->default(1);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('guest_product_views', function (Blueprint $table) {
            $table->id();
            $table->string('guest_key', 64);
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('view_count')->default(1);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();
            $table->unique(['guest_key', 'product_id']);
        });

        Schema::create('recommendation_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weight_segment')->default(40);
            $table->unsignedTinyInteger('weight_browse_history')->default(30);
            $table->unsignedTinyInteger('weight_business_type')->default(20);
            $table->unsignedTinyInteger('weight_popularity')->default(10);
            $table->unsignedSmallInteger('history_days')->default(30);
            $table->unsignedTinyInteger('min_views_for_signal')->default(2);
            $table->unsignedTinyInteger('vendor_diversity')->default(3);
            $table->boolean('exclude_viewed_product')->default(true);
            $table->timestamps();
        });

        Schema::create('customer_segments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_name')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->integer('serial')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_guest_home')->default(false);
            $table->boolean('is_primary_home')->default(false);
            $table->integer('vendor_diversity')->default(3);
            $table->integer('home_product_limit')->default(12);
            $table->string('business_type_key')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_segment_taxonomies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_segment_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('sub_category_id')->nullable();
            $table->unsignedBigInteger('child_category_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customer_segment_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_segment_id');
            $table->unsignedBigInteger('product_id');
            $table->boolean('is_featured')->default(false);
            $table->integer('serial')->default(0);
            $table->timestamps();
        });

        Schema::create('personalization_showcases', function (Blueprint $table) {
            $table->id();
            $table->string('business_type')->nullable();
            $table->string('title')->nullable();
            $table->boolean('status')->default(false);
            $table->boolean('include_high_views')->default(false);
            $table->text('category_ids')->nullable();
            $table->text('product_ids')->nullable();
            $table->text('vendor_ids')->nullable();
            $table->text('opening_category_ids')->nullable();
            $table->text('opening_product_ids')->nullable();
            $table->text('opening_vendor_ids')->nullable();
            $table->integer('home_limit')->nullable();
            $table->timestamps();
        });
    }

    private function seedCatalog(): void
    {
        // Kategori adları suggestCodesForNames için gerekli
        \Illuminate\Support\Facades\DB::table('categories')->insert([
            ['id' => 10, 'name' => 'Kuaför Mobilyaları', 'slug' => 'kuafor-mobilyalari', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 20, 'name' => 'Kozmetik', 'slug' => 'kozmetik', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 30, 'name' => 'Kozmetik', 'slug' => 'kozmetik-2', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 40, 'name' => 'Kozmetik', 'slug' => 'kozmetik-3', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        \Illuminate\Support\Facades\DB::table('sub_categories')->insert([
            ['id' => 11, 'category_id' => 10, 'name' => 'Berber koltukları', 'slug' => 'berber-koltuk', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 21, 'category_id' => 20, 'name' => 'Tıraş & berber malzemeleri', 'slug' => 'tiras-berber', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 31, 'category_id' => 30, 'name' => 'Tırnak', 'slug' => 'tirnak', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 41, 'category_id' => 40, 'name' => 'Salon sarf malzemeleri', 'slug' => 'salon-sarf', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // cat 10 = mobilya/koltuk, sub 11
        // cat 20 = alet/tıraş, sub 21
        // cat 30 = tırnak, sub 31
        // cat 40 = ortak sarf
        $defs = [
            'chair_viewed' => ['Berber Koltuğu Hidrolik A', 1, 10, 11, 5],
            'chair_alt_v2' => ['Berber Koltuğu Alternatif B', 2, 10, 11, 4],
            'chair_alt_v3' => ['Berber Koltuğu Alternatif C', 3, 10, 11, 3],
            'clipper_viewed' => ['Tıraş Makinesi Pro X', 1, 20, 21, 6],
            'clipper_alt_v2' => ['Tıraş Makinesi Alternatif Y', 2, 20, 21, 5],
            'clipper_alt_v3' => ['Tıraş Makinesi Alternatif Z', 3, 20, 21, 4],
            'nail_popular' => ['Popüler Oje Seti', 9, 30, 31, 50],
            'nail_click' => ['Jel Tırnak Kiti', 9, 30, 31, 8],
            'nail_alt' => ['Manikür Seti Alternatif', 8, 30, 31, 7],
            'generic_a' => ['Salon Havlu Paketi', 4, 40, 41, 20],
            'generic_b' => ['Dezenfektan Spreyi', 5, 40, 41, 18],
            'generic_c' => ['Boyun Bandı', 6, 40, 41, 15],
        ];

        foreach ($defs as $key => [$name, $vendor, $cat, $sub, $sold]) {
            $this->catalog[$key] = Product::query()->create([
                'name' => $name,
                'vendor_id' => $vendor,
                'category_id' => $cat,
                'sub_category_id' => $sub,
                'status' => 1,
                'approve_by_admin' => 1,
                'sold_qty' => $sold,
                'price' => 100,
                'offer_price' => 90,
            ]);
        }
    }
}
