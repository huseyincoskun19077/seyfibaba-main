<?php

namespace App\Services\Softtr;

use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorSofttrProductMap;
use App\Models\VendorSofttrSetting;
use App\Services\ImportCategoryResolver;
use App\Services\ProductImageStorage;
use App\Support\ProductImageUrl;
use App\Support\ProductSlug;
use Illuminate\Support\Facades\Log;

/**
 * Pull Softtr products into one vendor catalog (one-way Softtr → Seyfibaba).
 * Does not call Softtr updateStokAndPrice (that writes INTO Softtr).
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
     * Hourly: create new Softtr products + update price/stock for mapped ones.
     *
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
            // Softtr: max 100/page; always page with ≥2s gap (manual + hourly).
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

        $maps = VendorSofttrProductMap::query()
            ->where('vendor_id', $vendor->id)
            ->get()
            ->keyBy(fn (VendorSofttrProductMap $m) => (string) $m->softtr_product_id);

        $shopOrigin = $this->client->shopOrigin($setting);

        $this->categoryResolver->beginBulkImport(max(count($rawProducts), 1));

        try {
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
                $map = $maps->get((string) $normalized['softtr_id']);

                try {
                    if (! $map) {
                        $result = $this->upsertProduct($vendor, $normalized);
                        if ($result === 'created') {
                            $stats['created']++;
                            $newMap = VendorSofttrProductMap::query()
                                ->where('vendor_id', $vendor->id)
                                ->where('softtr_product_id', $normalized['softtr_id'])
                                ->first();
                            if ($newMap) {
                                $maps->put((string) $normalized['softtr_id'], $newMap);
                            }
                        } elseif ($result === 'updated') {
                            $stats['updated']++;
                        } else {
                            $stats['skipped']++;
                        }
                        continue;
                    }

                    if ($full) {
                        $result = $this->upsertProduct($vendor, $normalized);
                        if ($result === 'updated') {
                            $stats['updated']++;
                        } elseif ($result === 'created') {
                            $stats['created']++;
                        } else {
                            $stats['skipped']++;
                        }
                    } else {
                        $result = $this->updateMappedPriceStock($vendor, $map, $normalized);
                        if ($result === 'updated') {
                            $stats['updated']++;
                        } elseif ($result === 'unchanged') {
                            $stats['unchanged']++;
                        } else {
                            $stats['skipped']++;
                        }
                    }
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    Log::warning('Softtr product upsert failed', [
                        'vendor_id' => $vendor->id,
                        'softtr_id' => $normalized['softtr_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } finally {
            $this->categoryResolver->endBulkImport();
        }

        $message = sprintf(
            '%s: %d çekildi, %d yeni, %d güncellendi, %d aynı, %d atlandı, %d hata.',
            $label,
            $stats['fetched'],
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
    private function updateMappedPriceStock(Vendor $vendor, VendorSofttrProductMap $map, array $data): string
    {
        $price = $data['price'] > 0 ? $data['price'] : $data['offer_price'];
        if ($price <= 0) {
            return 'skipped';
        }

        $offer = $data['offer_price'] > 0 && $data['offer_price'] < $price ? $data['offer_price'] : 0;
        $qty = (int) $data['qty'];

        $product = Product::query()
            ->where('id', $map->product_id)
            ->where('vendor_id', $vendor->id)
            ->first();

        if (! $product) {
            return 'skipped';
        }

        $priceChanged = abs((float) $product->price - (float) $price) > 0.0001
            || abs((float) $product->offer_price - (float) $offer) > 0.0001;
        $qtyChanged = (int) $product->qty !== $qty;

        if (! $priceChanged && ! $qtyChanged) {
            $map->last_synced_at = now();
            $map->save();

            return 'unchanged';
        }

        $product->price = $price;
        $product->offer_price = $offer;
        $product->qty = $qty;
        $product->save();

        $map->softtr_sku = $data['sku'] !== '' ? $data['sku'] : $map->softtr_sku;
        $map->softtr_barcode = $data['barcode'] !== '' ? $data['barcode'] : $map->softtr_barcode;
        $map->last_synced_at = now();
        $map->save();

        return 'updated';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertProduct(Vendor $vendor, array $data): string
    {
        if ($data['price'] <= 0 && $data['offer_price'] <= 0) {
            throw new \RuntimeException('Fiyat yok: ' . $data['name']);
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
            throw new \RuntimeException('Kategori eşleştirilemedi: ' . ($data['category_name'] ?: $data['name']));
        }

        $map = VendorSofttrProductMap::query()
            ->where('vendor_id', $vendor->id)
            ->where('softtr_product_id', $data['softtr_id'])
            ->first();

        $product = null;
        $created = false;

        if ($map) {
            $product = Product::query()
                ->where('id', $map->product_id)
                ->where('vendor_id', $vendor->id)
                ->first();
        }

        if (! $product && ! empty($data['sku'])) {
            $product = Product::query()
                ->where('vendor_id', $vendor->id)
                ->where('sku', $data['sku'])
                ->first();
        }

        if (! $product) {
            $product = new Product();
            $product->vendor_id = $vendor->id;
            $created = true;
        }

        $name = $data['name'];
        $slugBase = ProductSlug::normalize($name);
        $shortName = mb_substr($name, 0, 30);
        $price = $data['price'] > 0 ? $data['price'] : $data['offer_price'];
        $offer = $data['offer_price'] > 0 && $data['offer_price'] < $price ? $data['offer_price'] : 0;

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
                // Softtr CDN URL — show even if download blocked.
                if (! ProductImageUrl::hasImage($thumbImage)) {
                    $thumbImage = $external;
                }
            }
        }

        $canPublish = ProductImageUrl::hasImage($thumbImage);

        $product->short_name = $shortName;
        $product->name = $name;
        $product->slug = $this->uniqueSlug($slugBase, $product->id);
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
        $product->sku = $data['sku'];
        $product->weight = is_numeric($data['weight']) ? $data['weight'] : 0;
        $product->tags = $name;
        $product->is_undefine = 1;
        $product->is_specification = 0;
        $product->seo_title = $name;
        $product->seo_description = mb_substr($name, 0, 155);
        $product->thumb_image = $thumbImage ?: ($product->thumb_image ?: '');
        $product->status = $canPublish ? 1 : 0;
        $product->approve_by_admin = $canPublish ? 1 : 0;
        $product->save();

        VendorSofttrProductMap::query()->updateOrCreate(
            [
                'vendor_id' => $vendor->id,
                'softtr_product_id' => $data['softtr_id'],
            ],
            [
                'product_id' => $product->id,
                'softtr_sku' => $data['sku'],
                'softtr_barcode' => $data['barcode'],
                'last_synced_at' => now(),
            ]
        );

        return $created ? 'created' : 'updated';
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
