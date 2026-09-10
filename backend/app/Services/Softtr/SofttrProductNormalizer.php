<?php

namespace App\Services\Softtr;

/**
 * Softtr product payloads → stable shape for Seyfibaba Product fields (names unchanged).
 * Docs list GET /products/list without a full schema; field synonyms cover Softtr Excel + typical API keys.
 */
class SofttrProductNormalizer
{
    /**
     * @param  array<string, mixed>  $raw
     * @return array{
     *   softtr_id: string,
     *   name: string,
     *   sku: string,
     *   barcode: string,
     *   price: float,
     *   offer_price: float,
     *   qty: int,
     *   category_name: string,
     *   sub_category_name: string,
     *   child_category_name: string,
     *   category_key: string,
     *   brand: string,
     *   short_description: string,
     *   long_description: string,
     *   image_url: string,
     *   weight: float|int|string
     * }|null
     */
    public function normalize(array $raw): ?array
    {
        $id = $this->first($raw, [
            'productId', 'product_id', 'id', 'Id', 'variantId', 'variant_id',
        ]);
        $barcode = trim((string) $this->first($raw, ['barcode', 'Barcode', 'ean', 'gtin']));
        $sku = trim((string) $this->first($raw, [
            'code', 'sku', 'product_code', 'productCode', 'stock_code', 'itemCode',
        ]));

        if (($id === null || $id === '') && $barcode !== '') {
            $id = $barcode;
        }
        if (($id === null || $id === '') && $sku !== '') {
            $id = $sku;
        }

        $name = trim((string) $this->first($raw, [
            'title_tr', 'title', 'name', 'product_name', 'productName', 'itemTitle', 'Name',
        ]));

        if ($id === null || $id === '' || $name === '') {
            return null;
        }

        $categoryPath = trim((string) $this->first($raw, [
            'products_cat_associate', 'category', 'category_name', 'categoryName', 'kategori',
        ]));
        [$categoryName, $subName, $childName] = $this->splitCategoryPath($categoryPath);
        if ($categoryName === '') {
            $categoryName = trim((string) $this->first($raw, ['main_category', 'ana_kategori']));
        }

        $price = $this->toFloat($this->first($raw, [
            'mainProductPrice', 'unit_price', 'unitPrice', 'price', 'sale_price', 'list_price',
        ]));
        $offer = $this->toFloat($this->first($raw, [
            'indirimli_fiyat', 'offer_price', 'offerPrice', 'discount_price', 'variantPrice',
        ]));
        if ($offer > 0 && $price <= 0) {
            $price = $offer;
            $offer = 0;
        }

        $qty = (int) $this->toFloat($this->first($raw, [
            'stockAmount', 'stock_amount', 'stok', 'stock', 'quantity', 'qty',
        ]));

        $categoryKey = strtolower(trim(implode('>', array_filter([$categoryName, $subName, $childName]))));
        if ($categoryKey === '') {
            $categoryKey = 'unknown';
        }

        return [
            'softtr_id' => (string) $id,
            'name' => $name,
            'sku' => $sku !== '' ? $sku : ($barcode !== '' ? $barcode : 'SOFTTR-' . $id),
            'barcode' => $barcode,
            'price' => $price,
            'offer_price' => $offer,
            'qty' => max(0, $qty),
            'category_name' => $categoryName,
            'sub_category_name' => $subName,
            'child_category_name' => $childName,
            'category_key' => 'path:' . $categoryKey,
            'brand' => trim((string) $this->first($raw, ['brands', 'brand', 'brand_name', 'marka', 'itemBrand'])),
            'short_description' => trim((string) $this->first($raw, [
                'description_tr', 'short_description', 'summary', 'description',
            ])),
            'long_description' => trim((string) $this->first($raw, [
                'detail_tr', 'long_description', 'detail', 'content', 'html_content',
            ])),
            'image_url' => $this->extractImageUrl($raw),
            'weight' => $this->first($raw, ['desi', 'weight', 'volumetric_weight']) ?? 0,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function extractListItems(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if ($this->isList($payload)) {
            return array_values(array_filter($payload, 'is_array'));
        }

        foreach (['data', 'content', 'products', 'items', 'result', 'results'] as $key) {
            if (! isset($payload[$key])) {
                continue;
            }
            $chunk = $payload[$key];
            if (is_array($chunk) && $this->isList($chunk)) {
                return array_values(array_filter($chunk, 'is_array'));
            }
            if (is_array($chunk) && isset($chunk['content']) && is_array($chunk['content']) && $this->isList($chunk['content'])) {
                return array_values(array_filter($chunk['content'], 'is_array'));
            }
            if (is_array($chunk) && isset($chunk['data']) && is_array($chunk['data']) && $this->isList($chunk['data'])) {
                return array_values(array_filter($chunk['data'], 'is_array'));
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $keys
     */
    private function first(array $raw, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $raw) && $raw[$key] !== null && $raw[$key] !== '') {
                return $raw[$key];
            }
        }

        return null;
    }

    private function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $raw = trim((string) $value);
        if (preg_match('/^\d{1,3}(\.\d{3})+,\d+$/', $raw)) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } else {
            $raw = str_replace([' ', ','], ['', '.'], $raw);
        }
        $normalized = preg_replace('/[^0-9.\-]/', '', $raw) ?? '0';

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function splitCategoryPath(string $path): array
    {
        if ($path === '') {
            return ['', '', ''];
        }
        $parts = preg_split('/\s*>\s*/', $path) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts)));

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
            $parts[2] ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function extractImageUrl(array $raw): string
    {
        $direct = $this->first($raw, [
            'picture', 'image_url', 'imageUrl', 'image', 'photo', 'thumbnail', 'gorsel',
        ]);
        if (is_string($direct) && preg_match('#^https?://#i', $direct)) {
            return trim($direct);
        }
        if (is_array($direct)) {
            foreach (['url', 'src', 'path', 'image'] as $k) {
                if (! empty($direct[$k]) && is_string($direct[$k]) && preg_match('#^https?://#i', $direct[$k])) {
                    return trim($direct[$k]);
                }
            }
        }

        foreach (['images', 'pictures', 'gallery'] as $key) {
            if (empty($raw[$key]) || ! is_array($raw[$key])) {
                continue;
            }
            $first = $raw[$key][0] ?? null;
            if (is_string($first) && preg_match('#^https?://#i', $first)) {
                return trim($first);
            }
            if (is_array($first)) {
                foreach (['url', 'src', 'path'] as $k) {
                    if (! empty($first[$k]) && is_string($first[$k]) && preg_match('#^https?://#i', $first[$k])) {
                        return trim($first[$k]);
                    }
                }
            }
        }

        return is_string($direct) ? trim($direct) : '';
    }

    private function isList(array $arr): bool
    {
        return $arr === [] || array_keys($arr) === range(0, count($arr) - 1);
    }
}
