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

    /**
     * Sıralama:
     * - 2 / price_asc → fiyat artan
     * - 3 / price_desc → fiyat azalan
     * - newest / 1 → en yeni (id desc)
     * - recommended / boş → satıcı çeşitliliği (sayfalar arası kararlı)
     */
    public static function applySorting(Builder $query, ?string $shortingId): Builder
    {
        $key = strtolower(trim((string) $shortingId));

        return match ($key) {
            '2', 'price_asc' => $query->orderByRaw('COALESCE(NULLIF(offer_price, 0), price) ASC')->orderByDesc('id'),
            '3', 'price_desc' => $query->orderByRaw('COALESCE(NULLIF(offer_price, 0), price) DESC')->orderByDesc('id'),
            '1', 'newest' => $query->orderByDesc('id'),
            default => self::applyVendorDiversitySort($query),
        };
    }

    /**
     * Önerilen: her satıcının en yeni ürünü önce (rank 1), sonra 2., 3.…
     * Kararlı — sayfalar arası tekrar/atlama yok.
     * MySQL 8 / MariaDB / SQLite: window ROW_NUMBER (correlated COUNT yok).
     */
    public static function applyVendorDiversitySort(Builder $query): Builder
    {
        $table = $query->getModel()->getTable();
        $query->reorder();

        $driver = $query->getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
            // Tek geçişli sıralama — DEPENDENT SUBQUERY / satır başı COUNT yok
            return $query
                ->orderByRaw(
                    "ROW_NUMBER() OVER (PARTITION BY {$table}.vendor_id ORDER BY {$table}.id DESC)"
                )
                ->orderByDesc($table.'.id');
        }

        // Eski sürücüler için son çare (yavaş)
        return $query
            ->orderByRaw(
                "(SELECT COUNT(*) FROM {$table} AS _vd WHERE _vd.vendor_id = {$table}.vendor_id AND _vd.id >= {$table}.id AND _vd.deleted_at IS NULL AND _vd.status = 1 AND _vd.approve_by_admin = 1) ASC"
            )
            ->orderByDesc($table.'.id');
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
