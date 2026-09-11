<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductImageStorage;
use App\Support\ProductImageUrl;
use Illuminate\Console\Command;

/**
 * Download remote product thumbs (e.g. qukasoft CDN) into uploads/custom-images.
 * Does not call Kuaför Sepeti API — only uses URLs already stored on products.
 */
class LocalizeProductImages extends Command
{
    protected $signature = 'products:localize-images
                            {--vendor= : vendor_id filter}
                            {--product= : single product id}
                            {--limit=0 : max products to process (0 = all)}
                            {--sleep-ms=200 : pause between downloads}
                            {--dry-run : list only, do not download}';

    protected $description = 'HTTP/CDN thumb_image URL’lerini Seyfibaba uploads altına indirir';

    public function handle(ProductImageStorage $imageStorage): int
    {
        @set_time_limit(0);

        $query = Product::query()
            ->whereNotNull('thumb_image')
            ->where('thumb_image', '!=', '')
            ->where(function ($q) {
                $q->where('thumb_image', 'like', 'http://%')
                    ->orWhere('thumb_image', 'like', 'https://%')
                    ->orWhere('thumb_image', 'like', '//%');
            })
            ->orderBy('id');

        if ($this->option('vendor') !== null && $this->option('vendor') !== '') {
            $query->where('vendor_id', (int) $this->option('vendor'));
        }

        if ($this->option('product') !== null && $this->option('product') !== '') {
            $query->where('id', (int) $this->option('product'));
        }

        $dryRun = (bool) $this->option('dry-run');
        $sleepMs = max(0, (int) $this->option('sleep-ms'));
        $max = max(0, (int) $this->option('limit'));

        $this->info(($dryRun ? '[dry-run] ' : '') . 'Başladı…');

        $ok = 0;
        $fail = 0;
        $skip = 0;
        $processed = 0;

        $query->chunkById(50, function ($products) use ($imageStorage, $dryRun, $sleepMs, $max, &$ok, &$fail, &$skip, &$processed) {
            foreach ($products as $product) {
                if ($max > 0 && $processed >= $max) {
                    return false;
                }
                $processed++;

                $url = trim((string) $product->thumb_image);
                if (! ProductImageUrl::isExternal($url)) {
                    $skip++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("  #{$product->id} {$url}");
                    $ok++;
                    continue;
                }

                $prefix = 'p' . $product->id . '-' . mb_substr((string) ($product->short_name ?: $product->name ?: 'product'), 0, 40);
                $extraHeaders = [];
                if (stripos($url, 'qukasoft.com') !== false) {
                    $extraHeaders['Referer'] = 'https://www.kuaforsepeti.com/';
                }

                $stored = $imageStorage->storeFromUrl($url, $prefix, 45, $extraHeaders);
                if (! $stored) {
                    $fail++;
                    $this->warn("  FAIL #{$product->id}");
                    continue;
                }

                $product->thumb_image = $stored;
                $product->save();
                $ok++;
                $this->line("  OK #{$product->id} → {$stored}");

                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }

            if ($max > 0 && $processed >= $max) {
                return false;
            }
        });

        $this->info("Bitti. ok={$ok} fail={$fail} skip={$skip}");

        return $fail > 0 && $ok === 0 ? self::FAILURE : self::SUCCESS;
    }
}
