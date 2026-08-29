<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalDocument extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'content',
        'version',
        'meta_title',
        'meta_description',
        'is_published',
        'requires_consent',
        'is_active',
        'sort_order',
        'category',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'requires_consent' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function consents(): HasMany
    {
        return $this->hasMany(LegalDocumentConsent::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_active', true)->where('is_published', true);
    }
}
