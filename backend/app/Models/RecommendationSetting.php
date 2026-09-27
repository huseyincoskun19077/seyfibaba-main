<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecommendationSetting extends Model
{
    protected $fillable = [
        'weight_segment',
        'weight_browse_history',
        'weight_business_type',
        'weight_popularity',
        'history_days',
        'min_views_for_signal',
        'vendor_diversity',
        'exclude_viewed_product',
    ];

    protected $casts = [
        'exclude_viewed_product' => 'boolean',
        'weight_segment' => 'integer',
        'weight_browse_history' => 'integer',
        'weight_business_type' => 'integer',
        'weight_popularity' => 'integer',
        'history_days' => 'integer',
        'min_views_for_signal' => 'integer',
        'vendor_diversity' => 'integer',
    ];

    public static function current(): self
    {
        $row = static::query()->first();
        if ($row) {
            return $row;
        }

        return static::query()->create([
            'weight_segment' => 40,
            'weight_browse_history' => 30,
            'weight_business_type' => 20,
            'weight_popularity' => 10,
            'history_days' => 30,
            'min_views_for_signal' => 1,
            'vendor_diversity' => 3,
            'exclude_viewed_product' => true,
        ]);
    }
}
