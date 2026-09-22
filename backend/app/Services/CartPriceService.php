<?php

namespace App\Services;

use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductVariantItem;
use Illuminate\Support\Facades\Schema;

class CartPriceService
{
    /**
     * Sepet satırından variant_item_id listesi.
     *
     * @param  array<string, mixed>|object  $cartProduct
     * @return array<int, int>
     */
    public function extractVariantItemIds($cartProduct): array
    {
        $variants = [];
        if (is_array($cartProduct)) {
            $variants = $cartProduct['variants'] ?? [];
        } elseif (is_object($cartProduct)) {
            $variants = $cartProduct->variants ?? [];
            if (is_object($variants) && method_exists($variants, 'toArray')) {
                $variants = $variants->toArray();
            }
        }

        if (! is_array($variants)) {
            return [];
        }

        $ids = [];
        foreach ($variants as $row) {
            if (is_array($row)) {
                $id = (int) ($row['variant_item_id'] ?? $row['variantItemId'] ?? 0);
            } elseif (is_object($row)) {
                $id = (int) ($row->variant_item_id ?? $row->variantItemId ?? 0);
            } else {
                $id = 0;
            }
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Güncel birim fiyat (varyant + flash sale dahil).
     *
     * @param  array<int, int|string>  $variantItemIds
     */
    public function resolveUnitPrice(Product $product, array $variantItemIds = []): float
    {
        $base = $product->offer_price
            ? (float) $product->offer_price
            : (float) $product->price;

        if ($variantItemIds !== []) {
            $items = ProductVariantItem::query()
                ->whereIn('id', $variantItemIds)
                ->get(['id', 'price', 'product_variant_name']);

            // Renk: mutlak satış fiyatı (0 = ürün fiyatı). Diğerleri: ek ücret.
            $colorAbsolute = null;
            $extras = 0.0;
            foreach ($items as $item) {
                $variantName = (string) ($item->product_variant_name ?? '');
                $amount = (float) $item->price;
                if (preg_match('/renk|color/iu', $variantName)) {
                    if ($amount > 0) {
                        $colorAbsolute = $amount;
                    }
                    continue;
                }
                if ($amount > 0) {
                    $extras += $amount;
                }
            }

            if ($colorAbsolute !== null) {
                $base = $colorAbsolute + $extras;
            } else {
                $base += $extras;
            }
        }

        return round($this->applyFlashSaleDiscount($product->id, $base), 2);
    }

    /**
     * @param  array<string, mixed>|object  $cartProduct
     */
    public function resolveUnitPriceFromCartLine(Product $product, $cartProduct): float
    {
        return $this->resolveUnitPrice($product, $this->extractVariantItemIds($cartProduct));
    }

    /**
     * Sipariş satırında saklanacak varyant ek ücreti (renk mutlak değil, 0).
     * Satır tutarı unit_price'tadır; bu alan sadece bilgi / ekstra kaydıdır.
     */
    public function displayVariantPrice(ProductVariantItem $item): float
    {
        $name = (string) ($item->product_variant_name ?? '');
        $amount = (float) $item->price;
        if (preg_match('/renk|color/iu', $name)) {
            return 0.0;
        }

        return max(0, round($amount, 2));
    }

    /**
     * @param  iterable<int, object>  $orderProductVariants
     */
    public function formatVariantLabel($orderProductVariants): string
    {
        $parts = [];
        foreach ($orderProductVariants as $v) {
            $name = trim((string) ($v->variant_name ?? ''));
            $value = trim((string) ($v->variant_value ?? ''));
            if ($name === '' && $value === '') {
                continue;
            }
            $parts[] = $name !== '' ? "{$name}: {$value}" : $value;
        }

        return implode(', ', $parts);
    }

    /**
     * Renk varyant stokunu düş.
     *
     * @param  array<int, int|string>  $variantItemIds
     */
    public function decrementVariantStock(array $variantItemIds, int $qty): void
    {
        if ($variantItemIds === [] || $qty <= 0) {
            return;
        }
        if (! Schema::hasColumn('product_variant_items', 'qty')) {
            return;
        }

        $items = ProductVariantItem::query()->whereIn('id', $variantItemIds)->get();
        foreach ($items as $item) {
            $name = (string) ($item->product_variant_name ?? '');
            if (! preg_match('/renk|color/iu', $name)) {
                continue;
            }
            $item->qty = max(0, (int) $item->qty - $qty);
            $item->save();
        }
    }

    /**
     * @param  array<int, int|string>  $variantItemIds
     */
    public function restoreVariantStock(array $variantItemIds, int $qty): void
    {
        if ($variantItemIds === [] || $qty <= 0) {
            return;
        }
        if (! Schema::hasColumn('product_variant_items', 'qty')) {
            return;
        }

        $items = ProductVariantItem::query()->whereIn('id', $variantItemIds)->get();
        foreach ($items as $item) {
            $name = (string) ($item->product_variant_name ?? '');
            if (! preg_match('/renk|color/iu', $name)) {
                continue;
            }
            $item->qty = (int) $item->qty + $qty;
            $item->save();
        }
    }

    /**
     * İptal/red için sipariş satırındaki renk stoklarını geri yükle.
     */
    public function restoreVariantStockForOrderProduct(OrderProduct $orderProduct): void
    {
        $orderProduct->loadMissing('orderProductVariants');
        $ids = [];
        foreach ($orderProduct->orderProductVariants as $v) {
            $item = ProductVariantItem::query()
                ->where('product_id', $orderProduct->product_id)
                ->where('name', $v->variant_value)
                ->where('product_variant_name', $v->variant_name)
                ->first();
            if ($item) {
                $ids[] = $item->id;
            }
        }
        $this->restoreVariantStock($ids, (int) $orderProduct->qty);
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
                ->with(['category', 'subCategory', 'childCategory'])
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

            $installment = app(CategoryInstallmentService::class)
                ->resolveInstallmentForProduct($product);

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
                    'barcode' => $product->barcode ?? null,
                    'sku' => $product->sku ?? null,
                    'sale_unit_qty' => max(1, (int) ($product->sale_unit_qty ?? 1)),
                    'max_installment' => (int) $installment['max_installment'],
                    'category_name' => (string) ($installment['category_name'] ?? ''),
                    'installment_source' => (string) ($installment['source'] ?? ''),
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
