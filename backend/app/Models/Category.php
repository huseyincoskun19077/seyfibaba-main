<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'max_installment' => 'integer',
        'serial' => 'integer',
    ];

    public function subCategories()
    {
        $rel = $this->hasMany(SubCategory::class);
        if (\Illuminate\Support\Facades\Schema::hasColumn('sub_categories', 'serial')) {
            $rel->orderBy('serial');
        }

        return $rel->orderBy('id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function activeSubCategories()
    {
        $rel = $this->hasMany(SubCategory::class)->where('status', 1);
        if (\Illuminate\Support\Facades\Schema::hasColumn('sub_categories', 'serial')) {
            $rel->orderBy('serial');
            $cols = ['id', 'name', 'slug', 'category_id', 'serial'];
        } else {
            $cols = ['id', 'name', 'slug', 'category_id'];
        }

        return $rel->orderBy('id')->select($cols);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'serial')) {
            return $query->orderBy('serial')->orderBy('id');
        }

        return $query->orderBy('id');
    }
}
