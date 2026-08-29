<?php

namespace Tests\Unit\CallCenter;

use App\Models\Admin;
use App\Models\CallCenterCommission;
use App\Models\Vendor;
use App\Services\CallCenter\CallCenterCommissionCalculator;
use App\Services\CallCenter\CallCenterCommissionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class CallCenterCommissionServiceTest extends TestCase
{
    use UsesInMemorySqlite;

    private CallCenterCommissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedTinyInteger('admin_type')->default(2);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('shop_name')->nullable();
            $table->string('registration_source')->default('self');
            $table->unsignedBigInteger('registered_by_admin_id')->nullable();
            $table->string('kyc_status')->default('not_submitted');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->timestamps();
        });

        Schema::create('call_center_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->unique();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedInteger('product_count')->default(0);
            $table->decimal('calculated_total', 12, 2)->default(0);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('paid_total', 12, 2)->default(0);
            $table->string('status', 32)->default('open');
            $table->json('breakdown')->nullable();
            $table->timestamp('agent_approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('call_center_commission_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('admin_id');
            $table->decimal('amount', 12, 2);
            $table->unsignedBigInteger('paid_by_admin_id');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        $this->service = new CallCenterCommissionService(new CallCenterCommissionCalculator);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('call_center_commission_payments');
        Schema::dropIfExists('call_center_commissions');
        Schema::dropIfExists('products');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('admins');
        parent::tearDown();
    }

    public function test_sync_creates_commission_row_for_call_center_vendor(): void
    {
        $agent = $this->createAgent();
        $vendor = $this->createVendor($agent, 'approved', 2);

        $commission = $this->service->syncForVendor($vendor);

        $this->assertSame($vendor->id, $commission->vendor_id);
        $this->assertSame(166.0, (float) $commission->calculated_total);
        $this->assertSame(CallCenterCommission::STATUS_OPEN, $commission->status);
    }

    public function test_agent_approve_moves_to_awaiting_payment(): void
    {
        $agent = $this->createAgent();
        $vendor = $this->createVendor($agent, 'approved', 1);

        $this->service->syncForVendor($vendor);
        $commission = $this->service->approveByAgent($vendor, $agent);

        $this->assertSame(CallCenterCommission::STATUS_AWAITING_PAYMENT, $commission->status);
        $this->assertSame(163.0, (float) $commission->approved_amount);
    }

    public function test_admin_mark_paid_records_payment_and_resets_status(): void
    {
        $agent = $this->createAgent();
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
            'admin_type' => Admin::TYPE_SUPER,
            'status' => 1,
        ]);
        $vendor = $this->createVendor($agent, 'approved', 1);

        $this->service->syncForVendor($vendor);
        $this->service->approveByAgent($vendor, $agent);

        $payment = $this->service->markPaid($vendor->fresh(), $admin, 'Test ödeme');

        $this->assertSame(163.0, (float) $payment->amount);
        $commission = CallCenterCommission::query()->where('vendor_id', $vendor->id)->first();
        $this->assertSame(163.0, (float) $commission->paid_total);
        $this->assertSame(CallCenterCommission::STATUS_OPEN, $commission->status);
        $this->assertNull($commission->approved_amount);
    }

    private function createAgent(): Admin
    {
        return Admin::query()->create([
            'name' => 'Agent',
            'email' => 'agent@example.com',
            'password' => bcrypt('secret'),
            'admin_type' => Admin::TYPE_CALL_CENTER,
            'status' => 1,
        ]);
    }

    private function createVendor(Admin $agent, string $kycStatus, int $productCount): Vendor
    {
        $vendor = Vendor::query()->create([
            'shop_name' => 'Test Shop',
            'registration_source' => 'call_center',
            'registered_by_admin_id' => $agent->id,
            'kyc_status' => $kycStatus,
        ]);

        foreach (range(1, $productCount) as $i) {
            \DB::table('products')->insert(['vendor_id' => $vendor->id, 'created_at' => now(), 'updated_at' => now()]);
        }

        return $vendor->fresh();
    }
}
