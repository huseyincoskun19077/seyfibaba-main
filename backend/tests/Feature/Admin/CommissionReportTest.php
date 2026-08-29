<?php

namespace Tests\Feature\Admin;

use App\Models\CommissionLedger;
use App\Support\CommissionLedgerCleanup;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class CommissionReportTest extends TestCase
{
    use UsesInMemorySqlite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->integer('payment_status')->default(0);
            $table->integer('order_status')->default(0);
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('shop_name');
            $table->timestamps();
        });

        Schema::create('commission_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_product_id')->nullable();
            $table->unsignedBigInteger('seller_id');
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('commission_rate', 8, 2)->default(10);
            $table->decimal('commission_amount', 12, 2);
            $table->decimal('seller_net_amount', 12, 2);
            $table->string('status')->default('settled');
            $table->timestamp('settled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('commission_ledger');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('orders');
        parent::tearDown();
    }

    public function test_paid_orders_scope_excludes_unpaid_ledger_rows(): void
    {
        $paidOrderId = DB::table('orders')->insertGetId([
            'order_id' => 'PAID-1',
            'total_amount' => 100,
            'payment_status' => 1,
            'order_status' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unpaidOrderId = DB::table('orders')->insertGetId([
            'order_id' => 'UNPAID-1',
            'total_amount' => 3000000,
            'payment_status' => 0,
            'order_status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vendorId = DB::table('vendors')->insertGetId([
            'shop_name' => 'Test Shop',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('commission_ledger')->insert([
            'order_id' => $paidOrderId,
            'seller_id' => $vendorId,
            'gross_amount' => 188.44,
            'commission_rate' => 10,
            'commission_amount' => 18.84,
            'seller_net_amount' => 169.60,
            'status' => 'settled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('commission_ledger')->insert([
            'order_id' => $unpaidOrderId,
            'seller_id' => $vendorId,
            'gross_amount' => 3000007,
            'commission_rate' => 10,
            'commission_amount' => 300022,
            'seller_net_amount' => 2699985,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $summary = CommissionLedger::paidOrders()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as total_gross')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as total_commission')
            ->first();

        $this->assertEquals(188.44, (float) $summary->total_gross);
        $this->assertEquals(18.84, (float) $summary->total_commission);
    }

    public function test_cleanup_removes_unpaid_order_ledger_rows(): void
    {
        $unpaidOrderId = DB::table('orders')->insertGetId([
            'order_id' => 'UNPAID-2',
            'total_amount' => 500,
            'payment_status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vendorId = DB::table('vendors')->insertGetId([
            'shop_name' => 'Cleanup Shop',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('commission_ledger')->insert([
            'order_id' => $unpaidOrderId,
            'seller_id' => $vendorId,
            'gross_amount' => 500,
            'commission_rate' => 10,
            'commission_amount' => 50,
            'seller_net_amount' => 450,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $deleted = CommissionLedgerCleanup::purgeInvalidRows();

        $this->assertSame(1, $deleted);
        $this->assertDatabaseCount('commission_ledger', 0);
    }
}
