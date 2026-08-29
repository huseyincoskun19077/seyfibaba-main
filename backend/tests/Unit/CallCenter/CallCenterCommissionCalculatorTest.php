<?php

namespace Tests\Unit\CallCenter;

use App\Models\Vendor;
use App\Services\CallCenter\CallCenterCommissionCalculator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class CallCenterCommissionCalculatorTest extends TestCase
{
    use UsesInMemorySqlite;

    private CallCenterCommissionCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('registration_source')->default('self');
            $table->string('kyc_status')->default('not_submitted');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->timestamps();
        });

        $this->calculator = new CallCenterCommissionCalculator;
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('vendors');
        parent::tearDown();
    }

    public function test_non_call_center_vendor_has_zero_commission(): void
    {
        $vendor = Vendor::query()->create(['registration_source' => 'self', 'kyc_status' => 'approved']);

        $result = $this->calculator->calculate($vendor, 10);

        $this->assertFalse($result['eligible']);
        $this->assertSame(0.0, $result['total']);
    }

    public function test_base_bonus_requires_kyc_and_at_least_one_product(): void
    {
        $vendor = Vendor::query()->create(['registration_source' => 'call_center', 'kyc_status' => 'approved']);

        $result = $this->calculator->calculate($vendor, 1);

        $this->assertSame(160.0, $result['base_amount']);
        $this->assertSame(3.0, $result['per_product_amount']);
        $this->assertSame(163.0, $result['total']);
    }

    public function test_kyc_not_approved_skips_base_bonus(): void
    {
        $vendor = Vendor::query()->create(['registration_source' => 'call_center', 'kyc_status' => 'pending']);

        $result = $this->calculator->calculate($vendor, 5);

        $this->assertSame(0.0, $result['base_amount']);
        $this->assertSame(15.0, $result['per_product_amount']);
        $this->assertSame(15.0, $result['total']);
    }

    public function test_per_product_cap_at_200(): void
    {
        $vendor = Vendor::query()->create(['registration_source' => 'call_center', 'kyc_status' => 'approved']);

        $result = $this->calculator->calculate($vendor, 250);

        $this->assertSame(200, $result['per_product_units']);
        $this->assertSame(600.0, $result['per_product_amount']);
    }

    public function test_milestone_bonus_every_100_products(): void
    {
        $vendor = Vendor::query()->create(['registration_source' => 'call_center', 'kyc_status' => 'approved']);

        $result100 = $this->calculator->calculate($vendor, 100);
        $this->assertSame(1, $result100['milestone_count']);
        $this->assertSame(200.0, $result100['milestone_amount']);

        $result300 = $this->calculator->calculate($vendor, 300);
        $this->assertSame(3, $result300['milestone_count']);
        $this->assertSame(600.0, $result300['milestone_amount']);
    }

    public function test_product_cap_at_500_for_milestones(): void
    {
        $vendor = Vendor::query()->create(['registration_source' => 'call_center', 'kyc_status' => 'approved']);

        $result = $this->calculator->calculate($vendor, 600);

        $this->assertSame(500, $result['capped_product_count']);
        $this->assertSame(5, $result['milestone_count']);
        $this->assertSame(1000.0, $result['milestone_amount']);
    }

    public function test_max_theoretical_total_per_seller(): void
    {
        $vendor = Vendor::query()->create(['registration_source' => 'call_center', 'kyc_status' => 'approved']);

        $result = $this->calculator->calculate($vendor, 500);

        // 160 + 600 + 1000 = 1760
        $this->assertSame(1760.0, $result['total']);
    }
}
