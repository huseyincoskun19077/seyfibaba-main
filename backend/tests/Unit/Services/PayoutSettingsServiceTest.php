<?php

namespace Tests\Unit\Services;

use App\Models\Setting;
use App\Services\PayoutSettingsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class PayoutSettingsServiceTest extends TestCase
{
    use UsesInMemorySqlite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('auto_complete_days')->default(15);
            $table->unsignedSmallInteger('payout_hold_days')->default(3);
            $table->integer('return_window_days')->default(14);
            $table->boolean('iyzico_payout_dry_run')->default(false);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('settings');
        parent::tearDown();
    }

    public function test_defaults_when_settings_missing(): void
    {
        $service = new PayoutSettingsService();

        $this->assertSame(15, $service->autoCompleteDays());
        $this->assertSame(3, $service->payoutHoldDays());
        $this->assertSame(14, $service->returnWindowDays());
    }

    public function test_reads_values_from_database(): void
    {
        Setting::query()->create([
            'auto_complete_days' => 10,
            'payout_hold_days' => 1,
            'return_window_days' => 7,
            'iyzico_payout_dry_run' => true,
        ]);

        $service = new PayoutSettingsService();

        $this->assertSame(10, $service->autoCompleteDays());
        $this->assertSame(1, $service->payoutHoldDays());
        $this->assertSame(7, $service->returnWindowDays());
        $this->assertTrue($service->iyzicoPayoutDryRun());
    }
}
