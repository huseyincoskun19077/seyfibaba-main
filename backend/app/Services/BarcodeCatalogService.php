<?php

namespace App\Services;

use App\Models\BarcodeCatalog;
use App\Models\Product;
use App\Models\Vendor;
use App\Support\ProductImageUrl;
use App\Support\ProductSlug;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BarcodeCatalogService
{
    public function normalizeBarcode(?string $barcode): string
    {
        return preg_replace('/\s+/', '', trim((string) $barcode)) ?? '';
    }

    public function findByBarcode(string $barcode): ?BarcodeCatalog
    {
        $code = $this->normalizeBarcode($barcode);
        if ($code === '') {
            return null;
        }

        return BarcodeCatalog::query()->where('barcode', $code)->first();
    }

    /**
     * Upsert catalog rows from existing products (e.g. vendor 15). One row per barcode.
     *
     * @return array{upserted: int, skipped: int}
     */
    public function seedFromVendor(int $vendorId): array
    {
        $upserted = 0;
        $skipped = 0;

        $codes = Product::query()
            ->where('vendor_id', $vendorId)
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '')
            ->select('barcode')
            ->distinct()
            ->orderBy('barcode')
            ->pluck('barcode');

        foreach ($codes as $rawCode) {
            $code = $this->normalizeBarcode($rawCode);
            if ($code === '') {
                $skipped++;
                continue;
            }

            $candidates = Product::query()
                ->where('vendor_id', $vendorId)
                ->where('barcode', $code)
                ->get();

            if ($candidates->isEmpty()) {
                $skipped++;
                continue;
            }

            $best = $candidates->sortBy(fn (Product $p) => $this->productRank($p))->first();
            $payload = $this->payloadFromProduct($best, $vendorId);

            $row = BarcodeCatalog::query()->firstOrNew(['barcode' => $code]);
            $usage = (int) ($row->usage_count ?? 0);
            $row->fill($payload);
            $row->barcode = $code;
            $row->usage_count = $usage;
            $row->save();
            $upserted++;
        }

        return ['upserted' => $upserted, 'skipped' => $skipped];
    }

    /**
     * Lower is better.
     */
    private function productRank(Product $product): int
    {
        $local = ProductImageUrl::hasImage($product->thumb_image) && ! ProductImageUrl::isExternal($product->thumb_image) ? 0 : 1;
        $live = (int) $product->status === 1 ? 0 : 1;

        return ($local * 10) + ($live * 5);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFromProduct(Product $product, int $vendorId): array
    {
        return [
            'name' => $product->name,
            'short_name' => $product->short_name,
            'short_description' => $product->short_description,
            'long_description' => $product->long_description,
            'category_id' => (int) ($product->category_id ?? 0),
            'sub_category_id' => (int) ($product->sub_category_id ?? 0),
            'child_category_id' => (int) ($product->child_category_id ?? 0),
            'brand_id' => (int) ($product->brand_id ?? 0),
            'thumb_image' => $product->thumb_image,
            'sku' => $product->sku ?: $product->barcode,
            'tags' => $product->tags,
            'seo_title' => $product->seo_title,
            'seo_description' => $product->seo_description,
            'weight' => is_numeric($product->weight) ? $product->weight : 0,
            'source_product_id' => $product->id,
            'source_vendor_id' => $vendorId,
        ];
    }

    /**
     * Create a seller-owned product from catalog. Price/stock/offer come from seller.
     * Catalog row is never deleted when seller product is deleted.
     */
    public function createSellerProduct(
        Vendor $seller,
        BarcodeCatalog $catalog,
        float $price,
        int $qty,
        ?float $offerPrice = null
    ): Product {
        $offer = $offerPrice && $offerPrice > 0 && $offerPrice < $price ? $offerPrice : 0;
        $name = trim((string) $catalog->name) ?: ('Ürün ' . $catalog->barcode);
        $thumb = $this->copyThumbForSeller($catalog);

        $product = new Product();
        $product->vendor_id = $seller->id;
        $product->short_name = mb_substr(trim((string) ($catalog->short_name ?: $name)), 0, 30);
        $product->name = $name;
        $product->slug = $this->uniqueSlug(ProductSlug::normalize($name) ?: 'urun');
        $product->category_id = (int) $catalog->category_id;
        $product->sub_category_id = (int) $catalog->sub_category_id;
        $product->child_category_id = (int) $catalog->child_category_id;
        $product->brand_id = (int) $catalog->brand_id;
        $product->sku = $catalog->sku ?: $catalog->barcode;
        $product->barcode = $catalog->barcode;
        $product->price = $price;
        $product->offer_price = $offer;
        $product->qty = max(0, $qty);
        $product->sale_unit_qty = 1;
        $product->short_description = $catalog->short_description ?: $name;
        $product->long_description = $catalog->long_description ?: ('<p>' . e($name) . '</p>');
        $product->tags = $catalog->tags ?: $name;
        $product->weight = (float) ($catalog->weight ?? 0);
        $product->is_undefine = 1;
        $product->is_specification = 0;
        $product->seo_title = $catalog->seo_title ?: mb_substr($name . ' ' . $catalog->barcode, 0, 190);
        $product->seo_description = $catalog->seo_description
            ?: mb_substr($name . '. Barkod: ' . $catalog->barcode, 0, 320);
        $product->thumb_image = $thumb ?: '';
        $canPublish = ProductImageUrl::hasImage($product->thumb_image);
        $product->status = $canPublish ? 1 : 0;
        $product->approve_by_admin = $canPublish ? 1 : 0;
        $product->new_product = 1;
        $product->save();

        $catalog->usage_count = (int) $catalog->usage_count + 1;
        $catalog->save();

        return $product;
    }

    /**
     * Copy catalog image into a new file so seller/catalog stay independent on disk.
     */
    private function copyThumbForSeller(BarcodeCatalog $catalog): string
    {
        $src = trim((string) $catalog->thumb_image);
        if ($src === '') {
            return '';
        }

        if (ProductImageUrl::isExternal($src)) {
            return $src;
        }

        $absolute = public_path(ltrim($src, '/'));
        if (! File::exists($absolute) || filesize($absolute) === 0) {
            return $src;
        }

        $ext = pathinfo($absolute, PATHINFO_EXTENSION) ?: 'jpg';
        $dir = ProductImageStorage::DIRECTORY;
        $uploadDir = public_path($dir);
        if (! File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $basename = 'bc-' . Str::slug(mb_substr($catalog->barcode . '-' . ($catalog->short_name ?: 'urun'), 0, 40));
        $relative = $dir . '/' . $basename . date('-Y-m-d-h-i-s-') . rand(999, 9999) . '.' . $ext;
        $dest = public_path($relative);

        try {
            if (@copy($absolute, $dest)) {
                return $relative;
            }
        } catch (\Throwable $e) {
            Log::warning('Barcode catalog image copy failed', [
                'barcode' => $catalog->barcode,
                'error' => $e->getMessage(),
            ]);
        }

        return $src;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base !== '' ? $base : 'urun';
        $candidate = $slug;
        $i = 2;
        while (Product::query()->where('slug', $candidate)->exists()) {
            $candidate = $slug . '-' . $i;
            $i++;
        }

        return $candidate;
    }

    public function sellerAlreadyHasBarcode(Vendor $seller, string $barcode): ?Product
    {
        $code = $this->normalizeBarcode($barcode);
        if ($code === '') {
            return null;
        }

        return Product::query()
            ->where('vendor_id', $seller->id)
            ->where(function ($q) use ($code) {
                $q->where('barcode', $code)->orWhere('sku', $code);
            })
            ->orderByDesc('id')
            ->first();
    }
}
