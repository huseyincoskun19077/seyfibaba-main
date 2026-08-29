<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionLedger extends Model
{
    use HasFactory;

    protected $table = 'commission_ledger';

    protected $fillable = [
        'order_id',
        'order_product_id',
        'seller_id',
        'gross_amount',
        'commission_rate',
        'commission_amount',
        'seller_net_amount',
        'status',
        'settled_at',
        'notes',
    ];

    protected $casts = [
        'settled_at' => 'datetime',
        'gross_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'seller_net_amount' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'seller_id');
    }

    /**
     * Yalnızca ödenmiş gerçek siparişlere bağlı komisyon kayıtları.
     */
    public function scopePaidOrders($query)
    {
        return $query->whereHas('order', function ($orderQuery) {
            $orderQuery->where('payment_status', 1);
        });
    }
}
