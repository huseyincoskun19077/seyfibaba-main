<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 0;
    public const STATUS_SELLER_APPROVED = 1;
    public const STATUS_ADMIN_APPROVED = 2;
    public const STATUS_ITEM_RECEIVED = 3;
    public const STATUS_REFUNDED = 4;
    public const STATUS_SELLER_REJECTED = 5;
    public const STATUS_ADMIN_REJECTED = 6;
    public const STATUS_USER_CANCELLED = 7;

    protected $fillable = [
        'order_id',
        'user_id',
        'seller_id',
        'order_product_id',
        'reason',
        'details',
        'description',
        'status',
        'qty',
        'refund_amount',
        'refund_method',
        'vendor_response',
        'seller_note',
        'admin_response',
        'admin_note',
        'rejected_reason',
        'refund_transaction_id',
        'refund_status',
        'refund_error',
        'approved_at',
        'rejected_at',
        'refunded_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'refunded_at' => 'datetime',
        'refund_amount' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class);
    }

    public function images()
    {
        return $this->hasMany(ReturnRequestImage::class);
    }

    public function seller()
    {
        return $this->belongsTo(Vendor::class, 'seller_id');
    }

    public function vendor()
    {
        return $this->seller();
    }
}
