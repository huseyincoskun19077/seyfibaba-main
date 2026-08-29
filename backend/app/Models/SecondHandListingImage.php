<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        $path = trim((string) $this->file_path);
        if ($path === '') {
            return null;
        }

        // public/uploads/... doğrudan web erişimi
        if (str_starts_with($path, 'uploads/')) {
            return url($path);
        }

        // public disk (storage/app/public)
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        // API üzerinden servis (local disk / eski kayıtlar)
        return url('/api/second-hand/images/'.$this->id);
    }
}
