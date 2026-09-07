<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SecondHandListing;
use App\Models\SecondHandListingImage;
use App\Models\SecondHandVerification;
use App\Models\SecondHandAgreement;
use App\Models\SecondHandSlider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SecondHandPublicController extends Controller
{
    public function index(Request $request)
    {
        $query = SecondHandListing::query()
            ->where('status', SecondHandListing::STATUS_ACTIVE)
            ->withoutCosmeticCategories()
            ->with(['images', 'city', 'category', 'subCategory', 'childCategory', 'user:id,name'])
            ->addSelect([
                'seller_verified' => SecondHandVerification::query()
                    ->selectRaw('1')
                    ->whereColumn('second_hand_verifications.user_id', 'second_hand_listings.user_id')
                    ->where('second_hand_verifications.status', SecondHandVerification::STATUS_APPROVED)
                    ->latest('second_hand_verifications.id')
                    ->limit(1),
                'seller_business_name' => SecondHandVerification::query()
                    ->select('business_name')
                    ->whereColumn('second_hand_verifications.user_id', 'second_hand_listings.user_id')
                    ->where('second_hand_verifications.status', SecondHandVerification::STATUS_APPROVED)
                    ->latest('second_hand_verifications.id')
                    ->limit(1),
                'seller_c2c_listings_total' => SecondHandListing::query()
                    ->from('second_hand_listings as sh2')
                    ->selectRaw('count(*)')
                    ->whereColumn('sh2.user_id', 'second_hand_listings.user_id'),
                'seller_c2c_listings_active' => SecondHandListing::query()
                    ->from('second_hand_listings as sh2')
                    ->selectRaw('count(*)')
                    ->whereColumn('sh2.user_id', 'second_hand_listings.user_id')
                    ->where('sh2.status', SecondHandListing::STATUS_ACTIVE),
            ]);

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->category_id);
        }
        if ($request->filled('sub_category_id')) {
            $query->where('sub_category_id', (int) $request->sub_category_id);
        }
        if ($request->filled('child_category_id')) {
            $query->where('child_category_id', (int) $request->child_category_id);
        }
        if ($request->filled('city_id')) {
            $query->where('city_id', (int) $request->city_id);
        }
        if ($request->filled('province')) {
            $query->where('province', trim((string) $request->province));
        }
        if ($request->filled('district')) {
            $query->where('district', trim((string) $request->district));
        }
        if ($request->filled('locality')) {
            $query->where('locality', trim((string) $request->locality));
        }
        if ($request->filled('neighborhood')) {
            $query->where('neighborhood', trim((string) $request->neighborhood));
        }
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }
        if ($request->filled('min_price') || $request->filled('max_price')) {
            $min = $request->filled('min_price') ? (float) $request->input('min_price') : null;
            $max = $request->filled('max_price') ? (float) $request->input('max_price') : null;
            if ($min != null && $max != null) {
                if ($max < $min) {
                    [$min, $max] = [$max, $min];
                }
                $query->whereBetween('price', [$min, $max]);
            } elseif ($min != null) {
                $query->where('price', '>=', $min);
            } elseif ($max != null) {
                $query->where('price', '<=', $max);
            }
        }
        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($qq) use ($q) {
                $qq->where('title', 'like', '%'.$q.'%')
                    ->orWhere('description', 'like', '%'.$q.'%');
            });
        }

        // Sıralama
        $sort = trim((string) $request->query('sort', ''));
        if ($sort === 'new') {
            $query->orderByDesc('published_at')->orderByDesc('id');
        } elseif ($sort === 'featured') {
            $query->orderByDesc('is_featured')
                ->orderByDesc('featured_at')
                ->orderByDesc('published_at')
                ->orderByDesc('id');
        } else {
            $query->orderByDesc('views_count')
                ->orderByDesc('published_at')
                ->orderByDesc('id');
        }

        $listings = $query->paginate(20)->withQueryString();

        return response()->json([
            'listings' => $listings,
            'condition_options' => SecondHandListing::conditionOptions(),
        ]);
    }

    public function show($id)
    {
        $listing = SecondHandListing::query()
            ->where('id', (int) $id)
            ->where('status', SecondHandListing::STATUS_ACTIVE)
            ->withoutCosmeticCategories()
            ->with(['images', 'city', 'category', 'subCategory', 'childCategory', 'user:id,name'])
            ->addSelect([
                'seller_verified' => SecondHandVerification::query()
                    ->selectRaw('1')
                    ->whereColumn('second_hand_verifications.user_id', 'second_hand_listings.user_id')
                    ->where('second_hand_verifications.status', SecondHandVerification::STATUS_APPROVED)
                    ->latest('second_hand_verifications.id')
                    ->limit(1),
                'seller_business_name' => SecondHandVerification::query()
                    ->select('business_name')
                    ->whereColumn('second_hand_verifications.user_id', 'second_hand_listings.user_id')
                    ->where('second_hand_verifications.status', SecondHandVerification::STATUS_APPROVED)
                    ->latest('second_hand_verifications.id')
                    ->limit(1),
                'seller_c2c_listings_total' => SecondHandListing::query()
                    ->from('second_hand_listings as sh2')
                    ->selectRaw('count(*)')
                    ->whereColumn('sh2.user_id', 'second_hand_listings.user_id'),
                'seller_c2c_listings_active' => SecondHandListing::query()
                    ->from('second_hand_listings as sh2')
                    ->selectRaw('count(*)')
                    ->whereColumn('sh2.user_id', 'second_hand_listings.user_id')
                    ->where('sh2.status', SecondHandListing::STATUS_ACTIVE),
            ])
            ->firstOrFail();

        // Görüntülenme (increment; refresh kullanma — addSelect ile gelen sanal alanlar kaybolur)
        try {
            $listing->increment('views_count');
        } catch (Throwable $e) {
            Log::warning('second_hand listing views_count increment failed', [
                'listing_id' => $listing->id,
                'message' => $e->getMessage(),
            ]);
        }

        $similarQuery = SecondHandListing::query()
            ->where('status', SecondHandListing::STATUS_ACTIVE)
            ->withoutCosmeticCategories()
            ->where('id', '!=', (int) $listing->id)
            ->with(['images', 'city', 'category', 'subCategory', 'childCategory'])
            ->addSelect([
                'seller_verified' => SecondHandVerification::query()
                    ->selectRaw('1')
                    ->whereColumn('second_hand_verifications.user_id', 'second_hand_listings.user_id')
                    ->where('second_hand_verifications.status', SecondHandVerification::STATUS_APPROVED)
                    ->latest('second_hand_verifications.id')
                    ->limit(1),
                'seller_business_name' => SecondHandVerification::query()
                    ->select('business_name')
                    ->whereColumn('second_hand_verifications.user_id', 'second_hand_listings.user_id')
                    ->where('second_hand_verifications.status', SecondHandVerification::STATUS_APPROVED)
                    ->latest('second_hand_verifications.id')
                    ->limit(1),
            ]);

        // Benzer ilanlar MVP: aynı kategori > aynı şehir > fiyat bandı
        if ($listing->child_category_id) $similarQuery->where('child_category_id', (int) $listing->child_category_id);
        elseif ($listing->sub_category_id) $similarQuery->where('sub_category_id', (int) $listing->sub_category_id);
        elseif ($listing->category_id) $similarQuery->where('category_id', (int) $listing->category_id);

        if ($listing->city_id) $similarQuery->where('city_id', (int) $listing->city_id);

        $price = (float) ($listing->price ?? 0);
        if ($price > 0) {
            $min = max(0, $price * 0.7);
            $max = $price * 1.3;
            $similarQuery->whereBetween('price', [$min, $max]);
        }

        $similar = $similarQuery
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        return response()->json([
            'listing' => $listing,
            'condition_options' => SecondHandListing::conditionOptions(),
            'similar_listings' => $similar,
        ]);
    }

    public function image($imageId)
    {
        $img = SecondHandListingImage::query()
            ->with('listing')
            ->find((int) $imageId);

        if (! $img || ! $img->listing) {
            abort(404);
        }

        $status = (string) $img->listing->status;
        $publicStatuses = [
            SecondHandListing::STATUS_ACTIVE,
            SecondHandListing::STATUS_SOLD,
            SecondHandListing::STATUS_INACTIVE,
            SecondHandListing::STATUS_PENDING,
            SecondHandListing::STATUS_DRAFT,
        ];
        if (! in_array($status, $publicStatuses, true)) {
            abort(404);
        }

        $path = trim((string) $img->file_path);
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '') {
            abort(404);
        }

        $absoluteCandidates = [
            // Yeni kayıtlar: public/uploads/second-hand/listings/...
            public_path($path),
            // storage:link → public/storage/...
            public_path('storage/'.$path),
            // Eski kayıtlar: storage/second_hand/listing_images/... (app/ altında değil)
            storage_path($path),
            // Laravel public disk kökü
            storage_path('app/public/'.$path),
            // Laravel local disk kökü
            storage_path('app/'.$path),
        ];

        // Eski path bazen storage önekiyle kaydedilmiş olabilir
        if (str_starts_with($path, 'storage/')) {
            $absoluteCandidates[] = public_path($path);
            $absoluteCandidates[] = storage_path('app/public/'.substr($path, strlen('storage/')));
        }

        foreach (array_unique($absoluteCandidates) as $absolute) {
            if (is_string($absolute) && $absolute !== '' && is_file($absolute)) {
                return response()->file($absolute);
            }
        }

        // Disk facade (özel root / yetki senaryoları)
        foreach (['public', 'local'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    return Storage::disk($disk)->response($path);
                }
            } catch (Throwable $e) {
                Log::warning('second-hand image disk check failed', [
                    'disk' => $disk,
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::warning('second-hand image file missing', [
            'image_id' => (int) $img->id,
            'listing_id' => (int) $img->listing_id,
            'file_path' => $path,
            'tried' => $absoluteCandidates,
        ]);

        abort(404);
    }

    public function agreements()
    {
        $agreement = SecondHandAgreement::query()->first();

        return response()->json([
            'terms_title' => (string) ($agreement?->terms_title ?? 'İkinci El Kullanım Koşulları'),
            'terms_content' => (string) ($agreement?->terms_content ?? ''),
            'privacy_title' => (string) ($agreement?->privacy_title ?? 'İkinci El KVKK / Gizlilik Metni'),
            'privacy_content' => (string) ($agreement?->privacy_content ?? ''),
            'homepage' => [
                'title' => (string) ($agreement?->homepage_title ?: 'Kuaför malzemeleri al/sat'),
                'subtitle' => (string) ($agreement?->homepage_subtitle ?: 'Doğrulanmış satıcılardan ikinci el ekipman. İlanlara herkes bakabilir; teklif ve mesaj için üye girişi gerekir.'),
                'cta_primary' => (string) ($agreement?->homepage_cta_primary ?: 'İlan ver'),
                'cta_secondary' => (string) ($agreement?->homepage_cta_secondary ?: 'İlanları gör'),
                'image' => $agreement?->homepage_image ? (string) $agreement->homepage_image : null,
                'show_categories' => $agreement?->homepage_show_categories !== false,
                'show_featured' => $agreement?->homepage_show_featured !== false,
                'sliders' => Schema::hasTable('second_hand_sliders')
                    ? SecondHandSlider::query()
                        ->where('status', 1)
                        ->orderBy('serial')
                        ->orderBy('id')
                        ->get(['id', 'image', 'title', 'subtitle', 'link', 'serial'])
                        ->map(function ($row) {
                            return [
                                'id' => (int) $row->id,
                                'image' => (string) $row->image,
                                'title' => (string) ($row->title ?? ''),
                                'subtitle' => (string) ($row->subtitle ?? ''),
                                'link' => (string) ($row->link ?? ''),
                            ];
                        })
                        ->values()
                    : [],
            ],
        ]);
    }
}

