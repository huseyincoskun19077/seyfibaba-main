<?php

namespace App\Console\Commands;

use App\Services\BarcodeCatalogService;
use Illuminate\Console\Command;

class SeedBarcodeCatalog extends Command
{
    protected $signature = 'products:seed-barcode-catalog
                            {--vendor=15 : Source vendor_id (existing products)}
                            {--dry-run : Only count distinct barcodes}';

    protected $description = 'Mevcut ürün barkodlarından kalıcı barcode_catalog oluşturur (API’den çekmez)';

    public function handle(BarcodeCatalogService $service): int
    {
        $vendorId = (int) $this->option('vendor');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $count = \App\Models\Product::query()
                ->where('vendor_id', $vendorId)
                ->whereNotNull('barcode')
                ->where('barcode', '!=', '')
                ->distinct('barcode')
                ->count('barcode');
            $this->info("[dry-run] vendor={$vendorId} distinct barcode≈{$count}");

            return self::SUCCESS;
        }

        $this->info("Seed başlıyor vendor={$vendorId} …");
        $result = $service->seedFromVendor($vendorId);
        $this->info('Bitti. upserted=' . $result['upserted'] . ' skipped=' . $result['skipped']);
        $this->info('Katalog toplam: ' . \App\Models\BarcodeCatalog::query()->count());

        return self::SUCCESS;
    }
}
