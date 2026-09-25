<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeBlock extends Model
{
    protected $fillable = [
        'title',
        'type',
        'feed',
        'image',
        'link',
        'mobile_link',
        'see_all_url',
        'product_ids',
        'category_ids',
        'limit_count',
        'serial',
        'status',
        'show_on_web',
        'show_on_mobile',
    ];

    protected $casts = [
        'status' => 'boolean',
        'show_on_web' => 'boolean',
        'show_on_mobile' => 'boolean',
        'limit_count' => 'integer',
        'serial' => 'integer',
    ];

    public const TYPES = [
        'campaign' => 'Kampanya görseli (tek afiş)',
        'product_feed' => 'Ürün listesi / şerit',
        'all_products' => 'Tüm ürünler (grid)',
        'category_grid' => 'Kategoriler',
        'flash_sale' => 'Flaş / countdown',
        'brands' => 'Markalar',
    ];

    public const FEEDS = [
        'popular' => 'Popüler ürünler',
        'discounted' => 'İndirimli ürünler',
        'featured' => 'Öne çıkan ürünler',
        'new' => 'Yeni gelen ürünler',
        'best' => 'En iyi / çok satan',
        'weekend' => 'Hafta sonuna özel',
        'custom' => 'Seçili ürün ID’leri',
    ];

    public function decodeIds(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_unique(array_map('intval', $decoded)));
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($v) => (int) trim((string) $v),
            explode(',', $raw)
        ))));
    }

    public function encodeIds($value): string
    {
        if (is_string($value)) {
            $ids = $this->decodeIds($value);
        } elseif (is_array($value)) {
            $ids = array_values(array_unique(array_map('intval', $value)));
        } else {
            $ids = [];
        }

        return json_encode($ids);
    }
}
