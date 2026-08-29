<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use App\Services\SellerAiAssistantService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class SellerAiAssistantServiceTest extends TestCase
{
    use UsesInMemorySqlite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('openai_enabled')->default(true);
            $table->string('openai_api_key')->nullable();
            $table->string('openai_model')->nullable();
            $table->integer('openai_timeout')->nullable();
            $table->boolean('claude_enabled')->default(false);
            $table->string('claude_api_key')->nullable();
            $table->string('claude_model')->nullable();
            $table->integer('claude_timeout')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('shop_name')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('thumb_image')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('offer_price', 10, 2)->default(0);
            $table->integer('qty')->default(0);
            $table->integer('status')->default(1);
            $table->integer('approve_by_admin')->default(1);
            $table->text('short_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('order_status')->default(0);
            $table->timestamps();
        });

        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->integer('qty')->default(1);
            $table->decimal('seller_net_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Setting::query()->create([
            'openai_enabled' => true,
            'openai_api_key' => 'test-key',
            'openai_model' => 'gpt-4o-mini',
            'openai_timeout' => 30,
            'claude_enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('order_products');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('users');
        Schema::dropIfExists('settings');
        parent::tearDown();
    }

    public function test_ai_assistant_updates_product_price_via_action_block(): void
    {
        $vendor = Vendor::query()->create(['user_id' => 1, 'shop_name' => 'Test Shop']);

        Product::query()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Profesyonel Erkek Berber Koltuğu',
            'slug' => 'berber-koltugu',
            'thumb_image' => 'uploads/test.jpg',
            'price' => 10000,
            'offer_price' => 0,
            'qty' => 5,
            'status' => 1,
            'approve_by_admin' => 1,
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => "Tamam, berber koltuğunuzun fiyatını güncelliyorum.\n<!--ACTION{\"type\":\"update_product\",\"product_id\":1,\"product_name\":\"berber koltugu\",\"fields\":{\"price\":12500}}-->",
                    ],
                ]],
            ]),
        ]);

        $service = app(SellerAiAssistantService::class);
        $result = $service->chat($vendor, 'Berber koltuğumun fiyatını 12500 yap');

        $this->assertStringContainsString('12500', $result['reply']);
        $this->assertNotNull($result['action_taken']);
        $this->assertSame(12500.0, (float) Product::first()->price);
    }

    public function test_ai_assistant_returns_message_when_ai_disabled(): void
    {
        Setting::first()->update(['openai_enabled' => false, 'claude_enabled' => false]);
        $vendor = Vendor::query()->create(['user_id' => 1, 'shop_name' => 'Test Shop']);

        $service = app(SellerAiAssistantService::class);
        $result = $service->chat($vendor, 'Merhaba');

        $this->assertStringContainsString('kapalı', $result['reply']);
    }

    public function test_ai_assistant_blocks_chatgpt_identity_question_without_calling_api(): void
    {
        Http::fake();
        $vendor = Vendor::query()->create(['user_id' => 1, 'shop_name' => 'Test Shop']);

        $service = app(SellerAiAssistantService::class);
        $result = $service->chat($vendor, 'chatgpt misin');

        Http::assertNothingSent();
        $this->assertStringContainsString('Seyfibaba satıcı paneli asistanı', $result['reply']);
        $this->assertStringNotContainsString('ChatGPT', $result['reply']);
    }

    public function test_ai_assistant_sanitizes_chatgpt_disclosure_in_model_response(): void
    {
        $vendor = Vendor::query()->create(['user_id' => 1, 'shop_name' => 'Test Shop']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'Evet, ben bir ChatGPT modeliyim. Ancak bu sohbet ortamında Seyfibaba satıcı asistanıyım.',
                    ],
                ]],
            ]),
        ]);

        $service = app(SellerAiAssistantService::class);
        $result = $service->chat($vendor, 'Merhaba');

        $this->assertStringNotContainsString('ChatGPT', $result['reply']);
        $this->assertStringContainsString('Seyfibaba satıcı paneli asistanı', $result['reply']);
    }
}
