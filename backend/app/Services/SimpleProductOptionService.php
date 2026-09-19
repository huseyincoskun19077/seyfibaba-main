<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Renk dışı ek seçenekler (Boyut vb.) — fiyat ek ücrettir.
 */
class SimpleProductOptionService
{
    public function payloadFromRequest(Request $request, string $key = 'sizes'): array
    {
        $rows = $request->input($key, []);
        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, function ($row) {
            return is_array($row) && trim((string) ($row['name'] ?? '')) !== '';
        }));
    }

    /**
     * @return array{ok:bool,saved:int,message:?string}
     */
    public function sync(Product $product, array $rows = [], string $groupName = 'Boyut', bool $clearWhenEmpty = true): array
    {
        try {
            $valid = [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $rawPrice = $row['price'] ?? null;
                $price = ($rawPrice === '' || $rawPrice === null) ? 0.0 : max(0, (float) $rawPrice);
                $valid[] = [
                    'name' => mb_substr($name, 0, 80),
                    'price' => round($price, 2),
                ];
            }

            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->where('name', $groupName)
                ->first();

            if ($valid === []) {
                if ($clearWhenEmpty && $variant) {
                    ProductVariantItem::query()->where('product_variant_id', $variant->id)->delete();
                    $variant->delete();
                }

                return ['ok' => true, 'saved' => 0, 'message' => null];
            }

            $hasVariantNameCol = Schema::hasColumn('product_variant_items', 'product_variant_name');
            $keepIds = [];

            DB::transaction(function () use ($product, &$variant, $valid, $groupName, $hasVariantNameCol, &$keepIds) {
                if (! $variant) {
                    $variant = new ProductVariant();
                    $variant->product_id = $product->id;
                    $variant->name = $groupName;
                    $variant->status = 1;
                    $variant->save();
                } else {
                    $variant->status = 1;
                    $variant->save();
                }

                foreach ($valid as $row) {
                    $item = ProductVariantItem::query()
                        ->where('product_id', $product->id)
                        ->where('product_variant_id', $variant->id)
                        ->where('name', $row['name'])
                        ->first();

                    if (! $item) {
                        $item = new ProductVariantItem();
                        $item->product_id = $product->id;
                        $item->product_variant_id = $variant->id;
                        $item->name = $row['name'];
                    }

                    if ($hasVariantNameCol) {
                        $item->product_variant_name = $groupName;
                    }
                    $item->price = $row['price'];
                    $item->status = 1;
                    $item->save();
                    $keepIds[] = $item->id;
                }

                ProductVariantItem::query()
                    ->where('product_variant_id', $variant->id)
                    ->whereNotIn('id', $keepIds)
                    ->delete();
            });

            return ['ok' => true, 'saved' => count($keepIds), 'message' => null];
        } catch (Throwable $e) {
            Log::error('SimpleProductOptionService sync failed', [
                'product_id' => $product->id,
                'group' => $groupName,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'saved' => 0,
                'message' => $groupName.' seçenekleri kaydedilemedi.',
            ];
        }
    }

    public function existingRows(Product $product, string $groupName = 'Boyut'): array
    {
        $variant = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('name', $groupName)
            ->first();

        if (! $variant) {
            return [];
        }

        return ProductVariantItem::query()
            ->where('product_variant_id', $variant->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->name,
                'price' => (float) $item->price > 0 ? $item->price : '',
            ])
            ->all();
    }
}
