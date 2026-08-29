<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProductView extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'view_count',
        'last_viewed_at',
        'reminded_at',
    ];

    protected $casts = [
        'last_viewed_at' => 'datetime',
        'reminded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
