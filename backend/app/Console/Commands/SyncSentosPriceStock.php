<?php

namespace App\Console\Commands;

use App\Models\VendorSentosSetting;
use App\Services\Sentos\SentosProductSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Hourly Sentos → Seyfibaba price/stock pull (docs-compliant pacing).
 * Only vendors with integration enabled; does not alter other sellers.
 */
class SyncSentosPriceStock extends Command
{
    protected $signature = 'sentos:sync-price-stock
                            {--vendor= : Only sync this vendor_id}
                            {--delay=5 : Seconds to wait between vendors (rate-limit friendly)}';

    protected $description = 'Sentos’tan saatlik fiyat ve stok güncellemesi (yalnızca eşleşmiş ürünler)';

    public function handle(SentosProductSyncService $syncService): int
    {
        if (! config('features.sentos_enabled', true)) {
            $this->info('FEATURE_SENTOS kapalı; atlandı.');

            return self::SUCCESS;
        }

        $vendorId = $this->option('vendor');
        $delay = max(0, (int) $this->option('delay'));

        $query = VendorSentosSetting::query()->where('is_enabled', true)->orderBy('id');
        if ($vendorId !== null && $vendorId !== '') {
            $query->where('vendor_id', (int) $vendorId);
        }

        $settings = $query->get();
        if ($settings->isEmpty()) {
            $this->info('Aktif Sentos satıcısı yok.');

            return self::SUCCESS;
        }

        $this->info('Sentos fiyat/stok senkronu: ' . $settings->count() . ' satıcı');

        $okCount = 0;
        $failCount = 0;

        foreach ($settings as $index => $setting) {
            if (! $setting->hasCredentials()) {
                $this->warn("vendor {$setting->vendor_id}: API bilgisi eksik, atlandı");
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
                Log::warning('sentos:sync-price-stock vendor failed', [
                    'vendor_id' => $setting->vendor_id,
                    'error' => $e->getMessage(),
                ]);
                $this->error('  Hata: ' . $e->getMessage());
            }

            if ($delay > 0 && $index < $settings->count() - 1) {
                sleep($delay);
            }
        }

        $this->info("Bitti. OK={$okCount} FAIL={$failCount}");

        return $failCount > 0 && $okCount === 0 ? self::FAILURE : self::SUCCESS;
    }
}
