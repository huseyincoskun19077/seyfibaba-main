<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];
    protected $appends = ['averageRating','totalSold'];

    public function getAverageRatingAttribute()
    {
        return $this->avgReview()->avg('rating') ?: '0';
    }

    public function getTotalSoldAttribute()
    {
        return $this->orderProducts()->sum('qty');
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function seller(){
        return $this->belongsTo(Vendor::class,'vendor_id');
    }

    public function brand(){
        return $this->belongsTo(Brand::class);
    }

    public function gallery(){
        return $this->hasMany(ProductGallery::class);
    }

    public function specifications(){
        return $this->hasMany(ProductSpecification::class);
    }

    public function reviews(){
        return $this->hasMany(ProductReview::class);
    }

    public function variants(){
        return $this->hasMany(ProductVariant::class);
    }

    public function orderProducts(){
        return $this->hasMany(OrderProduct::class);
    }

    public function activeVariants(){
        return $this->hasMany(ProductVariant::class)->select(['id','name','product_id']);
    }

    public function variantItems(){
        return $this->hasMany(ProductVariantItem::class);
    }

    public function avgReview(){
        return $this->hasMany(ProductReview::class)->where('status', 1);
    }

    public function category_name(){
        return $this->belongsTo(Category::class,'category_id','id');
    }

    public function viewStats()
    {
        return $this->hasOne(ProductView::class);
    }

    public function incrementViewCount()
    {
        $view = ProductView::firstOrNew(['product_id' => $this->id]);
        $view->view_count = ($view->view_count ?? 0) + 1;
        $view->last_viewed_at = now();
        $view->save();
    }

    public function adminActions()
    {
        return $this->morphMany(AdminAction::class, 'model', 'model_id');
    }

    public static function scopeFilter($query, $request)
    {
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%")
                  ->orWhere('slug', 'like', "%{$request->search}%");
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->vendor_id) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->is_stock === '1') {
            $query->where('qty', '>', 0);
        } elseif ($request->is_stock === '0') {
            $query->where('qty', '<=', 0);
        }

        return $query;
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($product) {
            if ($product->isDirty('qty') && $product->qty <= 0 && $product->status == 1) {
                $product->status = 0;
            }
        });
    }

    public function getLowStockAttribute()
    {
        $threshold = setting('min_stock_threshold') ?? 5;
        return $this->qty <= $threshold;
    }
}