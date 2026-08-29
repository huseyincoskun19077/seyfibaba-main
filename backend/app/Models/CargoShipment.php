<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Vendor;

class CargoShipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'seller_id',
        'geliver_shipment_id',
        'geliver_transaction_id',
        'carrier_name',
        'barcode',
        'tracking_number',
        'tracking_url',
        'label_url',
        'status',
        'created_by_type',
        'created_by_id',
        'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function seller()
    {
        return $this->belongsTo(Vendor::class, 'seller_id');
    }
}
