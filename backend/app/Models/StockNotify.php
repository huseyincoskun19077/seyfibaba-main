<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockNotify extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'email',
        'notified',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}