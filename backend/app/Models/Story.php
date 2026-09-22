<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Story extends Model
{
    protected $fillable = [
        'title',
        'image',
        'type',
        'feed',
        'link',
        'see_all_url',
        'serial',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'serial' => 'integer',
    ];

    public const FEEDS = [
        'popular' => 'Popüler ürünler',
        'bestseller' => 'En çok satılan',
        'discounted' => 'İndirimli ürünler',
        'featured' => 'Öne çıkanlar',
        'new_arrival' => 'Yeni ürünler',
    ];

    public const TYPES = [
        'product_feed' => 'Ürün vitrini (hover ile ürünler)',
        'link' => 'Bağlantı (sayfa / URL)',
    ];
}
