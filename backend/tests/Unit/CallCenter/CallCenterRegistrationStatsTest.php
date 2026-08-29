<?php

namespace Tests\Unit\CallCenter;

use App\Http\Controllers\WEB\Admin\CallCenterRegistrationController;
use App\Models\Admin;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class CallCenterRegistrationStatsTest extends TestCase
{
    use UsesInMemorySqlite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedTinyInteger('admin_type')->default(1);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('shop_name')->nullable();
            $table->string('registration_source')->nullable();
            $table->unsignedBigInteger('registered_by_admin_id')->nullable();
            $table->string('kyc_status')->default('not_submitted');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->string('name');
            $table->string('slug')->unique();
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
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('call_center_commissions');
        Schema::dropIfExists('products');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('users');
        Schema::dropIfExists('admins');
        parent::tearDown();
    }

    public function test_agent_stats_do_not_inflate_seller_and_kyc_counts_with_product_join(): void
    {
        $agent = Admin::query()->create([
            'name' => 'Emre Üstün',
            'email' => 'emre@example.com',
            'password' => bcrypt('secret'),
            'admin_type' => Admin::TYPE_CALL_CENTER,
            'status' => 1,
        ]);

        $approvedUser = User::query()->create(['name' => 'Onayli', 'email' => 'a@test.com']);
        $pendingUser = User::query()->create(['name' => 'Bekleyen', 'email' => 'b@test.com']);

        $approvedVendor = Vendor::query()->create([
            'user_id' => $approvedUser->id,
            'shop_name' => 'Onayli Firma',
            'registration_source' => 'call_center',
            'registered_by_admin_id' => $agent->id,
            'kyc_status' => 'approved',
        ]);

        $pendingVendor = Vendor::query()->create([
            'user_id' => $pendingUser->id,
            'shop_name' => 'Bekleyen Firma',
            'registration_source' => 'call_center',
            'registered_by_admin_id' => $agent->id,
            'kyc_status' => 'pending',
        ]);

        foreach (range(1, 3) as $i) {
            Product::query()->create([
                'vendor_id' => $approvedVendor->id,
                'name' => "Urun {$i}",
                'slug' => "urun-{$i}",
            ]);
        }

        Product::query()->create([
            'vendor_id' => $pendingVendor->id,
            'name' => 'Tek urun',
            'slug' => 'tek-urun',
        ]);

        $controller = app(CallCenterRegistrationController::class);
        $method = new \ReflectionMethod($controller, 'buildAgentStats');
        $method->setAccessible(true);
        $stats = $method->invoke($controller);

        $stat = $stats[$agent->id];

        $this->assertSame(2, $stat->seller_count);
        $this->assertSame(1, $stat->approved_kyc_count);
        $this->assertSame(4, $stat->product_count);
    }
}
