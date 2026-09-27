<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerSegment extends Model
{
    protected $fillable = [
        'code',
        'name',
        'slug',
        'short_name',
        'description',
        'icon',
        'image',
        'serial',
        'is_active',
        'show_on_guest_home',
        'is_primary_home',
        'vendor_diversity',
        'home_product_limit',
        'business_type_key',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_on_guest_home' => 'boolean',
        'is_primary_home' => 'boolean',
        'serial' => 'integer',
        'vendor_diversity' => 'integer',
        'home_product_limit' => 'integer',
    ];

    /** Varsayılan müşteri alanları (slug sabit; kategori silinmez) */
    public const DEFAULTS = [
        [
            'code' => 'women_salon',
            'name' => 'Kadın Kuaförü',
            'slug' => 'kadin-kuaforu',
            'short_name' => 'Kadın',
            'is_primary_home' => true,
            'serial' => 10,
            'business_type_key' => 'female_hairdresser',
        ],
        [
            'code' => 'men_barber',
            'name' => 'Erkek Kuaförü ve Berber',
            'slug' => 'erkek-kuaforu-berber',
            'short_name' => 'Erkek / Berber',
            'is_primary_home' => true,
            'serial' => 20,
            'business_type_key' => 'male_hairdresser',
        ],
        [
            'code' => 'beauty_salon',
            'name' => 'Güzellik Salonu',
            'slug' => 'guzellik-salonu',
            'short_name' => 'Güzellik',
            'is_primary_home' => true,
            'serial' => 30,
            'business_type_key' => 'beauty_salon',
        ],
        [
            'code' => 'nail',
            'name' => 'Tırnak ve Manikür',
            'slug' => 'tirnak-manikur',
            'short_name' => 'Tırnak',
            'is_primary_home' => true,
            'serial' => 40,
            'business_type_key' => 'nail_art',
        ],
        [
            'code' => 'shared',
            'name' => 'Ortak Salon İhtiyaçları',
            'slug' => 'ortak-salon-ihtiyaclari',
            'short_name' => 'Ortak',
            'is_primary_home' => false,
            'serial' => 50,
            'business_type_key' => null,
        ],
        [
            'code' => 'spare_parts',
            'name' => 'Yedek Parçalar',
            'slug' => 'yedek-parcalar',
            'short_name' => 'Yedek',
            'is_primary_home' => false,
            'serial' => 60,
            'business_type_key' => null,
        ],
    ];

    public function taxonomies(): HasMany
    {
        return $this->hasMany(CustomerSegmentTaxonomy::class)->orderBy('serial')->orderBy('id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(CustomerSegmentProduct::class)->orderBy('serial')->orderBy('id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGuestHome($query)
    {
        return $query->active()->where('show_on_guest_home', true)->orderBy('serial');
    }
}
