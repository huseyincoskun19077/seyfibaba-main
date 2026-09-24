<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryView extends Model
{
    protected $fillable = [
        'story_id',
        'platform',
        'user_id',
        'guest_key',
        'product_index',
        'products_total',
        'completed',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'product_index' => 'integer',
        'products_total' => 'integer',
        'user_id' => 'integer',
        'story_id' => 'integer',
    ];

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * @return array{
     *   total_views:int,
     *   web_views:int,
     *   mobile_views:int,
     *   auth_views:int,
     *   guest_views:int,
     *   unique_users:int,
     *   unique_auth:int,
     *   unique_guests:int,
     *   completions:int,
     *   avg_progress_pct:float
     * }
     */
    public static function statsForStory(int $storyId): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('story_views')) {
            return self::emptyStats();
        }

        $base = static::query()->where('story_id', $storyId);

        $total = (clone $base)->count();
        $web = (clone $base)->where('platform', 'web')->count();
        $mobile = (clone $base)->where('platform', 'mobile')->count();
        $authViews = (clone $base)->whereNotNull('user_id')->count();
        $guestViews = (clone $base)->whereNull('user_id')->count();
        $uniqueAuth = (int) (clone $base)->whereNotNull('user_id')->select('user_id')->distinct()->count('user_id');
        $uniqueGuests = (int) (clone $base)
            ->whereNull('user_id')
            ->whereNotNull('guest_key')
            ->where('guest_key', '!=', '')
            ->select('guest_key')
            ->distinct()
            ->count('guest_key');
        $completions = (clone $base)->where('completed', true)->count();

        $avgPct = 0.0;
        $rows = (clone $base)
            ->where('products_total', '>', 0)
            ->selectRaw('AVG(LEAST(product_index + 1, products_total) / products_total * 100) as avg_pct')
            ->value('avg_pct');
        if ($rows !== null) {
            $avgPct = round((float) $rows, 1);
        }

        return [
            'total_views' => $total,
            'web_views' => $web,
            'mobile_views' => $mobile,
            'auth_views' => $authViews,
            'guest_views' => $guestViews,
            'unique_users' => $uniqueAuth + $uniqueGuests,
            'unique_auth' => $uniqueAuth,
            'unique_guests' => $uniqueGuests,
            'completions' => $completions,
            'avg_progress_pct' => $avgPct,
        ];
    }

    public static function emptyStats(): array
    {
        return [
            'total_views' => 0,
            'web_views' => 0,
            'mobile_views' => 0,
            'auth_views' => 0,
            'guest_views' => 0,
            'unique_users' => 0,
            'unique_auth' => 0,
            'unique_guests' => 0,
            'completions' => 0,
            'avg_progress_pct' => 0.0,
        ];
    }
}
