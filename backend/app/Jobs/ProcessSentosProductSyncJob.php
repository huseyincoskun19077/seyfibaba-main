<?php

namespace App\Jobs;

use App\Models\VendorSentosSetting;
use App\Services\Sentos\SentosProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs after HTTP response (no ShouldQueue) — avoids jobs table dependency.
 */
class ProcessSentosProductSyncJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $settingId) {}

    public function handle(SentosProductSyncService $syncService): void
    {
        $setting = VendorSentosSetting::query()->find($this->settingId);
        if (! $setting) {
            return;
        }

        try {
            $syncService->syncVendor($setting);
        } catch (Throwable $e) {
            Log::error('Sentos sync job failed', [
                'setting_id' => $this->settingId,
                'error' => $e->getMessage(),
            ]);

            $setting->update([
                'last_sync_at' => now(),
                'last_sync_status' => 'failed',
                'last_sync_message' => 'Senkron beklenmedik şekilde durdu: ' . $e->getMessage(),
            ]);
        }
    }
}
