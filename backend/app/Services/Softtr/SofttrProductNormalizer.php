<?php

namespace App\Services\Softtr;

/**
 * Softtr product payloads → Seyfibaba Product fields (column names unchanged).
 * Softtr list schema is undocumented; synonyms + relative image resolution cover real shops.
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
    public function normalize(array $raw, string $shopOrigin = ''): ?array
    {
        // Some Softtr responses wrap the product under "product" / "data".
        foreach (['product', 'Product', 'item', 'content'] as $wrap) {
            if (! empty($raw[$wrap]) && is_array($raw[$wrap]) && ! $this->isList($raw[$wrap])) {
                $raw = array_merge($raw[$wrap], $raw);
                break;
            }
        }

        $id = $this->first($raw, [
            'productId', 'product_id', 'ProductId', 'id', 'Id', 'ID',
            'variantId', 'variant_id', 'productID',
        ]);
        $barcode = trim((string) $this->first($raw, [
            'barcode', 'Barcode', 'BARCODE', 'ean', 'gtin', 'barkod',
        ]));
        $sku = trim((string) $this->first($raw, [
            'code', 'sku', 'SKU', 'product_code', 'productCode', 'stock_code', 'itemCode', 'stokKodu',
        ]));

        if (($id === null || $id === '') && $barcode !== '') {
            $id = $barcode;
        }
        if (($id === null || $id === '') && $sku !== '') {
            $id = $sku;
        }

        $name = trim((string) $this->first($raw, [
            'title_tr', 'title', 'Title', 'name', 'Name', 'product_name', 'productName',
            'itemTitle', 'urunAdi', 'urun_adi',
        ]));

        if ($id === null || $id === '' || $name === '') {
            return null;
        }

        $categoryPath = trim((string) $this->first($raw, [
            'products_cat_associate', 'category', 'category_name', 'categoryName', 'kategori', 'categoryPath',
        ]));
        [$categoryName, $subName, $childName] = $this->splitCategoryPath($categoryPath);
        if ($categoryName === '') {
            $categoryName = trim((string) $this->first($raw, ['main_category', 'ana_kategori']));
        }

        $price = $this->toFloat($this->first($raw, [
            'salePrice', 'unitPrice', 'mainProductPrice', 'MainProductPrice', 'unit_price', 'unitPrice', 'price', 'Price',
            'sale_price', 'list_price', 'satisFiyati', 'fiyat',
        ]));
        $offer = $this->toFloat($this->first($raw, [
            'indirimli_fiyat', 'offer_price', 'offerPrice', 'discount_price', 'variantPrice',
            'VariantPrice', 'indirimliFiyat',
        ]));
        // Softtr: salePrice is often the sell price; unitPrice list — if both, prefer lower as offer when discount.
        if ($price > 0) {
            $unit = $this->toFloat($this->first($raw, ['unitPrice', 'UnitPrice']));
            $sale = $this->toFloat($this->first($raw, ['salePrice', 'SalePrice']));
            if ($unit > 0 && $sale > 0 && $sale < $unit) {
                $price = $unit;
                $offer = $sale;
            } elseif ($sale > 0) {
                $price = $sale;
            }
        }
        // variantPrice is often an add-on; only treat as offer if main price exists and variant is lower positive standalone.
        if ($offer > 0 && $price <= 0) {
            $price = $offer;
            $offer = 0;
        } elseif ($offer >= $price && $price > 0) {
            $offer = 0;
        }

        $qty = $this->extractStockQty($raw);

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
            'brand' => trim((string) $this->first($raw, [
                'brandName', 'brands', 'brand', 'Brand', 'brand_name', 'marka', 'itemBrand',
            ])),
            'short_description' => trim((string) $this->first($raw, [
                'descriptionShort', 'description_tr', 'short_description', 'summary', 'description', 'aciklama',
            ])),
            'long_description' => trim((string) $this->first($raw, [
                'description', 'detail_tr', 'long_description', 'detail', 'content', 'html_content', 'detay',
            ])),
            'image_url' => $this->extractImageUrl($raw, $shopOrigin),
            'weight' => $this->first($raw, ['desi', 'weight', 'volumetric_weight', 'Desi']) ?? 0,
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

        foreach (['data', 'content', 'products', 'items', 'result', 'results', 'productList'] as $key) {
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
            if (is_array($chunk) && isset($chunk['products']) && is_array($chunk['products']) && $this->isList($chunk['products'])) {
                return array_values(array_filter($chunk['products'], 'is_array'));
            }
        }

        return [];
    }

    /**
     * Softtr stockAmount (+ variants). Docs use stockAmount on price/stock updates.
     *
     * @param  array<string, mixed>  $raw
     */
    private function extractStockQty(array $raw): int
    {
        $fromVariants = 0;
        foreach (['variants', 'variantList', 'productVariants', 'Varyants', 'varyants'] as $key) {
            if (empty($raw[$key]) || ! is_array($raw[$key])) {
                continue;
            }
            foreach ($raw[$key] as $variant) {
                if (! is_array($variant)) {
                    continue;
                }
                $fromVariants += (int) $this->toFloat($this->first($variant, [
                    'stockAmount', 'StockAmount', 'stock_amount', 'stok', 'stock', 'quantity', 'qty',
                    'stokMiktari', 'stokAdedi', 'availableStock', 'totalStock',
                ]));
            }
        }

        $direct = $this->first($raw, [
            'unitStock', 'totalStock', 'stockAmount', 'StockAmount', 'stock_amount', 'stok', 'Stok', 'stock', 'Stock',
            'quantity', 'qty', 'stokMiktari', 'stokAdedi', 'StokMiktari', 'availableStock',
            'toplamStok', 'erp_stock',
        ]);

        // Nested stock object: { amount: 10 } / { stockAmount: 10 }
        if (($direct === null || $direct === '') && isset($raw['stock']) && is_array($raw['stock'])) {
            $direct = $this->first($raw['stock'], ['stockAmount', 'amount', 'qty', 'quantity', 'value']);
        }

        $fromProduct = (int) $this->toFloat($direct);

        if ($fromVariants > 0) {
            return max(0, $fromVariants);
        }

        return max(0, $fromProduct);
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

        // Case-insensitive fallback
        $lowerMap = [];
        foreach ($raw as $k => $v) {
            if (is_string($k) || is_int($k)) {
                $lowerMap[strtolower((string) $k)] = $v;
            }
        }
        foreach ($keys as $key) {
            $lk = strtolower($key);
            if (array_key_exists($lk, $lowerMap) && $lowerMap[$lk] !== null && $lowerMap[$lk] !== '') {
                return $lowerMap[$lk];
            }
        }

        return null;
    }

    private function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_bool($value)) {
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
     * Softtr often returns relative paths or bare filenames (Excel picture column).
     *
     * @param  array<string, mixed>  $raw
     */
    private function extractImageUrl(array $raw, string $shopOrigin = ''): string
    {
        $origin = rtrim($shopOrigin, '/');

        $candidates = [];
        foreach ([
            'picture', 'Picture', 'image_url', 'imageUrl', 'image', 'Image', 'photo', 'Photo',
            'thumbnail', 'thumb', 'gorsel', 'resim', 'resimUrl', 'productImage', 'product_image',
            'image1', 'Image1', 'mainImage', 'cover',
        ] as $key) {
            $val = $this->first($raw, [$key]);
            if ($val !== null && $val !== '') {
                $candidates[] = $val;
            }
        }

        foreach (['images', 'pictures', 'gallery', 'Images', 'Pictures'] as $key) {
            if (empty($raw[$key]) || ! is_array($raw[$key])) {
                continue;
            }
            $first = $raw[$key][0] ?? null;
            if ($first !== null) {
                $candidates[] = $first;
            }
        }

        foreach ($candidates as $candidate) {
            $resolved = $this->resolveImageCandidate($candidate, $origin);
            if ($resolved !== '') {
                return $resolved;
            }
        }

        return '';
    }

    private function resolveImageCandidate(mixed $candidate, string $origin): string
    {
        if (is_array($candidate)) {
            foreach (['url', 'src', 'path', 'image', 'picture', 'href'] as $k) {
                if (! empty($candidate[$k]) && is_string($candidate[$k])) {
                    $inner = $this->resolveImageCandidate($candidate[$k], $origin);
                    if ($inner !== '') {
                        return $inner;
                    }
                }
            }

            return '';
        }

        $path = trim((string) $candidate);
        if ($path === '' || strcasecmp($path, 'null') === 0) {
            return '';
        }

        if (str_starts_with($path, '//')) {
            return 'https:' . $path;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        if ($origin === '') {
            return $path;
        }

        // Absolute path on shop: /Data/xxx.jpg
        if (str_starts_with($path, '/')) {
            return $origin . $path;
        }

        // Relative path with folders
        if (str_contains($path, '/')) {
            return $origin . '/' . ltrim($path, '/');
        }

        // Bare filename — Softtr shops commonly keep files under /Data/
        if ($origin !== '') {
            return $origin . '/Data/' . $path;
        }

        return $path;
    }

    private function isList(array $arr): bool
    {
        return $arr === [] || array_keys($arr) === range(0, count($arr) - 1);
    }
}
