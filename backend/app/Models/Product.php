<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
class Product extends Model
{
    use HasFactory;

    protected $appends = ['averageRating', 'totalSold', 'unit_price', 'offer_unit_price'];

    protected $casts = [
        'sale_unit_qty' => 'integer',
    ];

    public function resolvedSaleUnitQty(): int
    {
        $qty = (int) ($this->attributes['sale_unit_qty'] ?? 1);

        return $qty > 0 ? $qty : 1;
    }

    public function getUnitPriceAttribute(): float
    {
        $units = $this->resolvedSaleUnitQty();
        $price = (float) ($this->attributes['price'] ?? 0);

        return $units > 0 ? round($price / $units, 2) : $price;
    }

    public function getOfferUnitPriceAttribute(): ?float
    {
        $offer = (float) ($this->attributes['offer_price'] ?? 0);
        if ($offer <= 0) {
            return null;
        }

        $units = $this->resolvedSaleUnitQty();

        return $units > 0 ? round($offer / $units, 2) : $offer;
    }

    public function getAverageRatingAttribute()
    {
        try {
            return $this->avgReview()->avg('rating') ?: '0';
        } catch (\Throwable $e) {
            return '0';
        }
    }

    public function getTotalSoldAttribute()
    {
        try {
            return (int) $this->orderProducts()->sum('qty');
        } catch (\Throwable $e) {
            return 0;
        }
    }




    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function subCategory(){
        return $this->belongsTo(SubCategory::class,'sub_category_id');
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

    public function isSalonFurnitureInquiryEligible(): bool
    {
        if (! $this->relationLoaded('category')) {
            $this->load('category');
        }
        if (! $this->relationLoaded('subCategory')) {
            $this->load('subCategory');
        }

        $parts = [
            $this->category?->name,
            $this->category?->slug,
            $this->subCategory?->name,
            $this->subCategory?->slug,
        ];

        $childId = (int) ($this->child_category_id ?? 0);
        if ($childId > 0) {
            $child = ChildCategory::query()->find($childId);
            $parts[] = $child?->name;
            $parts[] = $child?->slug;
        }

        $haystack = mb_strtolower(
            mb_convert_encoding(implode(' ', array_filter($parts)), 'UTF-8', 'UTF-8'),
            'UTF-8'
        );

        return str_contains($haystack, 'mobilya');
    }

    protected $guarded = [];

}
