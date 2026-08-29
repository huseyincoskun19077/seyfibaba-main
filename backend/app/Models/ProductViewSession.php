<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductViewSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'session_id',
        'ip_address',
        'duration',
        'engaged',
        'referrer'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate engagement based on duration
     * Users who stay for more than 10 seconds are considered "engaged"
     */
    public static function calculateEngagement($duration)
    {
        return $duration >= 10; // 10 seconds threshold
    }

    /**
     * Record a product view session
     */
    public static function recordView($productId, $userId = null, $sessionId = null, $duration = 0, $referrer = null)
    {
        return self::create([
            'product_id' => $productId,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'ip_address' => request()->ip(),
            'duration' => $duration,
            'engaged' => self::calculateEngagement($duration),
            'referrer' => $referrer
        ]);
    }
}