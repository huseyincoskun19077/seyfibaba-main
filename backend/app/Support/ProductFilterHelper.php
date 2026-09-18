<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductFilterHelper
{
    /**
     * Sidebar'da gosterilecek varyant gruplari.
     * Her grup icin katalogdaki tum secenek isimleri (distinct) listelenir.
     */
    public static function filterableVariants(): Collection
    {
        $productNames = Product::query()
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->pluck('name')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->filter()
            ->unique();

        $sharedVariantNames = ProductVariant::query()
            ->where('status', 1)
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(DISTINCT product_id) > 1')
            ->pluck('name');

        $standardNames = collect([
            'Renk', 'Beden', 'Boyut', 'Model', 'Kapasite', 'Malzeme',
            'Tip', 'Numara', 'Ebat', 'Guc', 'Güç', 'Voltaj', 'Renk Seçenekleri',
            'Ölçü', 'Olcu', 'Genişlik', 'Yükseklik',
        ]);

        $allowedNames = ProductVariant::query()
            ->where('status', 1)
            ->where(function ($query) use ($sharedVariantNames, $standardNames) {
                $query->whereIn('name', $sharedVariantNames)
                    ->orWhereIn('name', $standardNames);
            })
            ->pluck('name')
            ->unique()
            ->filter(function ($name) use ($productNames) {
                return ! $productNames->contains(mb_strtolower(trim((string) $name)));
            })
            ->values();

        if ($allowedNames->isEmpty()) {
            return collect();
        }

        return $allowedNames
            ->map(function ($groupName, $index) {
                $itemNames = ProductVariantItem::query()
                    ->where('status', 1)
                    ->where(function ($q) use ($groupName) {
                        $q->where('product_variant_name', $groupName)
                            ->orWhereHas('variant', function ($vq) use ($groupName) {
                                $vq->where('name', $groupName)->where('status', 1);
                            });
                    })
                    ->whereHas('product', function ($pq) {
                        $pq->where('status', 1)->where('approve_by_admin', 1);
                    })
                    ->distinct()
                    ->orderBy('name')
                    ->pluck('name')
                    ->filter(fn ($n) => trim((string) $n) !== '')
                    ->unique(fn ($n) => mb_strtolower(trim((string) $n)))
                    ->values();

                if ($itemNames->isEmpty()) {
                    return null;
                }

                $items = $itemNames->values()->map(function ($name, $i) use ($index) {
                    return (object) [
                        'id' => ($index + 1) * 1000 + $i + 1,
                        'name' => $name,
                        'price' => 0,
                        'product_variant_id' => $index + 1,
                    ];
                });

                return (object) [
                    'id' => $index + 1,
                    'name' => $groupName,
                    'active_variant_items' => $items,
                ];
            })
            ->filter()
            ->values();
    }

    public static function applySorting(Builder $query, ?string $shortingId): Builder
    {
        return match ((string) $shortingId) {
            '2' => $query->orderByRaw('COALESCE(NULLIF(offer_price, 0), price) ASC'),
            '3' => $query->orderByRaw('COALESCE(NULLIF(offer_price, 0), price) DESC'),
            default => $query->orderBy('id', 'desc'),
        };
    }

    public static function applyPriceFilter(Builder $query, $minPrice, $maxPrice): Builder
    {
        if (is_numeric($minPrice) && (float) $minPrice > 0) {
            $query->whereRaw('COALESCE(NULLIF(offer_price, 0), price) >= ?', [(float) $minPrice]);
        }

        if (is_numeric($maxPrice) && (float) $maxPrice > 0) {
            $query->whereRaw('COALESCE(NULLIF(offer_price, 0), price) <= ?', [(float) $maxPrice]);
        }

        return $query;
    }

    /** Indirimli urunler: offer_price > 0 ve offer_price < price */
    public static function applyDiscountedFilter(Builder $query): Builder
    {
        return $query
            ->whereNotNull('offer_price')
            ->where('offer_price', '>', 0)
            ->whereColumn('offer_price', '<', 'price');
    }
}
