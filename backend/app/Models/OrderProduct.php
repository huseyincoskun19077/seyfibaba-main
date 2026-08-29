<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'seller_id',
        'product_name',
        'unit_price',
        'qty',
        'vat',
        'commission_rate',
        'commission_amount',
        'seller_net_amount',
    ];

    public function commissionLedger()
    {
        return $this->hasOne(CommissionLedger::class, 'order_product_id');
    }

    public function seller(){
        return $this->belongsTo(Vendor::class,'seller_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function orderProductVariants(){
        return $this->hasMany(OrderProductVariant::class);
    }

    public function order(){
        return $this->belongsTo(Order::class);
    }

}
