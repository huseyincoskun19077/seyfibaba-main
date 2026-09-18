<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantItem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SimpleProductColorService
{
    public function payloadFromRequest(Request $request): array
    {
        $colors = $request->input('colors', []);
        $files = $request->file('colors', []);
        if (! is_array($colors)) {
            return [];
        }

        foreach ($colors as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            if (isset($files[$index]['image'])) {
                $colors[$index]['image'] = $files[$index]['image'];
            }
        }

        return $colors;
    }

    public function existingRows(Product $product): array
    {
        $variant = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('name', 'Renk')
            ->first();
        if (! $variant) {
            return [];
        }

        return ProductVariantItem::query()
            ->where('product_variant_id', $variant->id)
            ->orderBy('id')
            ->get()
            ->map(function (ProductVariantItem $item) use ($product) {
                return [
                    'name' => $item->name,
                    'price' => round((float) $product->price + (float) $item->price, 2),
                    'qty' => (int) ($item->qty ?? 0),
                    'image' => $item->image,
                ];
            })
            ->all();
    }

    /**
     * Sync Renk variant items. Never throws for image/storage issues —
     * product create must still succeed if colors partially fail.
     *
     * @return array{ok:bool,saved:int,message:?string}
     */
    public function sync(Product $product, array $colors = []): array
    {
        try {
            $valid = [];
            foreach ($colors as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $valid[] = [
                    'name' => mb_substr($name, 0, 80),
                    'price' => isset($row['price']) && $row['price'] !== '' && $row['price'] !== null
                        ? (float) $row['price']
                        : (float) $product->price,
                    'qty' => max(0, (int) ($row['qty'] ?? 0)),
                    'file' => ($row['image'] ?? null) instanceof UploadedFile ? $row['image'] : null,
                    'keep_image' => is_string($row['keep_image'] ?? null)
                        ? (string) $row['keep_image']
                        : (is_string($row['image'] ?? null) ? (string) $row['image'] : null),
                ];
            }

            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->where('name', 'Renk')
                ->first();

            if ($valid === []) {
                // Only clear when caller explicitly sent an empty color set
                // after having had colors before — still allow wipe on edit.
                if ($variant) {
                    ProductVariantItem::query()->where('product_variant_id', $variant->id)->delete();
                    $variant->delete();
                }

                return ['ok' => true, 'saved' => 0, 'message' => null];
            }

            if (! $variant) {
                $variant = new ProductVariant();
                $variant->product_id = $product->id;
                $variant->name = 'Renk';
                $variant->status = 1;
                $variant->save();
            } else {
                $variant->status = 1;
                $variant->save();
            }

            $hasImageCol = Schema::hasColumn('product_variant_items', 'image');
            $hasQtyCol = Schema::hasColumn('product_variant_items', 'qty');
            $keepIds = [];
            $imageErrors = 0;

            foreach ($valid as $row) {
                $item = ProductVariantItem::query()->firstOrNew([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'name' => $row['name'],
                ]);
                $item->product_variant_name = 'Renk';
                $item->price = round($row['price'] - (float) $product->price, 2);
                if ($hasQtyCol) {
                    $item->qty = $row['qty'];
                }
                $item->status = 1;

                if ($hasImageCol && $row['file']) {
                    try {
                        $item->image = app(ProductImageStorage::class)->store(
                            $row['file'],
                            'renk-' . $row['name']
                        );
                    } catch (Throwable $e) {
                        $imageErrors++;
                        Log::warning('Color variant image upload failed', [
                            'product_id' => $product->id,
                            'color' => $row['name'],
                            'error' => $e->getMessage(),
                        ]);
                    }
                } elseif ($hasImageCol && $row['keep_image'] && empty($item->image)) {
                    $item->image = $row['keep_image'];
                }

                $item->save();
                $keepIds[] = $item->id;
            }

            ProductVariantItem::query()
                ->where('product_variant_id', $variant->id)
                ->whereNotIn('id', $keepIds)
                ->delete();

            return [
                'ok' => true,
                'saved' => count($keepIds),
                'message' => $imageErrors > 0
                    ? 'Bazı renk fotoğrafları yüklenemedi; renk isimleri kaydedildi.'
                    : null,
            ];
        } catch (Throwable $e) {
            Log::error('SimpleProductColorService sync failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'saved' => 0,
                'message' => 'Renkler kaydedilemedi: ' . $e->getMessage(),
            ];
        }
    }
}
