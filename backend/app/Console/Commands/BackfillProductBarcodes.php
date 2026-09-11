<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Copy existing sku → barcode and append barcode into SEO fields when missing.
 */
class BackfillProductBarcodes extends Command
{
    protected $signature = 'products:backfill-barcodes
                            {--vendor= : vendor_id filter}
                            {--limit=0 : max products (0 = all)}
                            {--dry-run : count only}';

    protected $description = 'sku dolu ürünlerde barcode alanını doldurur; SEO’ya barkod ekler';

    public function handle(): int
    {
        $query = Product::query()
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->where(function ($q) {
                $q->whereNull('barcode')->orWhere('barcode', '');
            })
            ->orderBy('id');

        if ($this->option('vendor') !== null && $this->option('vendor') !== '') {
            $query->where('vendor_id', (int) $this->option('vendor'));
        }

        $limit = max(0, (int) $this->option('limit'));
        if ($limit > 0) {
            $query->limit($limit);
        }

        $dryRun = (bool) $this->option('dry-run');
        $total = (clone $query)->count();
        $this->info(($dryRun ? '[dry-run] ' : '') . "Doldurulacak: {$total}");

        if ($dryRun) {
            return self::SUCCESS;
        }

        $updated = 0;
        $query->chunkById(100, function ($products) use (&$updated) {
            foreach ($products as $product) {
                $code = trim((string) $product->sku);
                if ($code === '') {
                    continue;
                }

                $product->barcode = $code;

                $seoTitle = trim((string) ($product->seo_title ?? ''));
                if ($seoTitle === '') {
                    $seoTitle = trim((string) $product->name);
                }
                if ($seoTitle !== '' && ! str_contains($seoTitle, $code)) {
                    $product->seo_title = mb_substr($seoTitle . ' ' . $code, 0, 190);
                }

                $seoDesc = trim((string) ($product->seo_description ?? ''));
                if ($seoDesc === '') {
                    $seoDesc = trim((string) ($product->short_description ?: $product->name));
                }
                if ($seoDesc !== '' && ! str_contains($seoDesc, $code)) {
                    $product->seo_description = mb_substr(
                        rtrim($seoDesc, " \t\n\r.") . '. Barkod: ' . $code,
                        0,
                        320
                    );
                }

                $product->save();
                $updated++;
            }
        });

        $this->info("Bitti. updated={$updated}");

        return self::SUCCESS;
    }
}
