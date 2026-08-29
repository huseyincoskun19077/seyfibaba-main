<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IyzicoPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'api_key',
        'secret_key',
        'is_test_mode',
        'marketplace_mode',
        'sub_merchant_key',
        'store_sub_merchant_keys',
        'currency_id',
    ];

    public function currency()
    {
        return $this->belongsTo(MultiCurrency::class);
    }
}
