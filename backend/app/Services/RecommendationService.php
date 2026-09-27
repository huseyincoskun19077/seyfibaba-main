<?php

namespace App\Services;

use App\Models\CustomerSegment;
use App\Models\GuestProductView;
use App\Models\Product;
use App\Models\RecommendationSetting;
use App\Models\User;
use App\Models\UserProductView;
use App\Services\CustomerSegmentService;
use App\Support\ProductFilterHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Öneri motoru: alan > gezinme geçmişi > işletme türü > popülerlik + satıcı çeşitliliği.
 */
class RecommendationService
{
    /** Aynı ürün için view_count artışı arası minimum süre (sayfa yenileme koruması). */
    public const VIEW_DEDUP_MINUTES = 30;

    public function settings(): RecommendationSetting
    {
        return RecommendationSetting::current();
    }

    /**
     * Görüntüleme kaydı (üye).
     * Aynı ürünün kısa sürede tekrar yüklenmesi (sayfa yenileme) view_count'u şişirmez.
     */
    public function recordUserView(User $user, int $productId): void
    {
        if (! $this->personalizationAllowed($user)) {
            return;
        }

        $row = UserProductView::query()->firstOrNew([
            'user_id' => $user->id,
            'product_id' => $productId,
        ]);

        if ($row->exists && $row->last_viewed_at && $row->last_viewed_at->gt(now()->subMinutes(self::VIEW_DEDUP_MINUTES))) {
            $row->last_viewed_at = now();
            $row->save();

            return;
        }

        $row->view_count = (int) $row->view_count + 1;
        $row->last_viewed_at = now();
        $row->save();
    }

    /**
     * Misafir görüntüleme — yalnızca pazarlama/kişiselleştirme izni varken.
     */
    public function recordGuestView(string $guestKey, int $productId, bool $hasConsent): void
    {
        if (! $hasConsent || $guestKey === '') {
            return;
        }

        $row = GuestProductView::query()->firstOrNew([
            'guest_key' => mb_substr($guestKey, 0, 64),
            'product_id' => $productId,
        ]);

        if ($row->exists && $row->last_viewed_at && $row->last_viewed_at->gt(now()->subMinutes(self::VIEW_DEDUP_MINUTES))) {
            $row->last_viewed_at = now();
            $row->save();

            return;
        }

        $row->view_count = (int) $row->view_count + 1;
        $row->last_viewed_at = now();
        $row->save();
    }

    public function clearUserHistory(User $user): int
    {
        return UserProductView::query()->where('user_id', $user->id)->delete();
    }

    public function personalizationAllowed(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if (property_exists($user, 'personalization_enabled') || isset($user->personalization_enabled)) {
            return (bool) ($user->personalization_enabled ?? true);
        }

        return true;
    }

    /**
     * @return list<int> product ids scored then diversity-trimmed
     */
    public function recommendProductIds(
        ?User $user,
        ?string $segmentSlug = null,
        ?int $lockCategoryId = null,
        ?int $lockSubCategoryId = null,
        ?string $guestKey = null,
        bool $guestConsent = false,
        int $limit = 12
    ): array {
        $scored = $this->computeScores(
            $user,
            $segmentSlug,
            $lockCategoryId,
            $lockSubCategoryId,
            $guestKey,
            $guestConsent
        );
        $orderedIds = array_map('intval', array_keys($scored));

        return $this->applyVendorCap($orderedIds, $limit, (int) $this->settings()->vendor_diversity);
    }

    /**
     * Test / debug: ürün id → skor (yüksekten düşüğe sıralı).
     *
     * @return array<int, float>
     */
    public function recommendScoreMap(
        ?User $user,
        ?string $segmentSlug = null,
        ?int $lockCategoryId = null,
        ?int $lockSubCategoryId = null,
        ?string $guestKey = null,
        bool $guestConsent = false
    ): array {
        return $this->computeScores(
            $user,
            $segmentSlug,
            $lockCategoryId,
            $lockSubCategoryId,
            $guestKey,
            $guestConsent
        );
    }

