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
        'mobile_link',
        'see_all_url',
        'serial',
        'status',
        'show_on_web',
        'show_on_mobile',
    ];

    protected $casts = [
        'status' => 'boolean',
        'show_on_web' => 'boolean',
        'show_on_mobile' => 'boolean',
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
