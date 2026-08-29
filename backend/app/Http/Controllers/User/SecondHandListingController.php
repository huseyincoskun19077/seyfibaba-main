<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SecondHandListing;
use App\Models\SecondHandListingImage;
use App\Models\SecondHandVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SecondHandListingController extends Controller
{
    private const MAX_IMAGES_PER_LISTING = 6;

    private function ensureSecondHandVerified(int $userId): void
    {
        $ok = SecondHandVerification::query()
            ->where('user_id', $userId)
            ->where('status', SecondHandVerification::STATUS_APPROVED)
            ->exists();

        abort_unless($ok, 403, 'İkinci el ilan vermek için hesabınızı doğrulamanız gerekiyor.');
    }

    private function ensureAllowedCategory(?int $categoryId): void
    {
        if (! $categoryId) {
            return;
        }
        $cat = Category::query()->find($categoryId);
        if ($cat && SecondHandListing::isCosmeticLabel($cat->name, $cat->slug ?? null)) {
            abort(422, 'Kozmetik ürünler ikinci elde satılamaz.');
        }
    }

    public function myListings(Request $request)
    {
        $user = Auth::guard('api')->user();
        $this->ensureSecondHandVerified((int) $user->id);

        $query = SecondHandListing::query()
            ->where('user_id', $user->id)
            ->with(['images'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('condition')) {
            $cond = (string) $request->condition;
            $allowed = [
                SecondHandListing::CONDITION_NEW,
                SecondHandListing::CONDITION_LIGHTLY_USED,
                SecondHandListing::CONDITION_USED,
                SecondHandListing::CONDITION_DEFECTIVE,
            ];
            if (in_array($cond, $allowed, true)) {
                $query->where('condition', $cond);
            }
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            if ($term !== '') {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where('title', 'like', $like);
            }
        }

        $listings = $query->paginate(20)->withQueryString();

        return response()->json([
            'listings' => $listings,
            'condition_options' => SecondHandListing::conditionOptions(),
        ]);
    }

    public function createDraft(Request $request)
    {
        $user = Auth::guard('api')->user();
        $this->ensureSecondHandVerified((int) $user->id);

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],

            'category_id' => ['nullable', 'integer', 'min:1'],
            'sub_category_id' => ['nullable', 'integer', 'min:1'],
            'child_category_id' => ['nullable', 'integer', 'min:1'],

            'city_id' => ['nullable', 'integer', 'min:1'],
            'province' => ['nullable', 'string', 'max:190'],
            'district' => ['nullable', 'string', 'max:120'],
            'locality' => ['nullable', 'string', 'max:190'],
            'neighborhood' => ['nullable', 'string', 'max:190'],

            'condition' => ['required', 'in:new,lightly_used,used,defective'],
        ]);

        $this->ensureAllowedCategory($request->input('category_id') ? (int) $request->input('category_id') : null);

        $listing = SecondHandListing::query()->create([
            'user_id' => $user->id,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'price' => $request->input('price'),
            'category_id' => $request->input('category_id'),
            'sub_category_id' => $request->input('sub_category_id'),
            'child_category_id' => $request->input('child_category_id'),
            'city_id' => $request->input('city_id'),
            'province' => $request->input('province'),
            'district' => $request->input('district'),
            'locality' => $request->input('locality'),
            'neighborhood' => $request->input('neighborhood'),
            'condition' => $request->input('condition'),
            'status' => SecondHandListing::STATUS_DRAFT,
        ]);

        return response()->json([
            'message' => 'Taslak ilan oluşturuldu.',
            'listing' => $listing->load('images'),
        ], 201);
    }

    public function updateDraft(Request $request, $id)
    {
        $user = Auth::guard('api')->user();
        $this->ensureSecondHandVerified((int) $user->id);

        $listing = SecondHandListing::query()
            ->where('id', (int) $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($listing->status !== SecondHandListing::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Sadece taslak ilan düzenlenebilir.',
            ], 422);
        }

        $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],

            'category_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'sub_category_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'child_category_id' => ['sometimes', 'nullable', 'integer', 'min:1'],

            'city_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'province' => ['sometimes', 'nullable', 'string', 'max:190'],
            'district' => ['sometimes', 'nullable', 'string', 'max:120'],
            'locality' => ['sometimes', 'nullable', 'string', 'max:190'],
            'neighborhood' => ['sometimes', 'nullable', 'string', 'max:190'],

            'condition' => ['sometimes', 'required', 'in:new,lightly_used,used,defective'],
        ]);

        if ($request->has('category_id')) {
            $this->ensureAllowedCategory($request->input('category_id') ? (int) $request->input('category_id') : null);
        }

        $listing->fill($request->only([
            'title',
            'description',
            'price',
            'category_id',
            'sub_category_id',
            'child_category_id',
            'city_id',
            'province',
            'district',
            'locality',
            'neighborhood',
            'condition',
        ]));
        $listing->save();

        return response()->json([
            'message' => 'Taslak ilan güncellendi.',
            'listing' => $listing->load('images'),
        ]);
    }

    public function publish(Request $request, $id)
    {
        $user = Auth::guard('api')->user();
        $this->ensureSecondHandVerified((int) $user->id);

        $listing = SecondHandListing::query()
            ->where('id', (int) $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($listing->status !== SecondHandListing::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Sadece taslak ilan yayına alınabilir.',
            ], 422);
        }

        if (trim((string) $listing->title) === '' || (float) $listing->price <= 0) {
            return response()->json([
                'message' => 'İlanı yayına almak için başlık ve fiyat zorunludur.',
            ], 422);
        }

        // Admin onayı: kullanıcı "yayına al" dediğinde ilan onay kuyruğuna düşer.
        $listing->status = SecondHandListing::STATUS_PENDING;
        $listing->submitted_at = now();
        $listing->reviewed_by = null;
        $listing->reviewed_at = null;
        $listing->review_note = null;
        $listing->save();

        return response()->json([
            'message' => 'İlanınız admin onayına gönderildi.',
            'listing' => $listing->load('images'),
        ]);
    }

    public function uploadImage(Request $request, $id)
    {
        $user = Auth::guard('api')->user();
        $this->ensureSecondHandVerified((int) $user->id);

        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $listing = SecondHandListing::query()
            ->where('id', (int) $id)
            ->where('user_id', $user->id)
            ->withCount('images')
            ->firstOrFail();

        if ($listing->status !== SecondHandListing::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Fotoğraf yüklemek için ilan taslak olmalıdır.',
            ], 422);
        }

        if ((int) $listing->images_count >= self::MAX_IMAGES_PER_LISTING) {
            return response()->json([
                'message' => 'Bu ilan için maksimum fotoğraf sayısına ulaşıldı.',
            ], 422);
        }

        $file = $request->file('image');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $extension = 'jpg';
        }

        $dirRel = 'uploads/second-hand/listings/'.$listing->id;
        $dirAbs = public_path($dirRel);
        if (! File::isDirectory($dirAbs)) {
            File::makeDirectory($dirAbs, 0755, true);
        }

        $filename = 'img-'.date('Ymd-His').'-'.Str::lower(Str::random(6)).'.'.$extension;
        $path = $dirRel.'/'.$filename;
        $file->move($dirAbs, $filename);

        $maxSort = (int) SecondHandListingImage::query()
            ->where('listing_id', $listing->id)
            ->max('sort_order');

        $img = SecondHandListingImage::query()->create([
            'listing_id' => $listing->id,
            'file_path' => $path,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json([
            'message' => 'Fotoğraf yüklendi.',
            'image' => $img,
            'listing' => $listing->fresh()->load('images'),
        ], 201);
    }

    public function deleteImage(Request $request, $listingId, $imageId)
    {
        $user = Auth::guard('api')->user();
        $this->ensureSecondHandVerified((int) $user->id);

        $listing = SecondHandListing::query()
            ->where('id', (int) $listingId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($listing->status !== SecondHandListing::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Fotoğraf silmek için ilan taslak olmalıdır.',
            ], 422);
        }

        $img = SecondHandListingImage::query()
            ->where('id', (int) $imageId)
            ->where('listing_id', $listing->id)
            ->firstOrFail();

        if ($img->file_path) {
            $path = (string) $img->file_path;
            if (str_starts_with($path, 'uploads/')) {
                $absolute = public_path($path);
                if (is_file($absolute)) {
                    @unlink($absolute);
                }
            } else {
                Storage::disk('public')->delete($path);
                Storage::disk('local')->delete($path);
            }
        }

        $img->delete();

        return response()->json([
            'message' => 'Fotoğraf silindi.',
            'listing' => $listing->fresh()->load('images'),
        ]);
    }

    public function deactivate(Request $request, $id)
    {
        $user = Auth::guard('api')->user();
        $this->ensureSecondHandVerified((int) $user->id);

        $request->validate([
            'inactive_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $listing = SecondHandListing::query()
            ->where('id', (int) $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($listing->status !== SecondHandListing::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'Sadece yayındaki ilan pasife alınabilir.',
            ], 422);
        }

        $listing->status = SecondHandListing::STATUS_INACTIVE;
        $listing->inactive_reason = $request->input('inactive_reason');
        $listing->deactivated_at = now();
        $listing->save();

        return response()->json([
            'message' => 'İlan pasife alındı.',
            'listing' => $listing->load('images'),
        ]);
    }

    public function activate(Request $request, $id)
    {
        $user = Auth::guard('api')->user();
        $this->ensureSecondHandVerified((int) $user->id);

        $listing = SecondHandListing::query()
            ->where('id', (int) $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($listing->status !== SecondHandListing::STATUS_INACTIVE) {
            return response()->json([
                'message' => 'Sadece pasif ilan tekrar yayına alınabilir.',
            ], 422);
        }

        // Admin onayı: pasiften tekrar aktifleştirme de onay kuyruğuna gider.
        $listing->status = SecondHandListing::STATUS_PENDING;
        $listing->submitted_at = now();
        $listing->reviewed_by = null;
        $listing->reviewed_at = null;
        $listing->review_note = null;
        $listing->inactive_reason = null;
        $listing->deactivated_at = null;
        $listing->save();

        return response()->json([
            'message' => 'İlanınız tekrar admin onayına gönderildi.',
            'listing' => $listing->load('images'),
        ]);
    }

    public function markSold(Request $request, $id)
    {
        $user = Auth::guard('api')->user();
        $this->ensureSecondHandVerified((int) $user->id);

        $listing = SecondHandListing::query()
            ->where('id', (int) $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($listing->status !== SecondHandListing::STATUS_ACTIVE
            && $listing->status !== SecondHandListing::STATUS_INACTIVE) {
            return response()->json([
                'message' => 'Sadece yayındaki veya pasif ilan satıldı olarak işaretlenebilir.',
            ], 422);
        }

        $listing->status = SecondHandListing::STATUS_SOLD;
        $listing->sold_at = now();
        $listing->save();

        return response()->json([
            'message' => 'İlan satıldı olarak işaretlendi.',
            'listing' => $listing->load('images'),
        ]);
    }
}

