<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecondHandListingImage extends Model
{
    use HasFactory;

    protected $table = 'second_hand_listing_images';

    protected $fillable = [
        'listing_id',
        'file_path',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'url',
    ];

    public function listing()
    {
        return $this->belongsTo(SecondHandListing::class, 'listing_id');
    }

    public function getUrlAttribute(): ?string
    {
        if (! $this->id) {
            return null;
        }

        // Mobil/web istemciler için tutarlı: her zaman API üzerinden servis
        return url('/api/second-hand/images/'.$this->id);
    }
}
