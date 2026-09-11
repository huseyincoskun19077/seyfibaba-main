<?php

namespace App\Services\Softtr;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\Vendor;
use App\Models\VendorSofttrProductMap;
use App\Models\VendorSofttrSetting;
use App\Services\BarcodeCatalogService;
use App\Services\ImportCategoryResolver;
use App\Services\ProductImageStorage;
use App\Support\ProductImageUrl;
use App\Support\ProductSlug;
use Illuminate\Support\Facades\Log;

/**
 * Pull Softtr products into one vendor catalog (one-way Softtr → Seyfibaba).
 * Softtr list often returns one row per variant with the same SKU — we dedupe by SKU.
 */
class SofttrProductSyncService
{
    public function __construct(
        private SofttrApiClient $client,
        private SofttrProductNormalizer $normalizer,
        private ImportCategoryResolver $categoryResolver,
        private ProductImageStorage $imageStorage,
    ) {}

    /**
     * @return array{ok: bool, message: string, stats: array<string, int>}
     */
    public function syncVendor(VendorSofttrSetting $setting): array
    {
        return $this->runSync($setting, full: true, pageDelayMs: 0);
    }

    /**
     * @return array{ok: bool, message: string, stats: array<string, int>}
     */
    public function syncVendorPriceStock(VendorSofttrSetting $setting): array
    {
        return $this->runSync($setting, full: false, pageDelayMs: 1500);
    }