    /**
     * @return array<int, float>
     */
    private function computeScores(
        ?User $user,
        ?string $segmentSlug,
        ?int $lockCategoryId,
        ?int $lockSubCategoryId,
        ?string $guestKey,
        bool $guestConsent
    ): array {
        $settings = $this->settings();
        $scores = [];

        $history = $this->loadHistorySignals($user, $guestKey, $guestConsent, $settings);
        $types = ($user && $this->personalizationAllowed($user))
            ? $this->userBusinessTypeCodes($user)
            : [];

        // Kişisel sinyal yoksa boş dön → üst katman popüler fallback kullanır
        if ($history === [] && ! $segmentSlug && $types === []) {
            return [];
        }

        $base = Product::query()->where('status', 1)->where('approve_by_admin', 1);
        if ($lockCategoryId) {
            $base->where('category_id', $lockCategoryId);
        }
        if ($lockSubCategoryId) {
            $base->where('sub_category_id', $lockSubCategoryId);
        }

        $candidateIds = (clone $base)->orderByDesc('id')->limit(800)->pluck('id')->all();
        if ($candidateIds === []) {
            return [];
        }

        foreach ($candidateIds as $id) {
            $scores[(int) $id] = 0.0;
        }

        // 1) Müşteri alanı
        if ($segmentSlug) {
            $segment = CustomerSegment::query()->active()->where('slug', $segmentSlug)->first();
            if ($segment) {
                $segIds = app(CustomerSegmentService::class)
                    ->productQueryForSegment($segment)
                    ->whereIn('id', $candidateIds)
                    ->pluck('id');
                foreach ($segIds as $id) {
                    $scores[(int) $id] = ($scores[(int) $id] ?? 0) + $settings->weight_segment;
                }
            }
        }

        // 2) Gezinme geçmişi → aynı kategori/alt kategorideki ürünler (Trendyol tarzı)
        $viewedIds = [];
        foreach ($history as $h) {
            $viewedIds[] = (int) $h['product_id'];
            $related = Product::query()
                ->where('status', 1)
                ->where('approve_by_admin', 1)
                ->where('id', '!=', $h['product_id'])
                ->where(function ($q) use ($h) {
                    $q->where('category_id', $h['category_id']);
                    if (! empty($h['sub_category_id'])) {
                        $q->orWhere('sub_category_id', $h['sub_category_id']);
                    }
                })
                ->when($lockCategoryId, fn ($q) => $q->where('category_id', $lockCategoryId))
                ->when($lockSubCategoryId, fn ($q) => $q->where('sub_category_id', $lockSubCategoryId))
                ->orderByDesc('id')
                ->limit(60)
                ->pluck('id');

            foreach ($related as $rid) {
                $rid = (int) $rid;
                if (! isset($scores[$rid])) {
                    $scores[$rid] = 0.0;
                }
                $scores[$rid] += $settings->weight_browse_history * $h['weight'];
            }
        }

        if ($settings->exclude_viewed_product) {
            foreach ($viewedIds as $vid) {
                unset($scores[$vid]);
            }
        }

        // 3) İşletme türleri — taxonomy varsa onu kullan; yoksa kategori/ürün adına göre eşle
        if ($types !== []) {
            $this->applyBusinessTypeBoost($scores, $types, $settings);
        }

        // 4) Popülerlik (sold_qty) — işletme türünden sonra; tek başına ilk sırayı çalmaz
        $popular = Product::query()
            ->whereIn('id', array_keys($scores))
            ->orderByDesc('sold_qty')
            ->limit(100)
            ->pluck('sold_qty', 'id');
        foreach ($popular as $id => $sold) {
            $scores[(int) $id] = ($scores[(int) $id] ?? 0) + min(10, (float) $sold) * ($settings->weight_popularity / 10);
        }

        arsort($scores);

        return $scores;
    }

    /**
     * İşletme türü → ilgili ürünleri yükselt; ortak ürünlere kısmi puan; ilgisiz (örn. tırnak) yükseltme.
     *
     * @param  array<int, float>  $scores
     * @param  list<string>  $types
     */
    private function applyBusinessTypeBoost(array &$scores, array $types, RecommendationSetting $settings): void
    {
        $primaryCodes = $this->businessTypesToSegmentCodes($types);
        if ($primaryCodes === []) {
            return;
        }

        $weight = (float) $settings->weight_business_type;
        $segmentSvc = app(CustomerSegmentService::class);
        $boosted = [];

        $segments = CustomerSegment::query()->active()->whereIn('code', $primaryCodes)->get();
        foreach ($segments as $segment) {
            $ids = $segmentSvc
                ->productQueryForSegment($segment)
                ->whereIn('id', array_keys($scores))
                ->pluck('id');
            foreach ($ids as $id) {
                $id = (int) $id;
                if (isset($boosted[$id])) {
                    continue;
                }
                $scores[$id] = ($scores[$id] ?? 0) + $weight;
                $boosted[$id] = 'primary';
            }
        }

        // Taxonomy henüz boş olsa bile kategori / ürün adıyla işletme türünü uygula
        $products = Product::query()
            ->with(['category:id,name', 'subCategory:id,name'])
            ->whereIn('id', array_keys($scores))
            ->get(['id', 'name', 'category_id', 'sub_category_id']);

        foreach ($products as $product) {
            $id = (int) $product->id;
            if (isset($boosted[$id])) {
                continue;
            }

            $subNorm = Str::lower(Str::ascii((string) ($product->subCategory->name ?? '')));
            $catNorm = Str::lower(Str::ascii((string) ($product->category->name ?? '')));
            $nameNorm = Str::lower(Str::ascii((string) ($product->name ?? '')));
            $codes = $segmentSvc->suggestCodesForNames($subNorm, $catNorm, $nameNorm);

            if (preg_match('/\b(erkek|berber|sakal|tiras)\b/u', $nameNorm)) {
                $codes[] = 'men_barber';
            }
            if (preg_match('/\b(bayan|kadin|kadinlar)\b/u', $nameNorm)) {
                $codes[] = 'women_salon';
            }
            if (preg_match('/\b(oje|manikur|pedikur|protez\s*tirnak|jel\s*tirnak|tirnak)\b/u', $nameNorm)) {
                $codes[] = 'nail';
            }
            $codes = array_values(array_unique($codes));

            if (array_intersect($codes, $primaryCodes) !== []) {
                $scores[$id] = ($scores[$id] ?? 0) + $weight;
                $boosted[$id] = 'primary';
            } elseif (in_array('shared', $codes, true)) {
                $scores[$id] = ($scores[$id] ?? 0) + ($weight * 0.5);
                $boosted[$id] = 'shared';
            }
        }
    }

