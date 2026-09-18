<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantItem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
            ->map(function (ProductVariantItem $item) {
                return [
                    'name' => $item->name,
                    // Absolute sale price for this color (not a delta).
                    'price' => round((float) $item->price, 2),
                    'qty' => (int) ($item->qty ?? 0),
                    'image' => $item->image ?? null,
                ];
            })
            ->all();
    }

    /**
     * Sync Renk variant items.
     *
     * @return array{ok:bool,saved:int,message:?string}
     */
    public function sync(Product $product, array $colors = [], bool $clearWhenEmpty = true): array
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
                if ($clearWhenEmpty && $variant) {
                    ProductVariantItem::query()->where('product_variant_id', $variant->id)->delete();
                    $variant->delete();
                }

                return ['ok' => true, 'saved' => 0, 'message' => null];
            }

            $hasImageCol = Schema::hasColumn('product_variant_items', 'image');
            $hasQtyCol = Schema::hasColumn('product_variant_items', 'qty');
            $hasVariantNameCol = Schema::hasColumn('product_variant_items', 'product_variant_name');
            $hasDefaultCol = Schema::hasColumn('product_variant_items', 'is_default');

            $keepIds = [];
            $imageErrors = 0;
            $itemErrors = 0;

            DB::transaction(function () use (
                $product,
                &$variant,
                $valid,
                $hasImageCol,
                $hasQtyCol,
                $hasVariantNameCol,
                $hasDefaultCol,
                &$keepIds,
                &$imageErrors,
                &$itemErrors
            ) {
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

                foreach ($valid as $index => $row) {
                    try {
                        // Do NOT use firstOrNew()/fill() — ProductVariantItem is fully guarded.
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
                            $item->product_variant_name = 'Renk';
                        }
                        $item->price = round($row['price'], 2);
                        if ($hasQtyCol) {
                            $item->qty = $row['qty'];
                        }
                        $item->status = 1;
                        if ($hasDefaultCol && ! $item->exists && $index === 0) {
                            $item->is_default = 1;
                        }

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
                    } catch (Throwable $e) {
                        $itemErrors++;
                        Log::error('Color variant item save failed', [
                            'product_id' => $product->id,
                            'color' => $row['name'],
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                if ($keepIds !== []) {
                    ProductVariantItem::query()
                        ->where('product_variant_id', $variant->id)
                        ->whereNotIn('id', $keepIds)
                        ->delete();
                } elseif ($variant) {
                    // Nothing saved — remove orphan Renk shell
                    ProductVariantItem::query()->where('product_variant_id', $variant->id)->delete();
                    $variant->delete();
                    $variant = null;
                }
            });

            if ($keepIds === []) {
                return [
                    'ok' => false,
                    'saved' => 0,
                    'message' => 'Renkler kaydedilemedi. Lütfen tekrar deneyin.',
                ];
            }

            $message = null;
            if ($itemErrors > 0) {
                $message = 'Bazı renkler kaydedilemedi.';
            } elseif ($imageErrors > 0) {
                $message = 'Bazı renk fotoğrafları yüklenemedi; renk isimleri kaydedildi.';
            }

            return [
                'ok' => true,
                'saved' => count($keepIds),
                'message' => $message,
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