    /**
     * @return array{ok: bool, message: string, stats: array<string, int>}
     */
    private function runSync(VendorSofttrSetting $setting, bool $full, int $pageDelayMs): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $stats = [
            'fetched' => 0,
            'deduped' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        if (! $setting->is_enabled || ! $setting->hasCredentials()) {
            return [
                'ok' => false,
                'message' => 'Softtr kapalı veya API bilgisi eksik.',
                'stats' => $stats,
            ];
        }

        $vendor = Vendor::query()->find($setting->vendor_id);
        if (! $vendor) {
            return [
                'ok' => false,
                'message' => 'Satıcı bulunamadı.',
                'stats' => $stats,
            ];
        }

        $label = $full ? 'Ürün senkronu' : 'Saatlik senkron';

        try {
            $delay = max(2000, $pageDelayMs);
            $rawProducts = $this->client->listAllProducts($setting, 100, 200, $delay);
        } catch (\Throwable $e) {
            Log::warning('Softtr product list failed', [
                'vendor_id' => $vendor->id,
                'error' => $e->getMessage(),
            ]);
            $setting->update([
                'last_sync_at' => now(),
                'last_sync_status' => 'failed',
                'last_sync_message' => $label . ' listesi alınamadı: ' . $e->getMessage(),
                'last_sync_stats' => $stats,
            ]);

            return [
                'ok' => false,
                'message' => 'Ürün listesi alınamadı: ' . $e->getMessage(),
                'stats' => $stats,
            ];
        }

        $shopOrigin = $this->client->shopOrigin($setting);
        $grouped = [];
        foreach ($rawProducts as $raw) {
            if (! is_array($raw)) {
                continue;
            }
            $normalized = $this->normalizer->normalize($raw, $shopOrigin);
            if (! $normalized) {
                $stats['skipped']++;
                continue;
            }
            $stats['fetched']++;

            $key = $this->dedupeKey($normalized);
            if (! isset($grouped[$key])) {
                $normalized['softtr_ids'] = [(string) $normalized['softtr_id']];
                $grouped[$key] = $normalized;
                continue;
            }

            $grouped[$key]['softtr_ids'][] = (string) $normalized['softtr_id'];
            $grouped[$key]['qty'] = max((int) $grouped[$key]['qty'], (int) $normalized['qty']);
            if ($grouped[$key]['price'] <= 0 && $normalized['price'] > 0) {
                $grouped[$key]['price'] = $normalized['price'];
            }
            if ($grouped[$key]['image_url'] === '' && $normalized['image_url'] !== '') {
                $grouped[$key]['image_url'] = $normalized['image_url'];
            }
            if ($grouped[$key]['barcode'] === '' && $normalized['barcode'] !== '') {
                $grouped[$key]['barcode'] = $normalized['barcode'];
            }
        }

        $stats['deduped'] = count($grouped);
        $this->categoryResolver->beginBulkImport(max(count($grouped), 1));

        try {
            foreach ($grouped as $normalized) {
                $softtrIds = array_values(array_unique($normalized['softtr_ids'] ?? [(string) $normalized['softtr_id']]));
                unset($normalized['softtr_ids']);
                $normalized['softtr_id'] = $softtrIds[0];

                try {
                    $result = $this->upsertProduct($vendor, $normalized, $softtrIds);
                    if ($result === 'created') {
                        $stats['created']++;
                    } elseif ($result === 'updated') {
                        $stats['updated']++;
                    } elseif ($result === 'unchanged') {
                        $stats['unchanged']++;
                    } else {
                        $stats['skipped']++;
                    }
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    Log::warning('Softtr product upsert failed', [
                        'vendor_id' => $vendor->id,
                        'softtr_id' => $normalized['softtr_id'] ?? null,
                        'sku' => $normalized['sku'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } finally {
            $this->categoryResolver->endBulkImport();
        }

        $message = sprintf(
            '%s: %d Softtr satırı → %d ürün (SKU birleşimi), %d yeni, %d güncellendi, %d aynı, %d atlandı, %d hata.',
            $label,
            $stats['fetched'],
            $stats['deduped'],
            $stats['created'],
            $stats['updated'],
            $stats['unchanged'],
            $stats['skipped'],
            $stats['failed']
        );

        $ok = $stats['failed'] === 0
            || ($stats['created'] + $stats['updated'] + $stats['unchanged']) > 0;

        $setting->update([
            'last_sync_at' => now(),
            'last_sync_status' => $ok ? 'success' : 'failed',
            'last_sync_message' => $message,
            'last_sync_stats' => $stats,
        ]);

        return [
            'ok' => $ok,
            'message' => $message,
            'stats' => $stats,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function dedupeKey(array $data): string
    {
        $sku = mb_strtolower(trim((string) ($data['sku'] ?? '')));
        if ($sku !== '' && ! str_starts_with($sku, 'softtr-')) {
            return 'sku:' . $sku;
        }
        $barcode = mb_strtolower(trim((string) ($data['barcode'] ?? '')));
        if ($barcode !== '') {
            return 'barcode:' . $barcode;
        }
        $name = mb_strtolower(trim((string) ($data['name'] ?? '')));

        return 'name:' . $name . '|price:' . number_format((float) ($data['price'] ?? 0), 2, '.', '');
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $softtrIds
     */
    private function upsertProduct(Vendor $vendor, array $data, array $softtrIds = []): string
    {
        if ($data['price'] <= 0 && $data['offer_price'] <= 0) {
            throw new \RuntimeException('Fiyat yok: ' . $data['name']);
        }

        $softtrIds = array_values(array_unique(array_filter(array_map('strval', $softtrIds))));
        if ($softtrIds === []) {
            $softtrIds = [(string) $data['softtr_id']];
        }

        $match = $this->categoryResolver->resolve(
            $data['name'],
            $data['category_name'] !== '' ? $data['category_name'] : null,
            $data['sub_category_name'] !== '' ? $data['sub_category_name'] : null,
            $data['child_category_name'] !== '' ? $data['child_category_name'] : null,
            $data['brand'] !== '' ? $data['brand'] : null,
            $data['short_description'] !== '' ? $data['short_description'] : null,
            $vendor
        );

        $category = $match['category'] ?? null;
        if (! $category) {
            $match = $this->defaultCategoryMatch();
            $category = $match['category'] ?? null;
            // Softtr often sends brandName even when category is missing.
            if (($match['brand'] ?? null) === null && ($data['brand'] ?? '') !== '') {
                $brand = Brand::query()
                    ->where('status', 1)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['brand']))])
                    ->first();
                if ($brand) {
                    $match['brand'] = $brand;
                }
            }
        }
        if (! $category) {
            throw new \RuntimeException('Kategori eşleştirilemedi: ' . ($data['category_name'] ?: $data['name']));
        }

        $sku = trim((string) $data['sku']);
        $product = $this->findExistingProduct($vendor, $sku, $softtrIds);
        $created = false;

        if (! $product) {
            $product = new Product();
            $product->vendor_id = $vendor->id;
            $created = true;
        } else {
            $this->collapseSkuDuplicates($vendor, $sku, $product);
        }

        $name = $data['name'];
        $slugBase = ProductSlug::normalize($name);
        $shortName = mb_substr($name, 0, 30);
        $price = $data['price'] > 0 ? $data['price'] : $data['offer_price'];
        $offer = $data['offer_price'] > 0 && $data['offer_price'] < $price ? $data['offer_price'] : 0;

        $priceChanged = $created
            || abs((float) $product->price - (float) $price) > 0.0001
            || abs((float) ($product->offer_price ?? 0) - (float) $offer) > 0.0001;
        $qtyChanged = $created || (int) $product->qty !== (int) $data['qty'];

        $thumbImage = $product->thumb_image ?? '';
        if (! ProductImageUrl::hasImage($thumbImage) && $data['image_url'] !== '') {
            $candidates = [$data['image_url']];
            if (preg_match('#^(https?://[^/]+)/(Data|Uploads|uploads|images|Images|Content/Images)/(.+)$#i', $data['image_url'], $m)) {
                foreach (['Data', 'Uploads', 'uploads', 'images', 'Images', 'Content/Images'] as $folder) {
                    $candidates[] = $m[1] . '/' . $folder . '/' . $m[3];
                }
            }

            foreach (array_unique($candidates) as $candidateUrl) {
                $external = ProductImageUrl::normalizeForStorage($candidateUrl);
                if (! $external) {
                    continue;
                }
                $stored = $this->imageStorage->storeFromUrl($external, $shortName ?: 'softtr', 15);
                if ($stored) {
                    $thumbImage = $stored;
                    break;
                }
                if (! ProductImageUrl::hasImage($thumbImage)) {
                    $thumbImage = $external;
                }
            }
        }

        $canPublish = ProductImageUrl::hasImage($thumbImage);

        if (! $created && ! $priceChanged && ! $qtyChanged && ProductImageUrl::hasImage($product->thumb_image ?? null)) {
            foreach ($softtrIds as $sid) {
                VendorSofttrProductMap::query()->updateOrCreate(
                    ['vendor_id' => $vendor->id, 'softtr_product_id' => $sid],
                    [
                        'product_id' => $product->id,
                        'softtr_sku' => $sku,
                        'softtr_barcode' => $data['barcode'],
                        'last_synced_at' => now(),
                    ]
                );
            }

            return 'unchanged';
        }

        $product->short_name = $shortName;
        $product->name = $name;
        if ($created || trim((string) $product->slug) === '') {
            $product->slug = $this->uniqueSlug($slugBase, $product->id);
        }
        $product->category_id = (int) ($category->id ?? 0);
        $product->sub_category_id = (int) ($match['sub_category']->id ?? 0);
        $product->child_category_id = (int) ($match['child_category']->id ?? 0);
        $product->brand_id = (int) ($match['brand']->id ?? 0);
        $product->price = $price;
        $product->offer_price = $offer;
        $product->qty = (int) $data['qty'];
        $product->short_description = $data['short_description'] !== '' ? $data['short_description'] : $name;
        $product->long_description = $data['long_description'] !== ''
            ? $data['long_description']
            : '<p>' . e($name) . '</p>';
        $product->sku = $sku !== '' ? $sku : $product->sku;
        $barcode = trim((string) ($data['barcode'] ?? ''));
        if ($barcode !== '') {
            $product->barcode = $barcode;
        } elseif ($sku !== '' && preg_match('/^[0-9]{8,14}$/', $sku)) {
            $product->barcode = $sku;
        }
        $product->weight = is_numeric($data['weight']) ? $data['weight'] : 0;
        $product->tags = $name;
        $product->is_undefine = 1;
        $product->is_specification = 0;
        $seoTitle = $name;
        if (! empty($product->barcode) && ! str_contains($seoTitle, (string) $product->barcode)) {
            $seoTitle = mb_substr($name . ' ' . $product->barcode, 0, 190);
        }
        $product->seo_title = $seoTitle;
        $product->seo_description = mb_substr(
            $name . (! empty($product->barcode) ? ('. Barkod: ' . $product->barcode) : ''),
            0,
            320
        );
        $product->thumb_image = $thumbImage ?: ($product->thumb_image ?: '');
        $product->status = $canPublish ? 1 : 0;
        $product->approve_by_admin = $canPublish ? 1 : 0;
        $product->save();

        try {
            app(BarcodeCatalogService::class)->upsertFromProduct($product);
        } catch (\Throwable $e) {
            Log::warning('Softtr barcode catalog upsert failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }

        foreach ($softtrIds as $sid) {
            VendorSofttrProductMap::query()->updateOrCreate(
                [
                    'vendor_id' => $vendor->id,
                    'softtr_product_id' => $sid,
                ],
                [
                    'product_id' => $product->id,
                    'softtr_sku' => $sku,
                    'softtr_barcode' => $data['barcode'],
                    'last_synced_at' => now(),
                ]
            );
        }

        return $created ? 'created' : 'updated';
    }

    /**
     * Softtr list API often has no category fields — fall back to Kozmetik / Tırnak Malzemeleri.
     *
     * @return array{category: ?Category, sub_category: ?SubCategory, child_category: ?ChildCategory, brand: ?Brand}
     */
    private function defaultCategoryMatch(): array
    {
        $categoryId = (int) config('features.softtr_default_category_id', 3);
        $subName = trim((string) config('features.softtr_default_sub_category_name', 'Tırnak Malzemeleri'));

        $category = Category::query()
            ->where('id', $categoryId)
            ->where('status', 1)
            ->first()
            ?: Category::query()->where('name', 'Kozmetik')->where('status', 1)->first();

        $sub = null;
        if ($category && $subName !== '') {
            $sub = SubCategory::query()
                ->where('category_id', $category->id)
                ->where('status', 1)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($subName)])
                ->first();

            if (! $sub) {
                $sub = SubCategory::query()
                    ->where('category_id', $category->id)
                    ->where('status', 1)
                    ->where('name', 'like', '%' . $subName . '%')
                    ->orderBy('id')
                    ->first();
            }
        }

        return [
            'category' => $category,
            'sub_category' => $sub,
            'child_category' => null,
            'brand' => null,
        ];
    }

    /**
     * @param  list<string>  $softtrIds
     */
    private function findExistingProduct(Vendor $vendor, string $sku, array $softtrIds): ?Product
    {
        if ($sku !== '') {
            $bySku = Product::query()
                ->where('vendor_id', $vendor->id)
                ->whereRaw('LOWER(TRIM(sku)) = ?', [mb_strtolower(trim($sku))])
                ->orderBy('id')
                ->first();
            if ($bySku) {
                return $bySku;
            }
        }

        $map = VendorSofttrProductMap::query()
            ->where('vendor_id', $vendor->id)
            ->whereIn('softtr_product_id', $softtrIds)
            ->orderBy('id')
            ->first();

        if ($map) {
            return Product::query()
                ->where('id', $map->product_id)
                ->where('vendor_id', $vendor->id)
                ->first();
        }

        return null;
    }

    private function collapseSkuDuplicates(Vendor $vendor, string $sku, Product $keep): void
    {
        if ($sku === '') {
            return;
        }

        $dupes = Product::query()
            ->where('vendor_id', $vendor->id)
            ->whereRaw('LOWER(TRIM(sku)) = ?', [mb_strtolower(trim($sku))])
            ->where('id', '!=', $keep->id)
            ->get();

        foreach ($dupes as $dupe) {
            VendorSofttrProductMap::query()
                ->where('vendor_id', $vendor->id)
                ->where('product_id', $dupe->id)
                ->update(['product_id' => $keep->id]);

            $dupe->status = 0;
            $dupe->qty = 0;
            $dupe->sku = trim((string) $dupe->sku) . '-dup-' . $dupe->id;
            $dupe->approve_by_admin = 0;
            $dupe->save();
        }
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base !== '' ? $base : 'urun';
        $candidate = $slug;
        $i = 2;
        while (
            Product::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $slug . '-' . $i;
            $i++;
        }

        return $candidate;
    }
}
