<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'product_id',
        'activity_type',
        'order_id',
        'amount',
        'referrer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Record a user activity
     */
    public static function recordActivity($userId = null, $sessionId = null, $activityType, $productId = null, $orderId = null, $amount = 0, $referrer = null)
    {
        return self::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'ip_address' => request()->ip(),
            'product_id' => $productId,
            'activity_type' => $activityType,
            'order_id' => $orderId,
            'amount' => $amount,
            'referrer' => $referrer
        ]);
    }
}