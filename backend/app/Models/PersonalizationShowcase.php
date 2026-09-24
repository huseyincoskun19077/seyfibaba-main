<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalizationShowcase extends Model
{
    protected $fillable = [
        'business_type',
        'title',
        'category_ids',
        'product_ids',
        'vendor_ids',
        'opening_category_ids',
        'opening_product_ids',
        'opening_vendor_ids',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public const BUSINESS_TYPES = [
        'female_hairdresser' => 'Bayan kuaförü',
        'male_hairdresser' => 'Erkek kuaförü',
        'barber' => 'Berber',
        'beauty_salon' => 'Güzellik salonu',
        'nail_art' => 'Nail art / Protez tırnak',
        'other' => 'Diğer',
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
        // "1,2,3" fallback
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
