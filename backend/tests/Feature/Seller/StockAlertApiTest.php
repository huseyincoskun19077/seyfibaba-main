<?php

namespace Tests\Feature\Seller;

use App\Models\Admin;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class StockAlertApiTest extends TestCase
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
            $table->timestamps();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('status')->default(1);
            $table->string('shop_name')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->integer('low_stock_threshold')->default(5);
            $table->boolean('stock_alert_enabled')->default(true);
            $table->integer('low_stock_relative_percent')->default(20);
            $table->integer('low_stock_min_qty')->default(1);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->default(0);
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable();
            $table->string('thumb_image')->nullable();
            $table->integer('qty')->default(0);
            $table->unsignedInteger('initial_qty')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('products');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_product_stock_drop_creates_notifications_for_seller_and_admin(): void
    {
        $sellerUser = User::query()->create([
            'name' => 'Seller User',
            'email' => 'seller@example.com',
        ]);

        $vendor = Vendor::query()->create([
            'user_id' => $sellerUser->id,
            'status' => 1,
            'shop_name' => 'Seller Shop',
        ]);

        Admin::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        Setting::query()->create([
            'low_stock_threshold' => 5,
            'stock_alert_enabled' => true,
            'low_stock_relative_percent' => 20,
            'low_stock_min_qty' => 1,
        ]);

        $product = Product::query()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Stock Test Product',
            'slug' => 'stock-test-product',
            'qty' => 10,
            'initial_qty' => 10,
            'status' => 1,
        ]);

        $product->update(['qty' => 2]);

        $this->assertSame(2, DatabaseNotification::query()->count());

        $sellerNotification = DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $sellerUser->id)
            ->first();

        $this->assertNotNull($sellerNotification);
        $this->assertSame('stock_alert', $sellerNotification->data['type']);
        $this->assertSame($product->id, $sellerNotification->data['product_id']);
        $this->assertSame(10, $sellerNotification->data['initial_qty']);
    }

    public function test_buyer_notifications_endpoint_lists_notifications(): void
    {
        $buyer = User::query()->create([
            'name' => 'Buyer User',
            'email' => 'buyer@example.com',
        ]);

        DatabaseNotification::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\AdminBroadcastNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $buyer->id,
            'data' => json_encode([
                'type' => 'admin_broadcast',
                'title' => 'Duyuru',
                'message' => 'Test message',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($buyer, 'api')->get('/api/user/notifications');
        $response->assertOk()
            ->assertJsonPath('unread_count', 1);
    }

    public function test_seller_notifications_endpoint_lists_and_marks_notifications_as_read(): void
    {
        $sellerUser = User::query()->create([
            'name' => 'Seller User',
            'email' => 'seller@example.com',
        ]);

        Vendor::query()->create([
            'user_id' => $sellerUser->id,
            'status' => 1,
            'shop_name' => 'Seller Shop',
        ]);

        DatabaseNotification::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\StockAlertNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $sellerUser->id,
            'data' => json_encode([
                'type' => 'stock_alert',
                'message' => 'Low stock warning',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($sellerUser, 'api')->get('/api/seller/notifications');
        $response->assertOk()
            ->assertJsonPath('unread_count', 1);

        $notificationId = $response->json('notifications.data.0.id');

        $markResponse = $this->actingAs($sellerUser, 'api')->put("/api/seller/notifications/{$notificationId}/read");
        $markResponse->assertOk();

        $this->assertNotNull(DatabaseNotification::query()->find($notificationId)?->read_at);
    }
}
