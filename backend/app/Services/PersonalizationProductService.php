<?php

namespace App\Services;

use App\Models\PersonalizationShowcase;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PersonalizationProductService
{
    public function productsForUser(?User $user, int $limit = 16): array
    {
        $limit = min(24, max(4, $limit));
        $title = 'Sana Özel';
        $source = 'popular';
        $products = collect();

        if ($user && Schema::hasTable('personalization_showcases')) {
            $type = trim((string) ($user->business_type ?? ''));
            $status = trim((string) ($user->business_status ?? ''));
            $isOpening = in_array($status, ['opening_soon', 'planning'], true);

            if ($type !== '') {
                $showcase = PersonalizationShowcase::query()
                    ->where('business_type', $type)
                    ->where('status', true)
                    ->first();

                if ($showcase) {
                    $title = trim((string) $showcase->title) ?: $title;
                    $products = $this->resolveShowcaseProducts($showcase, $isOpening, $limit);
                    if ($products->isNotEmpty()) {
                        $source = 'personalized';
                    }
                }
            }
        }

        if ($products->isEmpty()) {
            $products = $this->popularFallback($limit);
            $source = 'popular';
            if (! $user || empty($user->business_type)) {
                $title = 'Popüler ürünler';
            }
        }

        return [
            'title' => $title,
            'source' => $source,
            'business_type' => $user->business_type ?? null,
            'business_status' => $user->business_status ?? null,
            'products' => $products->values(),
        ];
    }

    private function resolveShowcaseProducts(
        PersonalizationShowcase $showcase,
        bool $isOpening,
        int $limit
    ): Collection {
        $categoryIds = $showcase->decodeIds($showcase->category_ids);
        $productIds = $showcase->decodeIds($showcase->product_ids);
        $vendorIds = $showcase->decodeIds($showcase->vendor_ids);

        if ($isOpening) {
            $oCats = $showcase->decodeIds($showcase->opening_category_ids);
            $oProds = $showcase->decodeIds($showcase->opening_product_ids);
            $oVendors = $showcase->decodeIds($showcase->opening_vendor_ids);
            if ($oCats || $oProds || $oVendors) {
                $categoryIds = $oCats ?: $categoryIds;
                $productIds = $oProds ?: $productIds;
                $vendorIds = $oVendors ?: $vendorIds;
            }
        }

        $select = [
            'id', 'name', 'short_name', 'slug', 'thumb_image', 'qty', 'sale_unit_qty',
            'sold_qty', 'price', 'offer_price', 'vendor_id', 'category_id', 'brand_id',
            'is_undefine', 'is_featured', 'new_product', 'is_top', 'is_best',
        ];

        $collected = collect();

        if ($productIds) {
            $byId = Product::query()
                ->select($select)
                ->where('status', 1)
                ->where('approve_by_admin', 1)
                ->whereIn('id', $productIds)
                ->get()
                ->sortBy(fn ($p) => array_search((int) $p->id, $productIds, true))
                ->values();
            $collected = $collected->concat($byId);
        }

        if ($vendorIds && $collected->count() < $limit) {
            $byVendor = Product::query()
                ->select($select)
                ->where('status', 1)
                ->where('approve_by_admin', 1)
                ->whereIn('vendor_id', $vendorIds)
                ->orderByDesc('id')
                ->take($limit * 2)
                ->get();
            $collected = $collected->concat($byVendor);
        }

        if ($categoryIds && $collected->count() < $limit) {
            $byCat = Product::query()
                ->select($select)
                ->where('status', 1)
                ->where('approve_by_admin', 1)
                ->where(function ($q) use ($categoryIds) {
                    $q->whereIn('category_id', $categoryIds)
                        ->orWhereIn('sub_category_id', $categoryIds)
                        ->orWhereIn('child_category_id', $categoryIds);
                })
                ->orderByDesc('is_top')
                ->orderByDesc('id')
                ->take($limit * 2)
                ->get();
            $collected = $collected->concat($byCat);
        }

        return $collected
            ->unique('id')
            ->take($limit)
            ->values();
    }

    private function popularFallback(int $limit): Collection
    {
        $select = [
            'id', 'name', 'short_name', 'slug', 'thumb_image', 'qty', 'sale_unit_qty',
            'sold_qty', 'price', 'offer_price', 'vendor_id', 'category_id', 'brand_id',
            'is_undefine', 'is_featured', 'new_product', 'is_top', 'is_best',
        ];

        $top = Product::query()
            ->select($select)
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->where('is_top', 1)
            ->orderByDesc('id')
            ->take($limit)
            ->get();

        if ($top->isNotEmpty()) {
            return $top;
        }

        // is_top yoksa aktif ürünlerden doldur — şerit boş kalmasın
        return Product::query()
            ->select($select)
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->orderByDesc('sold_qty')
            ->orderByDesc('id')
            ->take($limit)
            ->get();
    }
}
