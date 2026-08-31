<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileSlider extends Model
{
    protected $fillable = [
        'image',
        'title',
        'subtitle',
        'link',
        'product_slug',
        'serial',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'serial' => 'integer',
    ];
}
