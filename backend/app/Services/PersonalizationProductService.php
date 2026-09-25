<?php

namespace App\Services;

use App\Models\PersonalizationShowcase;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PersonalizationProductService
{
    public const HOME_LIMIT = 12;

    /**
     * @param  string  $scope  home = anasayfa (yalnız admin seçimi), all = Tümünü gör (+ opsiyonel yüksek görüntülenme)
     */
    public function productsForUser(?User $user, int $limit = self::HOME_LIMIT, string $scope = 'home'): array
    {
        $scope = $scope === 'all' ? 'all' : 'home';
        $title = 'Sana Özel';
        $source = 'popular';
        $includeHighViews = false;
        $products = collect();
        $showcase = null;

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
                    $includeHighViews = (bool) $showcase->include_high_views;
                    $homeLimit = $showcase->homeLimit();
                    $limit = $scope === 'home'
                        ? $homeLimit
                        : min(48, max($homeLimit, $limit));

                    $products = $this->resolveShowcaseProducts($showcase, $isOpening, $limit, $scope);
                    if ($products->isNotEmpty()) {
                        $source = 'personalized';
                    }
                }
            }
        }

        // Anasayfa: sektörü olmayan misafir / vitrin yoksa popüler (eski davranış)
        // Sektörü olan kullanıcıda admin seçimi yoksa boş bırak — otomatik karışık ürün gösterme
        if ($products->isEmpty()) {
            $hasType = $user && trim((string) ($user->business_type ?? '')) !== '';
            if ($scope === 'home' && ! $hasType) {
                $products = $this->popularFallback(self::HOME_LIMIT);
                $source = 'popular';
                $title = 'Popüler ürünler';
            } elseif ($scope === 'all' && ! $hasType) {
                $products = $this->popularFallback(min(48, max(12, $limit)));
                $source = 'popular';
                $title = 'Popüler ürünler';
            } elseif ($hasType && $source !== 'personalized') {
                $source = 'personalized';
                $title = $title ?: 'Sana Özel';
            }
        }

        return [
            'title' => $title,
            'source' => $source,
            'scope' => $scope,
            'include_high_views' => $includeHighViews,
            'home_limit' => $showcase ? $showcase->homeLimit() : self::HOME_LIMIT,
            'business_type' => $user->business_type ?? null,
            'business_status' => $user->business_status ?? null,
            'products' => $products->values(),
        ];
    }

    /**
     * Ürün listeleme (highlight=sana_ozel) için ID listesi.
     *
     * @return array{0: string, 1: int[]}
     */
    public function productIdsForUser(?User $user, string $scope = 'all'): array
    {
        $payload = $this->productsForUser($user, $scope === 'home' ? self::HOME_LIMIT : 48, $scope);
        $ids = collect($payload['products'])->pluck('id')->map(fn ($id) => (int) $id)->filter()->values()->all();

        return [$payload['title'] ?? 'Sana Özel', $ids];
    }

    private function resolveShowcaseProducts(
        PersonalizationShowcase $showcase,
        bool $isOpening,
        int $limit,
        string $scope
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

        $select = $this->productSelect(false);
        $collected = collect();

        // 1) Admin pin ürünler (sıra korunur)
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

        // 2) Seçili satıcılar
        if ($vendorIds && $collected->count() < $limit) {
            $need = $limit - $collected->count();
            $byVendor = Product::query()
                ->select($select)
                ->where('status', 1)
                ->where('approve_by_admin', 1)
                ->whereIn('vendor_id', $vendorIds)
                ->when($collected->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $collected->pluck('id')))
                ->orderByDesc('id')
                ->take($need)
                ->get();
            $collected = $collected->concat($byVendor);
        }

        // 3) Seçili kategori / alt / child
        if ($categoryIds && $collected->count() < $limit) {
            $need = $limit - $collected->count();
            $byCat = Product::query()
                ->select($select)
                ->where('status', 1)
                ->where('approve_by_admin', 1)
                ->where(function ($q) use ($categoryIds) {
                    $q->whereIn('category_id', $categoryIds)
                        ->orWhereIn('sub_category_id', $categoryIds)
                        ->orWhereIn('child_category_id', $categoryIds);
                })
                ->when($collected->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $collected->pluck('id')))
                ->orderByDesc('is_top')
                ->orderByDesc('id')
                ->take(max($need, $scope === 'all' ? $limit : $need))
                ->get();
            $collected = $collected->concat($byCat);
        }

        $collected = $collected->unique('id')->values();

        // Anasayfa: yalnızca admin seçimi (limit kadar)
        if ($scope === 'home') {
            return $collected->take($limit)->values();
        }

        // Tümünü gör: admin seçimleri + (tikliyse) yüksek görüntülenme
        $collected = $collected->take($limit)->values();

        if ($showcase->include_high_views) {
            $extra = min(24, max(12, $limit - $collected->count()));
            if ($extra < 12) {
                $extra = 12;
            }
            $high = $this->highViewProducts($extra, $collected->pluck('id')->all());
            $collected = $collected->concat($high)->unique('id')->take($limit)->values();
        }

        return $collected->take($limit)->values();
    }

    private function highViewProducts(int $limit, array $excludeIds = []): Collection
    {
        if ($limit <= 0) {
            return collect();
        }

        if (Schema::hasTable('product_views')) {
            return Product::query()
                ->select($this->productSelect(true))
                ->where('products.status', 1)
                ->where('products.approve_by_admin', 1)
                ->leftJoin('product_views', 'product_views.product_id', '=', 'products.id')
                ->when($excludeIds !== [], fn ($qq) => $qq->whereNotIn('products.id', $excludeIds))
                ->orderByDesc(DB::raw('COALESCE(product_views.view_count, 0)'))
                ->orderByDesc('products.id')
                ->take($limit)
                ->get();
        }

        return Product::query()
            ->select($this->productSelect(false))
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->when($excludeIds !== [], fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->orderByDesc('sold_qty')
            ->orderByDesc('id')
            ->take($limit)
            ->get();
    }

    private function productSelect(bool $qualified = false): array
    {
        $cols = [
            'id', 'name', 'short_name', 'slug', 'thumb_image',
            'qty', 'sale_unit_qty', 'sold_qty', 'price', 'offer_price',
            'vendor_id', 'category_id', 'brand_id',
            'is_undefine', 'is_featured', 'new_product', 'is_top', 'is_best',
        ];

        if (! $qualified) {
            return $cols;
        }

        return array_map(static fn ($c) => 'products.'.$c, $cols);
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