    /**
     * @return list<array{product_id:int,category_id:int,sub_category_id:int,weight:float}>
     */
    private function loadHistorySignals(?User $user, ?string $guestKey, bool $guestConsent, RecommendationSetting $settings): array
    {
        $since = Carbon::now()->subDays(max(1, (int) $settings->history_days));
        // Tek tıklama = sinyal (Trendyol tarzı)
        $minViews = 1;
        $rows = collect();

        if ($user && $this->personalizationAllowed($user)) {
            $rows = UserProductView::query()
                ->where('user_id', $user->id)
                ->where('last_viewed_at', '>=', $since)
                ->where('view_count', '>=', $minViews)
                ->orderByDesc('last_viewed_at')
                ->limit(30)
                ->get();
        } elseif ($guestConsent && $guestKey) {
            $rows = GuestProductView::query()
                ->where('guest_key', mb_substr($guestKey, 0, 64))
                ->where('last_viewed_at', '>=', $since)
                ->where('view_count', '>=', $minViews)
                ->orderByDesc('last_viewed_at')
                ->limit(30)
                ->get();
        }

        $out = [];
        foreach ($rows as $row) {
            $product = Product::query()->find($row->product_id, ['id', 'category_id', 'sub_category_id']);
            if (! $product) {
                continue;
            }
            $daysAgo = max(0, $row->last_viewed_at?->diffInDays(now()) ?? 0);
            $decay = max(0.25, 1 - ($daysAgo / max(1, (int) $settings->history_days)));
            // İlk tıklamada tam sinyal; tekrar bakışlarda biraz daha güçlenir
            $freq = min(1.0, 0.85 + (0.15 * min(2, max(0, (int) $row->view_count - 1))));
            $out[] = [
                'product_id' => (int) $product->id,
                'category_id' => (int) $product->category_id,
                'sub_category_id' => (int) $product->sub_category_id,
                'weight' => $decay * $freq,
            ];
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public function userBusinessTypeCodes(?User $user): array
    {
        if (! $user) {
            return [];
        }
        $types = [];
        if (! empty($user->business_types)) {
            $decoded = is_array($user->business_types)
                ? $user->business_types
                : json_decode((string) $user->business_types, true);
            if (is_array($decoded)) {
                $types = array_values(array_filter(array_map('strval', $decoded)));
            }
        }
        if ($types === [] && ! empty($user->business_type)) {
            $types = [(string) $user->business_type];
        }

        return $types;
    }

    /**
     * @param  list<string>  $types
     * @return list<string>
     */
    public function businessTypesToSegmentCodes(array $types): array
    {
        $map = [
            'female_hairdresser' => 'women_salon',
            'male_hairdresser' => 'men_barber',
            'barber' => 'men_barber',
            'beauty_salon' => 'beauty_salon',
            'nail_art' => 'nail',
        ];
        $codes = [];
        foreach ($types as $t) {
            if (isset($map[$t])) {
                $codes[] = $map[$t];
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * @param  list<int>  $orderedIds
     * @return list<int>
     */
    private function applyVendorCap(array $orderedIds, int $limit, int $perVendor): array
    {
        if ($orderedIds === []) {
            return [];
        }
        $products = Product::query()->whereIn('id', $orderedIds)->get(['id', 'vendor_id'])->keyBy('id');
        $counts = [];
        $picked = [];
        foreach ($orderedIds as $id) {
            $p = $products->get($id);
            if (! $p) {
                continue;
            }
            $vid = (int) $p->vendor_id;
            $counts[$vid] = ($counts[$vid] ?? 0) + 1;
            if ($counts[$vid] > $perVendor) {
                continue;
            }
            $picked[] = $id;
            if (count($picked) >= $limit) {
                break;
            }
        }

        return $picked;
    }
}
