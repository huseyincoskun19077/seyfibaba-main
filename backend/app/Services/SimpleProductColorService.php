<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantItem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

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

    public function sync(Product $product, array $colors = []): void
    {
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
            ];
        }

        $variant = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('name', 'Renk')
            ->first();

        if ($valid === []) {
            if ($variant) {
                ProductVariantItem::query()->where('product_variant_id', $variant->id)->delete();
                $variant->delete();
            }
            return;
        }

        if (! $variant) {
            $variant = new ProductVariant();
            $variant->product_id = $product->id;
            $variant->name = 'Renk';
            $variant->status = 1;
            $variant->save();
        }

        $keepIds = [];
        foreach ($valid as $row) {
            $item = ProductVariantItem::query()->firstOrNew([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'name' => $row['name'],
            ]);
            $item->product_variant_name = 'Renk';
            $item->price = round($row['price'] - (float) $product->price, 2);
            $item->qty = $row['qty'];
            $item->status = 1;
            if ($row['file']) {
                $item->image = app(ProductImageStorage::class)->store($row['file'], 'renk-' . $row['name']);
            }
            $item->save();
            $keepIds[] = $item->id;
        }

        ProductVariantItem::query()
            ->where('product_variant_id', $variant->id)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
