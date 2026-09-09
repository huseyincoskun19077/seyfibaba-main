<?php

namespace App\Services\Sentos;

/**
 * Normalize heterogeneous Sentos product payloads into a stable shape.
 */
class SentosProductNormalizer
{
    /**
     * @param  array<string, mixed>  $raw
     * @return array{
     *   sentos_id: string,
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
        $id = $this->first($raw, ['id', 'product_id', 'productId', 'Id']);
        $name = trim((string) $this->first($raw, ['name', 'title', 'product_name', 'productName', 'Name']));

        if ($id === null || $id === '' || $name === '') {
            return null;
        }

        $categoryName = $this->extractCategoryName($raw);
        $subName = $this->extractNestedName($raw, ['sub_category', 'subCategory', 'subcategory', 'category2']);
        $childName = $this->extractNestedName($raw, ['child_category', 'childCategory', 'category3']);
        $categoryKey = $this->buildCategoryKey($raw, $categoryName, $subName, $childName);

        $price = $this->toFloat($this->first($raw, [
            'price', 'sale_price', 'salePrice', 'unit_price', 'unitPrice', 'list_price', 'mainProductPrice',
        ]));
        $offer = $this->toFloat($this->first($raw, [
            'offer_price', 'offerPrice', 'discount_price', 'discountPrice', 'special_price',
        ]));
        $qty = (int) $this->toFloat($this->first($raw, [
            'stock', 'stock_amount', 'stockAmount', 'quantity', 'qty', 'inventory',
        ]));

        $sku = trim((string) $this->first($raw, ['sku', 'code', 'product_code', 'productCode', 'stock_code']));
        $barcode = trim((string) $this->first($raw, ['barcode', 'barcode_number', 'gtin', 'ean']));

        return [
            'sentos_id' => (string) $id,
            'name' => $name,
            'sku' => $sku !== '' ? $sku : ($barcode !== '' ? $barcode : 'SENTOS-' . $id),
            'barcode' => $barcode,
            'price' => $price,
            'offer_price' => $offer,
            'qty' => max(0, $qty),
            'category_name' => $categoryName,
            'sub_category_name' => $subName,
            'child_category_name' => $childName,
            'category_key' => $categoryKey,
            'brand' => trim((string) $this->first($raw, ['brand', 'brand_name', 'brandName', 'marka'])),
            'short_description' => trim((string) $this->first($raw, [
                'short_description', 'shortDescription', 'summary', 'description',
            ])),
            'long_description' => trim((string) $this->first($raw, [
                'long_description', 'longDescription', 'detail', 'details', 'content', 'html_content',
            ])),
            'image_url' => $this->extractImageUrl($raw),
            'weight' => $this->first($raw, ['weight', 'desi', 'volumetric_weight']) ?? 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
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

        foreach (['data', 'products', 'content', 'items', 'result', 'results'] as $key) {
            if (! isset($payload[$key])) {
                continue;
            }
            $chunk = $payload[$key];
            if (is_array($chunk) && $this->isList($chunk)) {
                return array_values(array_filter($chunk, 'is_array'));
            }
            if (is_array($chunk) && isset($chunk['data']) && is_array($chunk['data']) && $this->isList($chunk['data'])) {
                return array_values(array_filter($chunk['data'], 'is_array'));
            }
            if (is_array($chunk) && isset($chunk['content']) && is_array($chunk['content']) && $this->isList($chunk['content'])) {
                return array_values(array_filter($chunk['content'], 'is_array'));
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
        $normalized = str_replace([' ', ','], ['', '.'], (string) $value);
        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized) ?? '0';

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function extractCategoryName(array $raw): string
    {
        $direct = $this->first($raw, [
            'category_name', 'categoryName', 'category_title', 'main_category',
        ]);
        if (is_string($direct) && trim($direct) !== '') {
            return trim($direct);
        }

        $category = $raw['category'] ?? $raw['categories'] ?? null;
        if (is_string($category)) {
            return trim($category);
        }
        if (is_array($category)) {
            if ($this->isList($category)) {
                $first = $category[0] ?? null;
                if (is_string($first)) {
                    return trim($first);
                }
                if (is_array($first)) {
                    return trim((string) ($first['name'] ?? $first['title'] ?? $first['category_name'] ?? ''));
                }
            }

            return trim((string) ($category['name'] ?? $category['title'] ?? $category['category_name'] ?? ''));
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $keys
     */
    private function extractNestedName(array $raw, array $keys): string
    {
        $value = $this->first($raw, $keys);
        if (is_string($value)) {
            return trim($value);
        }
        if (is_array($value)) {
            return trim((string) ($value['name'] ?? $value['title'] ?? ''));
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function buildCategoryKey(array $raw, string $categoryName, string $subName, string $childName): string
    {
        $id = $this->first($raw, ['category_id', 'categoryId', 'main_category_id']);
        if (is_array($raw['category'] ?? null) && isset($raw['category']['id'])) {
            $id = $raw['category']['id'];
        }

        $path = strtolower(trim(implode('>', array_filter([$categoryName, $subName, $childName]))));
        if ($id !== null && $id !== '') {
            return 'id:' . $id . '|' . $path;
        }

        return $path !== '' ? 'path:' . $path : 'unknown';
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function extractImageUrl(array $raw): string
    {
        $direct = $this->first($raw, [
            'image_url', 'imageUrl', 'image', 'picture', 'thumbnail', 'thumb', 'cover',
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

        foreach (['images', 'pictures', 'gallery', 'product_images'] as $key) {
            if (empty($raw[$key]) || ! is_array($raw[$key])) {
                continue;
            }
            $first = $raw[$key][0] ?? null;
            if (is_string($first) && preg_match('#^https?://#i', $first)) {
                return trim($first);
            }
            if (is_array($first)) {
                foreach (['url', 'src', 'path', 'image'] as $k) {
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
