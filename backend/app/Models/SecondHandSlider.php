<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecondHandSlider extends Model
{
    protected $fillable = [
        'image',
        'title',
        'subtitle',
        'link',
        'serial',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'serial' => 'integer',
    ];
}
