<?php

namespace Tests\Feature\Recommendations;

use App\Models\Product;
use App\Models\RecommendationSetting;
use App\Models\User;
use App\Models\UserProductView;
use App\Services\RecommendationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * JWT middleware zinciri: POST /api/user/product-view → doğru user_id.
 * Tarayıcı (useProductViewTracker) bu testte doğrulanmaz.
 */
class ProductViewJwtApiTest extends TestCase
{
    use UsesInMemorySqlite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->boolean('personalization_enabled')->default(true);
            $table->string('business_type')->nullable();
            $table->text('business_types')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->default(0);
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('sub_category_id')->nullable();
            $table->string('name')->nullable();
            $table->integer('status')->default(1);
            $table->integer('approve_by_admin')->default(1);
            $table->integer('sold_qty')->default(0);
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

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_segments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
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
            $table->timestamps();
        });

        RecommendationSetting::current();
    }

    public function test_unauthenticated_product_view_is_rejected(): void
    {
        $product = Product::query()->create([
            'name' => 'Berber Koltuğu',
            'vendor_id' => 1,
            'category_id' => 10,
            'sub_category_id' => 11,
            'status' => 1,
            'approve_by_admin' => 1,
        ]);

        $this->postJson('/api/user/product-view', ['product_id' => $product->id])
            ->assertStatus(401);

        $this->assertSame(0, UserProductView::query()->count());
    }

    public function test_jwt_product_view_binds_to_authenticated_user_and_dedups_refresh(): void
    {
        $userA = User::query()->create([
            'name' => 'Kullanıcı A',
            'email' => 'a-jwt@test.local',
            'password' => bcrypt('secret'),
        ]);
        $userA->forceFill([
            'personalization_enabled' => true,
            'business_type' => 'male_hairdresser',
            'business_types' => ['male_hairdresser'],
        ])->save();

        $userB = User::query()->create([
            'name' => 'Kullanıcı B',
            'email' => 'b-jwt@test.local',
            'password' => bcrypt('secret'),
        ]);
        $userB->forceFill(['personalization_enabled' => true])->save();

        $product = Product::query()->create([
            'name' => 'Tıraş Makinesi Pro',
            'vendor_id' => 2,
            'category_id' => 20,
            'sub_category_id' => 21,
            'status' => 1,
            'approve_by_admin' => 1,
            'sold_qty' => 3,
        ]);

        $tokenA = JWTAuth::fromUser($userA);

        $res = $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/user/product-view', ['product_id' => $product->id]);

        $res->assertOk()->assertJson(['success' => true]);

        $row = UserProductView::query()->first();
        $this->assertNotNull($row);
        $this->assertSame((int) $userA->id, (int) $row->user_id);
        $this->assertNotSame((int) $userB->id, (int) $row->user_id);
        $this->assertSame(1, (int) $row->view_count);

        // Aynı JWT ile yenileme (dedup penceresi)
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/user/product-view', ['product_id' => $product->id])
            ->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/user/product-view', ['product_id' => $product->id])
            ->assertOk();

        $row->refresh();
        $this->assertSame(1, (int) $row->view_count);
        $this->assertSame(1, UserProductView::query()->count());
    }

    public function test_guest_without_consent_does_not_store_view(): void
    {
        $product = Product::query()->create([
            'name' => 'Berber Makas',
            'vendor_id' => 1,
            'status' => 1,
            'approve_by_admin' => 1,
        ]);

        $this->postJson('/api/guest-product-view', [
            'product_id' => $product->id,
            'guest_key' => 'guest_jwt_test',
            'consent' => false,
        ])->assertOk()->assertJson(['success' => false]);

        $this->assertDatabaseMissing('guest_product_views', ['guest_key' => 'guest_jwt_test']);
    }

    public function test_recommendations_change_after_repeated_views_via_service(): void
    {
        \Illuminate\Support\Facades\DB::table('categories')->insert([
            ['id' => 20, 'name' => 'Kozmetik', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 30, 'name' => 'Kozmetik', 'created_at' => now(), 'updated_at' => now()],
        ]);
        \Illuminate\Support\Facades\DB::table('sub_categories')->insert([
            ['id' => 21, 'category_id' => 20, 'name' => 'Tıraş & berber malzemeleri', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 31, 'category_id' => 30, 'name' => 'Tırnak', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $user = User::query()->create([
            'name' => 'Berber',
            'email' => 'berber-jwt@test.local',
            'password' => bcrypt('secret'),
        ]);
        $user->forceFill([
            'personalization_enabled' => true,
            'business_types' => ['male_hairdresser'],
            'business_type' => 'male_hairdresser',
        ])->save();

        $clipper = Product::query()->create([
            'name' => 'Tıraş Makinesi Görüntülenen',
            'vendor_id' => 1,
            'category_id' => 20,
            'sub_category_id' => 21,
            'status' => 1,
            'approve_by_admin' => 1,
            'sold_qty' => 2,
        ]);
        $alt = Product::query()->create([
            'name' => 'Tıraş Makinesi Alternatif',
            'vendor_id' => 2,
            'category_id' => 20,
            'sub_category_id' => 21,
            'status' => 1,
            'approve_by_admin' => 1,
            'sold_qty' => 1,
        ]);
        Product::query()->create([
            'name' => 'Popüler Oje Seti',
            'vendor_id' => 9,
            'category_id' => 30,
            'sub_category_id' => 31,
            'status' => 1,
            'approve_by_admin' => 1,
            'sold_qty' => 99,
        ]);

        $rec = app(RecommendationService::class);
        $before = $rec->recommendProductIds($user->fresh(), null, null, null, null, false, 5);
        $beforeScoreAlt = $rec->recommendScoreMap($user->fresh())[(int) $alt->id] ?? 0;

        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/user/product-view', ['product_id' => $clipper->id])
            ->assertOk();
        $this->travel(31)->minutes();
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/user/product-view', ['product_id' => $clipper->id])
            ->assertOk();

        $after = $rec->recommendProductIds($user->fresh(), null, null, null, null, false, 5);
        $afterScoreAlt = $rec->recommendScoreMap($user->fresh())[(int) $alt->id] ?? 0;

        $this->assertGreaterThan($beforeScoreAlt, $afterScoreAlt);
        $this->assertNotContains((int) $clipper->id, $after);
        $this->assertContains((int) $alt->id, $after);
        $this->assertNotSame($before, $after);
    }
}
