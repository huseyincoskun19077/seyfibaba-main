<?php

namespace App\Console\Commands;

use App\Models\VendorSofttrSetting;
use App\Services\Softtr\SofttrProductSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncSofttrPriceStock extends Command
{
    protected $signature = 'softtr:sync-price-stock
                            {--vendor= : Only sync this vendor_id}
                            {--delay=5 : Seconds between vendors}';

    protected $description = 'Softtr’dan saatlik ürün / fiyat / stok çekimi';

    public function handle(SofttrProductSyncService $syncService): int
    {
        if (! config('features.softtr_enabled', true)) {
            $this->info('FEATURE_SOFTTR kapalı; atlandı.');

            return self::SUCCESS;
        }

        $vendorId = $this->option('vendor');
        $delay = max(0, (int) $this->option('delay'));

        $query = VendorSofttrSetting::query()->where('is_enabled', true)->orderBy('id');
        if ($vendorId !== null && $vendorId !== '') {
            $query->where('vendor_id', (int) $vendorId);
        }

        $settings = $query->get();
        if ($settings->isEmpty()) {
            $this->info('Aktif Softtr satıcısı yok.');

            return self::SUCCESS;
        }

        $okCount = 0;
        $failCount = 0;

        foreach ($settings as $index => $setting) {
            if (! $setting->hasCredentials()) {
                continue;
            }

            $this->line("→ vendor {$setting->vendor_id} …");
            try {
                $result = $syncService->syncVendorPriceStock($setting);
                if ($result['ok']) {
                    $okCount++;
                    $this->info('  ' . $result['message']);
                } else {
                    $failCount++;
                    $this->error('  ' . $result['message']);
                }
            } catch (\Throwable $e) {
                $failCount++;
                Log::warning('softtr:sync-price-stock failed', [
                    'vendor_id' => $setting->vendor_id,
                    'error' => $e->getMessage(),
                ]);
                $this->error('  ' . $e->getMessage());
            }

            if ($delay > 0 && $index < $settings->count() - 1) {
                sleep($delay);
            }
        }

        $this->info("Bitti. OK={$okCount} FAIL={$failCount}");

        return $failCount > 0 && $okCount === 0 ? self::FAILURE : self::SUCCESS;
    }
}
