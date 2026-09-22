<?php

use App\Models\Setting;

/**
 * Vitrin ürün sayfası tam URL’si (Next.js: /urun/{slug}).
 * Admin host’u veya route('product-detail') yerine kullanın; böylece asla /api/product/... oluşmaz.
 */
function storefront_product_url(?string $slug): string
{
    $setting = Setting::query()->first();
    $base = rtrim($setting?->frontend_url ?? config('app.frontend_url') ?? config('app.url'), '/');

    if ($slug === null || $slug === '') {
        return $base;
    }

    return $base.'/urun/'.$slug;
}

/**
 * Ürün görseli — yerel dosya yolu veya harici CDN linki (Trendyol vb.).
 */
function product_image_url(?string $path): string
{
    return \App\Support\ProductImageUrl::resolve($path);
}

/**
 * Seyfibaba TR para formatı — frontend priceFormat.js ile aynı.
 * Örnek: sb_money(1518) => ₺1.518,00  |  sb_money(62.69, true) => -₺62,69
 */
function sb_money(mixed $value, bool $forceMinus = false): string
{
    return \App\Helpers\PriceFormat::money($value, $forceMinus);
}

/** İndirimli fiyat HTML bloğu (e-posta / admin). */
function sb_price_html(mixed $listPrice, mixed $salePrice = null): string
{
    return \App\Helpers\PriceFormat::htmlBlock($listPrice, $salePrice);
}
