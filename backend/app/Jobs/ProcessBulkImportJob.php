<?php

namespace App\Jobs;

use App\Models\BulkImport;
use App\Models\Vendor;
use App\Services\BulkProductImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Toplu import — HTTP yanıtı döndükten sonra çalışır (nginx 504 önlenir).
 * ShouldQueue kullanılmaz; terminate callback ile senkron işlenir.
 */
class ProcessBulkImportJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $bulkImportId,
        public ?int $vendorId = null,
    ) {}

    public function handle(BulkProductImportService $bulkImportService): void
    {
        $bulkImport = BulkImport::query()->find($this->bulkImportId);
        if (! $bulkImport) {
            return;
        }

        if (in_array($bulkImport->status, ['completed', 'failed'], true)) {
            return;
        }

        $vendor = $this->vendorId ? Vendor::query()->find($this->vendorId) : null;

        try {
            $bulkImportService->process($bulkImport, $vendor);
        } catch (Throwable $e) {
            Log::error('Bulk import job failed', [
                'bulk_import_id' => $this->bulkImportId,
                'message' => $e->getMessage(),
            ]);

            $bulkImport->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_log' => array_merge($bulkImport->error_log ?? [], [[
                    'row' => 0,
                    'message' => 'Import işlemi beklenmedik şekilde durdu: ' . $e->getMessage(),
                ]]),
            ]);
        }
    }
}
