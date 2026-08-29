<?php

namespace App\Services;

use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use App\Models\ProductVariantItem;

class CartPriceService
{
    /**
     * Güncel birim fiyat (varyant + flash sale dahil).
     *
     * @param  array<int, int|string>  $variantItemIds
     */
    public function resolveUnitPrice(Product $product, array $variantItemIds = []): float
    {
        $variantPrice = 0.0;
        if ($variantItemIds !== []) {
            $variantPrice = (float) ProductVariantItem::query()
                ->whereIn('id', $variantItemIds)
                ->sum('price');
        }

        $base = $product->offer_price
            ? (float) $product->offer_price
            : (float) $product->price;

        $price = $base + $variantPrice;

        return round($this->applyFlashSaleDiscount($product->id, $price), 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{items: array<int, array<string, mixed>>, subtotal: float, has_price_changes: bool}
     */
    public function refreshCartItems(array $items): array
    {
        $result = [];
        $subtotal = 0.0;
        $hasChanges = false;

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $variantItemIds = array_values(array_filter(
                array_map('intval', $item['variant_item_ids'] ?? [])
            ));

            $product = Product::query()
                ->where('id', $productId)
                ->where('status', 1)
                ->where('approve_by_admin', 1)
                ->first();

            if (! $product) {
                $result[] = [
                    'product_id' => $productId,
                    'available' => false,
                    'message' => 'Ürün artık satışta değil',
                ];
                continue;
            }

            $unitPrice = $this->resolveUnitPrice($product, $variantItemIds);
            $lineTotal = round($unitPrice * $qty, 2);
            $subtotal += $lineTotal;

            $previousUnit = isset($item['previous_unit_price'])
                ? (float) $item['previous_unit_price']
                : null;

            $priceChanged = $previousUnit !== null
                && round($previousUnit, 2) !== $unitPrice;

            if ($priceChanged) {
                $hasChanges = true;
            }

            $variants = [];
            if ($variantItemIds !== []) {
                $variantItems = ProductVariantItem::query()
                    ->whereIn('id', $variantItemIds)
                    ->get()
                    ->keyBy('id');

                foreach ($variantItemIds as $vid) {
                    $vi = $variantItems->get($vid);
                    if (! $vi) {
                        continue;
                    }
                    $variants[] = [
                        'variant_item_id' => $vi->id,
                        'variant_id' => $vi->product_variant_id,
                        'product_id' => $productId,
                        'variant_item' => [
                            'id' => $vi->id,
                            'product_variant_name' => $vi->product_variant_name,
                            'name' => $vi->name,
                            'price' => (float) $vi->price,
                        ],
                    ];
                }
            }

            $result[] = [
                'product_id' => $productId,
                'available' => true,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'price_changed' => $priceChanged,
                'previous_unit_price' => $previousUnit,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => (float) $product->price,
                    'offer_price' => $product->offer_price !== null ? (float) $product->offer_price : null,
                    'thumb_image' => $product->thumb_image,
                    'vendor_id' => $product->vendor_id,
                    'qty' => (int) $product->qty,
                ],
                'variants' => $variants,
            ];
        }

        return [
            'items' => $result,
            'subtotal' => round($subtotal, 2),
            'has_price_changes' => $hasChanges,
        ];
    }

    protected function applyFlashSaleDiscount(int $productId, float $price): float
    {
        $isFlashSale = FlashSaleProduct::query()
            ->where(['product_id' => $productId, 'status' => 1])
            ->first();

        if (! $isFlashSale) {
            return $price;
        }

        $flashSale = FlashSale::query()->first();
        if (! $flashSale || (int) $flashSale->status !== 1) {
            return $price;
        }

        if (date('Y-m-d H:i:s') > $flashSale->end_time) {
            return $price;
        }

        $offerPrice = ((float) $flashSale->offer / 100) * $price;

        return max(0, $price - $offerPrice);
    }
}
