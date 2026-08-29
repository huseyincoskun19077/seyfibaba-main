<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecondHandAgreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'terms_title',
        'terms_content',
        'privacy_title',
        'privacy_content',
        'homepage_title',
        'homepage_subtitle',
        'homepage_cta_primary',
        'homepage_cta_secondary',
        'homepage_image',
        'homepage_show_categories',
        'homepage_show_featured',
        'updated_by',
    ];

    protected $casts = [
        'homepage_show_categories' => 'boolean',
        'homepage_show_featured' => 'boolean',
    ];
}

