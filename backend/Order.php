<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;


    public function user(){
        return $this->belongsTo(User::class) ->withDefault([
            'name' => $this->orderAddress?->billing_name ?? 'Guest'
        ]);
    }

    public function orderProducts(){
        return $this->hasMany(OrderProduct::class);
    }

    public function orderAddress(){
        return $this->hasOne(OrderAddress::class);
    }

    public function deliveryman(){
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id', 'id');
    }

    public function vendor(){
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function coupon(){
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function cargoShipment()
    {
        return $this->hasOne(CargoShipment::class)->latestOfMany();
    }
}
