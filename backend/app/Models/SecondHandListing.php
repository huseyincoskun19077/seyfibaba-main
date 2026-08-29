<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecondHandListing extends Model
{
    use HasFactory;

    public const CONDITION_NEW = 'new';
    public const CONDITION_LIGHTLY_USED = 'lightly_used';
    public const CONDITION_USED = 'used';
    public const CONDITION_DEFECTIVE = 'defective';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SOLD = 'sold';

    protected $table = 'second_hand_listings';

    protected $fillable = [
        'user_id',
        'category_id',
        'sub_category_id',
        'child_category_id',
        'title',
        'description',
        'price',
        'city_id',
        'province',
        'district',
        'locality',
        'neighborhood',
        'condition',
        'status',
        'is_featured',
        'is_urgent',
        'featured_at',
        'inactive_reason',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'published_at',
        'deactivated_at',
        'sold_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'published_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'sold_at' => 'datetime',
        'featured_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_urgent' => 'boolean',
        'views_count' => 'integer',
    ];

    /**
     * İlan durumu (Letgo / ikinci el pazarlarında yaygın satıcı dili).
     * Anahtarlar API/DB’de aynı kalır; yalnızca kullanıcıya gösterilen metin değişir.
     */
    public static function conditionOptions(): array
    {
        return [
            self::CONDITION_NEW => 'Sıfır',
            self::CONDITION_LIGHTLY_USED => 'Sıfır ayarında',
            self::CONDITION_USED => 'İyi durumda',
            self::CONDITION_DEFECTIVE => 'Yıpranmış veya onarım gerekebilir',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(SecondHandListingImage::class, 'listing_id')->orderBy('sort_order')->orderBy('id');
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    public function childCategory()
    {
        return $this->belongsTo(ChildCategory::class, 'child_category_id');
    }

    public static function isCosmeticLabel(?string $name, ?string $slug = null): bool
    {
        $hay = mb_strtolower(trim(($name ?? '').' '.($slug ?? '')));

        return $hay !== '' && str_contains($hay, 'kozmetik');
    }

    public static function cosmeticCategoryIds(): array
    {
        return Category::query()
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%kozmetik%'])
                    ->orWhereRaw('LOWER(COALESCE(slug, "")) LIKE ?', ['%kozmetik%']);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function scopeWithoutCosmeticCategories($query)
    {
        $ids = self::cosmeticCategoryIds();
        if ($ids === []) {
            return $query;
        }

        return $query->where(function ($q) use ($ids) {
            $q->whereNull('category_id')->orWhereNotIn('category_id', $ids);
        });
    }
}

