<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductView extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 
        'view_count', 
        'daily_views',
        'monthly_views', 
        'yearly_views',
        'add_to_cart_count', 
        'purchase_count',
        'total_view_duration',
        'last_viewed_at', 
        'last_cart_at', 
        'last_purchase_at',
        'last_daily_reset',
        'last_monthly_reset'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public static function updateViews($productId)
    {
        $view = self::firstOrCreate(['product_id' => $productId]);
        
        // Increment all view counts
        $view->increment('view_count');
        $view->increment('daily_views');
        $view->increment('monthly_views');
        $view->increment('yearly_views');
        $view->last_viewed_at = now();
        $view->save();
        
        return $view;
    }
    
    public static function updateCart($productId)
    {
        $view = self::firstOrCreate(['product_id' => $productId]);
        $view->increment('add_to_cart_count');
        $view->last_cart_at = now();
        $view->save();
        
        return $view;
    }
    
    public static function updatePurchase($productId)
    {
        $view = self::firstOrCreate(['product_id' => $productId]);
        $view->increment('purchase_count');
        $view->last_purchase_at = now();
        $view->save();
        
        return $view;
    }
}
