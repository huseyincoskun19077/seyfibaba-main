<?php

namespace App\Services;

use App\Models\Category;
use App\Models\FlashSaleProduct;
use App\Models\HomeBlock;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class HomeBlockService
{
    public function activeBlocks(?string $platform = null): Collection
    {
        if (! Schema::hasTable('home_blocks')) {
            return collect();
        }

        $q = HomeBlock::query()
            ->where('status', true)
            ->orderBy('serial')
            ->orderBy('id');

        if ($platform === 'web') {
            $q->where('show_on_web', true);
        } elseif ($platform === 'mobile') {
            $q->where('show_on_mobile', true);
        }

        return $q->get();
    }

    /**
     * API için çözülmüş blok listesi.
     *
     * @return array<int, array<string, mixed>>
     */
    public function resolvedBlocks(?string $platform = null): array
    {
        $blocks = $this->activeBlocks($platform);
        $out = [];

        foreach ($blocks as $block) {
            $item = [
                'id' => $block->id,
                'title' => $block->title,
                'type' => $block->type,
                'feed' => $block->feed,
                'image' => $block->image,
                'link' => $block->link,
                'mobile_link' => $block->mobile_link,
                'see_all_url' => $block->see_all_url,
                'limit' => (int) ($block->limit_count ?: 12),
                'serial' => (int) $block->serial,
                'products' => [],
                'categories' => [],
            ];

            if ($block->type === 'campaign') {
                // Kampanyada görsel yoksa (seed placeholder) atla
                if (! $block->image) {
                    continue;
                }
                $out[] = $item;
                continue;
            }

            if ($block->type === 'product_feed' || $block->type === 'all_products') {
                $item['products'] = $this->resolveProducts($block)->values()->all();
                if ($block->type === 'product_feed' && empty($item['products']) && empty($block->decodeIds($block->product_ids))) {
                    // boş feed (weekend vs.) — yine de başlık gösterebiliriz; ürün yoksa gizle
                    continue;
                }
                $out[] = $item;
                continue;
            }

            if ($block->type === 'category_grid') {
                $item['categories'] = $this->resolveCategories($block)->values()->all();
                $out[] = $item;
                continue;
            }

            if ($block->type === 'flash_sale') {
                $item['products'] = $this->resolveFlashProducts((int) $block->limit_count)->values()->all();
                $out[] = $item;
                continue;
            }

            if ($block->type === 'brands') {
                $out[] = $item;
                continue;
            }

            $out[] = $item;
        }

        return $out;
    }

    private function productSelect(): array
    {
        return [
            'id', 'name', 'short_name', 'slug', 'thumb_image', 'qty', 'sale_unit_qty',
            'sold_qty', 'price', 'offer_price', 'vendor_id', 'category_id', 'brand_id',
            'is_undefine', 'is_featured', 'new_product', 'is_top', 'is_best',
        ];
    }

    private function resolveProducts(HomeBlock $block): Collection
    {
        $limit = min(48, max(4, (int) ($block->limit_count ?: 12)));
        $select = $this->productSelect();
        $ids = $block->decodeIds($block->product_ids);

        if ($ids || $block->feed === 'custom' || $block->feed === 'weekend') {
            if ($ids) {
                return Product::query()
                    ->select($select)
                    ->where('status', 1)
                    ->where('approve_by_admin', 1)
                    ->whereIn('id', $ids)
                    ->get()
                    ->sortBy(fn ($p) => array_search((int) $p->id, $ids, true))
                    ->take($limit)
                    ->values();
            }
            if ($block->feed === 'weekend' || $block->feed === 'custom') {
                return collect();
            }
        }

        $q = Product::query()
            ->select($select)
            ->where('status', 1)
            ->where('approve_by_admin', 1);

        $categoryIds = $block->decodeIds($block->category_ids);
        if ($categoryIds) {
            $q->where(function ($qq) use ($categoryIds) {
                $qq->whereIn('category_id', $categoryIds)
                    ->orWhereIn('sub_category_id', $categoryIds)
                    ->orWhereIn('child_category_id', $categoryIds);
            });
        }

        if ($block->type === 'all_products') {
            return $q->inRandomOrder()->take($limit)->get();
        }

        switch ($block->feed) {
            case 'discounted':
                $q->where('offer_price', '>', 0)
                    ->whereColumn('offer_price', '<', 'price')
                    ->orderByDesc('id');
                break;
            case 'featured':
                $q->where('is_featured', 1)->orderByDesc('id');
                break;
            case 'new':
                $q->where('new_product', 1)->orderByDesc('id');
                break;
            case 'best':
                $q->where('is_best', 1)->orderByDesc('id');
                break;
            case 'popular':
            default:
                $q->where('is_top', 1)->orderByDesc('id');
                break;
        }

        $rows = $q->take($limit)->get();
        if ($rows->isEmpty() && $block->feed === 'popular') {
            return Product::query()
                ->select($select)
                ->where('status', 1)
                ->where('approve_by_admin', 1)
                ->orderByDesc('sold_qty')
                ->take($limit)
                ->get();
        }

        return $rows;
    }

    private function resolveCategories(HomeBlock $block): Collection
    {
        $limit = min(30, max(4, (int) ($block->limit_count ?: 15)));
        $ids = $block->decodeIds($block->category_ids);

        $q = Category::query()->where('status', 1);
        if ($ids) {
            $q->whereIn('id', $ids);
        }

        return $q->ordered()->take($limit)->get(['id', 'name', 'slug', 'image', 'icon']);
    }

    private function resolveFlashProducts(int $limit): Collection
    {
        $limit = min(24, max(4, $limit ?: 12));
        $select = $this->productSelect();

        if (! Schema::hasTable('flash_sale_products')) {
            return collect();
        }

        $productIds = FlashSaleProduct::query()
            ->where('status', 1)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($productIds === []) {
            return collect();
        }

        return Product::query()
            ->select($select)
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->whereIn('id', $productIds)
            ->take($limit)
            ->get();
    }
}
