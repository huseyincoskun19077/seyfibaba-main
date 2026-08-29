<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\ReturnRequest;
use App\Models\Setting;
use App\Services\IyzicoService;
use App\Services\PayoutSettingsService;
use App\Services\SellerPayoutService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Iyzipay\Model\Approval;
use Mockery;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class SellerPayoutServiceTest extends TestCase
{
    use UsesInMemorySqlite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();
        $this->createTables();
        Setting::query()->create([
            'auto_complete_days' => 15,
            'payout_hold_days' => 3,
            'return_window_days' => 14,
            'iyzico_payout_dry_run' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Schema::dropIfExists('return_requests');
        Schema::dropIfExists('order_products');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('settings');
        parent::tearDown();
    }

    public function test_sync_payment_transaction_ids_maps_product_lines(): void
    {
        $order = $this->makeCompletedIyzicoOrder();
        $this->insertOrderProduct($order->id, 42, 5);

        $service = app(SellerPayoutService::class);
        $service->syncPaymentTransactionIds($order->fresh('orderProducts'));

        $this->assertSame('TXN-42', $order->fresh('orderProducts')->orderProducts->first()->iyzico_payment_transaction_id);
    }

    public function test_iyzico_payout_succeeds_in_dry_run_mode(): void
    {
        $order = $this->makeCompletedIyzicoOrder(withProduct: true);
        $order->payout_eligible_at = now()->subMinute();
        $order->save();

        $result = app(SellerPayoutService::class)->processOrderPayout($order, false);

        $this->assertTrue($result['success']);
        $this->assertSame('completed', $order->fresh()->payout_status);
        $this->assertNotNull($order->fresh()->payout_processed_at);
    }

    public function test_bank_payment_marks_withdrawable_without_iyzico_call(): void
    {
        $order = $this->makeCompletedIyzicoOrder(withProduct: true);
        $order->payment_method = 'bankpayment';
        $order->iyzico_payment_data = null;
        $order->save();

        $iyzico = Mockery::mock(IyzicoService::class);
        $iyzico->shouldNotReceive('approvePaymentItem');
        $this->app->instance(IyzicoService::class, $iyzico);

        $result = app(SellerPayoutService::class)->processOrderPayout($order, true);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('çekim talebi', $result['message']);
        $this->assertNull($order->fresh()->seller_paid_at);
    }

    public function test_active_return_blocks_payout(): void
    {
        $order = $this->makeCompletedIyzicoOrder(withProduct: true);
        $order->payout_eligible_at = now()->subMinute();
        $order->save();

        ReturnRequest::query()->insert([
            'order_id' => $order->id,
            'user_id' => 1,
            'seller_id' => 5,
            'order_product_id' => $order->orderProducts->first()->id,
            'reason' => 'defective',
            'qty' => 1,
            'status' => ReturnRequest::STATUS_PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order->payout_blocked_at = now();
        $order->save();

        $result = app(SellerPayoutService::class)->processOrderPayout($order, true);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('blok', strtolower($result['message']));
    }

    public function test_payout_not_due_before_hold_period(): void
    {
        $order = $this->makeCompletedIyzicoOrder(withProduct: true);
        $order->customer_confirmed_at = now();
        $order->payout_eligible_at = now()->addDays(2);
        $order->save();

        $result = app(SellerPayoutService::class)->processOrderPayout($order, false);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('bekleme', strtolower($result['message']));
    }

    public function test_real_iyzico_approval_path(): void
    {
        Setting::query()->first()->update(['iyzico_payout_dry_run' => false]);

        $order = $this->makeCompletedIyzicoOrder(withProduct: true);
        $order->payout_eligible_at = now()->subMinute();
        $order->save();

        $approval = Mockery::mock(Approval::class);
        $approval->shouldReceive('getStatus')->andReturn('success');

        $iyzico = Mockery::mock(IyzicoService::class);
        $iyzico->shouldReceive('approvePaymentItem')
            ->once()
            ->with('TXN-42', Mockery::type('string'))
            ->andReturn($approval);

        $this->app->instance(IyzicoService::class, $iyzico);

        $result = app(SellerPayoutService::class)->processOrderPayout($order, false);

        $this->assertTrue($result['success']);
        $this->assertNotNull($order->fresh('orderProducts')->orderProducts->first()->iyzico_approved_at);
    }

    protected function makeCompletedIyzicoOrder(bool $withProduct = false): Order
    {
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => 1,
            'order_id' => 'ORD-1001',
            'payment_method' => 'Iyzico',
            'payment_status' => 1,
            'order_status' => 3,
            'customer_confirmed_at' => now()->subDays(4),
            'payout_status' => 'pending',
            'iyzico_payment_data' => json_encode([
                'payment_id' => 'PAY-1',
                'items' => [[
                    'item_id' => 'PROD-42',
                    'payment_transaction_id' => 'TXN-42',
                    'price' => '100.00',
                ]],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = Order::query()->findOrFail($orderId);

        if ($withProduct) {
            $this->insertOrderProduct($order->id, 42, 5, 90, 10);
            $order->load('orderProducts');
        }

        return $order;
    }

    protected function insertOrderProduct(int $orderId, int $productId, int $sellerId, float $net = 0, float $commission = 0): void
    {
        DB::table('order_products')->insert([
            'order_id' => $orderId,
            'product_id' => $productId,
            'seller_id' => $sellerId,
            'product_name' => 'Test',
            'unit_price' => 100,
            'qty' => 1,
            'seller_net_amount' => $net,
            'commission_amount' => $commission,
            'payout_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createTables(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('auto_complete_days')->default(15);
            $table->unsignedSmallInteger('payout_hold_days')->default(3);
            $table->integer('return_window_days')->default(14);
            $table->boolean('iyzico_payout_dry_run')->default(false);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('order_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->tinyInteger('payment_status')->default(0);
            $table->tinyInteger('order_status')->default(0);
            $table->timestamp('customer_confirmed_at')->nullable();
            $table->timestamp('auto_complete_date')->nullable();
            $table->timestamp('payout_eligible_at')->nullable();
            $table->timestamp('payout_blocked_at')->nullable();
            $table->text('payout_block_reason')->nullable();
            $table->timestamp('payout_hold_until')->nullable();
            $table->timestamp('payout_processed_at')->nullable();
            $table->timestamp('seller_paid_at')->nullable();
            $table->string('payout_status')->nullable();
            $table->text('iyzico_payment_data')->nullable();
            $table->timestamps();
        });

        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('seller_id')->default(0);
            $table->string('product_name')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->integer('qty')->default(1);
            $table->decimal('commission_amount', 12, 2)->nullable();
            $table->decimal('seller_net_amount', 12, 2)->nullable();
            $table->timestamp('payout_eligible_at')->nullable();
            $table->string('payout_status')->default('pending');
            $table->timestamp('payout_processed_at')->nullable();
            $table->text('payout_block_reason')->nullable();
            $table->string('iyzico_payment_transaction_id', 64)->nullable();
            $table->timestamp('iyzico_approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('order_product_id');
            $table->string('reason');
            $table->integer('qty')->default(1);
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }
}
