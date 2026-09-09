<?php

namespace App\Services\Sentos;

use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorSentosCategoryMap;
use App\Models\VendorSentosProductMap;
use App\Models\VendorSentosSetting;
use App\Services\ImportCategoryResolver;
use App\Services\ProductImageStorage;
use App\Support\ProductImageUrl;
use App\Support\ProductSlug;
use Illuminate\Support\Facades\Log;

/**
 * Pulls Sentos products into a single vendor catalog.
 * Does not touch other vendors, checkout, or payment.
 */
class SentosProductSyncService
{
    public function __construct(
        private SentosApiClient $client,
        private SentosProductNormalizer $normalizer,
        private ImportCategoryResolver $categoryResolver,
        private ProductImageStorage $imageStorage,
    ) {}

    /**
     * @return array{
     *   ok: bool,
     *   message: string,
     *   stats: array<string, int>
     * }
     */
    public function syncVendor(VendorSentosSetting $setting): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $stats = [
            'fetched' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        if (! $setting->is_enabled || ! $setting->hasCredentials()) {
            return [
                'ok' => false,
                'message' => 'Sentos kapalı veya API bilgisi eksik.',
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

        $setting->update([
            'last_sync_at' => now(),
            'last_sync_status' => 'processing',
            'last_sync_message' => 'Ürünler Sentos’tan çekiliyor…',
            'last_sync_stats' => $stats,
        ]);

        try {
            $rawProducts = $this->client->listAllProducts($setting);
        } catch (\Throwable $e) {
            Log::warning('Sentos product list failed', [
                'vendor_id' => $vendor->id,
                'error' => $e->getMessage(),
            ]);

            $setting->update([
                'last_sync_at' => now(),
                'last_sync_status' => 'failed',
                'last_sync_message' => 'Ürün listesi alınamadı: ' . $e->getMessage(),
                'last_sync_stats' => $stats,
            ]);

            return [
                'ok' => false,
                'message' => 'Ürün listesi alınamadı: ' . $e->getMessage(),
                'stats' => $stats,
            ];
        }

        $this->categoryResolver->beginBulkImport(max(count($rawProducts), 1));

        try {
            foreach ($rawProducts as $raw) {
                if (! is_array($raw)) {
                    continue;
                }

                $normalized = $this->normalizer->normalize($raw);
                if (! $normalized) {
                    $stats['skipped']++;
                    continue;
                }

                $stats['fetched']++;

                try {
                    $result = $this->upsertProduct($vendor, $normalized);
                    if ($result === 'created') {
                        $stats['created']++;
                    } elseif ($result === 'updated') {
                        $stats['updated']++;
                    } else {
                        $stats['skipped']++;
                    }
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    Log::warning('Sentos product upsert failed', [
                        'vendor_id' => $vendor->id,
                        'sentos_id' => $normalized['sentos_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } finally {
            $this->categoryResolver->endBulkImport();
        }

        $message = sprintf(
            'Senkron tamam: %d çekildi, %d yeni, %d güncellendi, %d atlandı, %d hata.',
            $stats['fetched'],
            $stats['created'],
            $stats['updated'],
            $stats['skipped'],
            $stats['failed']
        );

        $ok = $stats['failed'] === 0 || ($stats['created'] + $stats['updated']) > 0;

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
    private function upsertProduct(Vendor $vendor, array $data): string
    {
        if ($data['price'] <= 0 && $data['offer_price'] <= 0) {
            throw new \RuntimeException('Fiyat yok: ' . $data['name']);
        }

        $categoryIds = $this->resolveCategoryIds($vendor, $data);
        if ($categoryIds === null) {
            throw new \RuntimeException('Kategori eşleştirilemedi: ' . ($data['category_name'] ?: $data['name']));
        }

        $map = VendorSentosProductMap::query()
            ->where('vendor_id', $vendor->id)
            ->where('sentos_product_id', $data['sentos_id'])
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
            $external = ProductImageUrl::normalizeForStorage($data['image_url']);
            if ($external) {
                $thumbImage = $external;
            } else {
                $stored = $this->imageStorage->storeFromUrl($data['image_url'], $shortName ?: 'sentos', 20);
                if ($stored) {
                    $thumbImage = $stored;
                }
            }
        }

        $canPublish = ProductImageUrl::hasImage($thumbImage);

        $product->short_name = $shortName;
        $product->name = $name;
        $product->slug = $this->uniqueSlug($slugBase, $product->id);
        $product->category_id = $categoryIds['category_id'];
        $product->sub_category_id = $categoryIds['sub_category_id'];
        $product->child_category_id = $categoryIds['child_category_id'];
        $product->brand_id = $categoryIds['brand_id'];
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

        VendorSentosProductMap::query()->updateOrCreate(
            [
                'vendor_id' => $vendor->id,
                'sentos_product_id' => $data['sentos_id'],
            ],
            [
                'product_id' => $product->id,
                'sentos_sku' => $data['sku'],
                'last_synced_at' => now(),
            ]
        );

        return $created ? 'created' : 'updated';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{category_id:int,sub_category_id:int,child_category_id:int,brand_id:int}|null
     */
    private function resolveCategoryIds(Vendor $vendor, array $data): ?array
    {
        $key = $data['category_key'] ?: ('path:' . strtolower($data['category_name'] ?: 'unknown'));

        $existing = VendorSentosCategoryMap::query()
            ->where('vendor_id', $vendor->id)
            ->where('sentos_category_key', $key)
            ->first();

        if ($existing) {
            return [
                'category_id' => (int) $existing->category_id,
                'sub_category_id' => (int) $existing->sub_category_id,
                'child_category_id' => (int) $existing->child_category_id,
                'brand_id' => 0,
            ];
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

        $category = $match['category'];
        if (! $category) {
            return null;
        }

        VendorSentosCategoryMap::query()->create([
            'vendor_id' => $vendor->id,
            'sentos_category_key' => $key,
            'sentos_category_name' => trim(implode(' > ', array_filter([
                $data['category_name'],
                $data['sub_category_name'],
                $data['child_category_name'],
            ]))) ?: $data['category_name'],
            'category_id' => $category->id,
            'sub_category_id' => $match['sub_category']?->id ?? 0,
            'child_category_id' => $match['child_category']?->id ?? 0,
        ]);

        return [
            'category_id' => (int) $category->id,
            'sub_category_id' => (int) ($match['sub_category']?->id ?? 0),
            'child_category_id' => (int) ($match['child_category']?->id ?? 0),
            'brand_id' => (int) ($match['brand']?->id ?? 0),
        ];
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base ?: 'urun';
        $candidate = $slug;
        $i = 1;

        while (
            Product::query()
                ->where('slug', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $slug . '-' . $i;
            $i++;
        }

        return $candidate;
    }
}
