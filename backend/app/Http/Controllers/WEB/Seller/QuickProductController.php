<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Setting;
use App\Models\SubCategory;
use App\Models\ChildCategory;
use App\Services\QuickProductService;
use App\Services\AiVisionService;
use App\Services\ProductImageStorage;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Throwable;

class QuickProductController extends Controller
{
    public function __construct(
        private QuickProductService $quickProduct
    ) {
        $this->middleware('auth:web');
    }

    public function create()
    {
        $seller = $this->approvedSeller();
        if ($seller instanceof \Illuminate\Http\RedirectResponse) {
            return $seller;
        }

        $setting = Setting::first();
        $aiEnabled = (bool) $setting->openai_enabled || (bool) $setting->claude_enabled;
        $brands = Brand::query()->where('status', 1)->orderBy('name')->get(['id', 'name']);
        $categories = Category::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);
        $commissionRate = $seller->getEffectiveCommissionRate() ?: 10;

        return view('seller.quick_product', compact('aiEnabled', 'brands', 'categories', 'commissionRate'));
    }

    public function store(Request $request)
    {
        $seller = $this->approvedSeller();
        if ($seller instanceof \Illuminate\Http\RedirectResponse) {
            return $seller;
        }

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:500',
            'quantity' => 'required|integer|min:1|max:999999',
            'price' => 'required|numeric|min:0.01|max:99999999',
            'offer_price' => 'nullable|numeric|min:0|max:99999999',
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('status', 1)],
            'sub_category_id' => ['nullable', 'integer', Rule::exists('sub_categories', 'id')->where('status', 1)],
            'child_category_id' => ['nullable', 'integer', Rule::exists('child_categories', 'id')->where('status', 1)],
            'brand_id' => 'nullable|integer|exists:brands,id',
            'brand_name' => 'nullable|string|max:255',
            'thumb_image' => 'required|image|mimes:jpeg,jpg,png,webp|max:8192',
            'gallery_images' => 'nullable|array|max:10',
            'gallery_images.*' => 'image|mimes:jpeg,jpg,png,webp|max:8192',
            'short_description' => 'nullable|string|max:5000',
            'long_description' => 'nullable|string|max:50000',
            'short_name' => 'nullable|string|max:100',
            'tags' => 'nullable|string|max:1000',
            'seo_title' => 'nullable|string|max:200',
            'seo_description' => 'nullable|string|max:500',
            'sku' => 'nullable|string|max:100',
            'weight' => 'nullable|numeric|min:0',
            'sale_unit_qty' => 'nullable|integer|min:1|max:9999',
            'colors' => 'nullable|array|max:20',
            'colors.*.name' => 'nullable|string|max:80',
            'colors.*.price' => 'nullable|numeric|min:0',
            'colors.*.qty' => 'nullable|integer|min:0',
            'colors.*.image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:8192',
        ], [
            'name.required' => 'Ürün adı gerekli.',
            'quantity.required' => 'Adet gerekli.',
            'price.required' => 'Fiyat gerekli.',
            'category_id.required' => 'Kategori seçmelisiniz.',
            'category_id.exists' => 'Geçerli bir kategori seçin.',
            'thumb_image.required' => 'Ürün fotoğrafı gerekli.',
            'thumb_image.image' => 'Geçerli bir fotoğraf yükleyin.',
        ]);

        $offerPrice = $request->filled('offer_price') ? (float) $request->offer_price : null;
        if ($offerPrice !== null && $offerPrice >= (float) $validated['price']) {
            $offerPrice = null;
        }

        $brandId = $request->filled('brand_id') ? (int) $request->brand_id : null;
        $brandName = trim((string) $request->input('brand_name', ''));

        if ($request->filled('sub_category_id')) {
            $subValid = SubCategory::query()
                ->where('id', (int) $validated['sub_category_id'])
                ->where('category_id', (int) $validated['category_id'])
                ->where('status', 1)
                ->exists();
            if (! $subValid) {
                return redirect()
                    ->back()
                    ->withInput($request->except(['thumb_image', 'gallery_images']))
                    ->with([
                        'messege' => 'Seçilen alt kategori, ana kategori ile uyuşmuyor.',
                        'alert-type' => 'error',
                    ]);
            }
        }

        $userContent = [
            'short_description' => $request->input('short_description'),
            'long_description' => $request->input('long_description'),
            'short_name' => $request->input('short_name'),
            'tags' => $request->input('tags'),
            'seo_title' => $request->input('seo_title'),
            'seo_description' => $request->input('seo_description'),
            'sku' => $request->input('sku'),
            'weight' => $request->input('weight'),
            'sale_unit_qty' => max(1, (int) ($request->input('sale_unit_qty', 1) ?: 1)),
            'colors' => app(\App\Services\SimpleProductColorService::class)->payloadFromRequest($request),
            'child_category_id' => $request->filled('child_category_id') ? (int) $validated['child_category_id'] : null,
            'gallery_images' => $request->file('gallery_images'),
        ];

        try {
            $result = $this->quickProduct->create(
                $seller,
                $validated['name'],
                (int) $validated['quantity'],
                (float) $validated['price'],
                $offerPrice,
                $request->file('thumb_image'),
                $brandId,
                $brandName !== '' ? $brandName : null,
                (int) $validated['category_id'],
                $request->filled('sub_category_id') ? (int) $validated['sub_category_id'] : null,
                $userContent
            );

            $product = $result['product'];

            return redirect()
                ->route('seller.product.quick-create')
                ->with([
                    'quick_product_success' => true,
                    'quick_product_id' => $product->id,
                    'quick_product_name' => $product->name,
                    'messege' => 'Ürününüz yayına alındı: ' . $product->name,
                    'alert-type' => 'success',
                ]);
        } catch (Throwable $e) {
            Log::error('Quick product store failed', [
                'seller_id' => $seller->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput($request->except(['thumb_image', 'gallery_images']))
                ->with([
                    'messege' => $e->getMessage() ?: 'Ürün eklenemedi. Lütfen tekrar deneyin.',
                    'alert-type' => 'error',
                ]);
        }
    }

    public function aiFill(Request $request)
    {
        $seller = $this->approvedSeller();
        if ($seller instanceof \Illuminate\Http\RedirectResponse) {
            return response()->json(['error' => 'Seller not approved'], 403);
        }

        $request->validate([
            'name' => 'required|string|min:2|max:500',
            'thumb_image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:8192',
            'price' => 'nullable|numeric',
        ]);

        try {
            $aiVision = app(AiVisionService::class);
            $categoryTree = $this->quickProduct->buildCategoryTree();

            $image = $request->file('thumb_image');
            $ai = null;
            if ($image) {
                try {
                    $ai = $aiVision->analyzeProduct(
                        $image,
                        $request->input('name'),
                        (float) ($request->input('price') ?: 0),
                        null,
                        1,
                        $categoryTree
                    );
                } catch (Throwable $e) {
                    Log::warning('Quick product AI fill failed', ['error' => $e->getMessage()]);
                }
            }

            if (! is_array($ai)) {
                $ai = $this->quickProduct->textOnlyFallback($request->input('name'));
            }

            return response()->json([
                'short_description' => $ai['short_description'] ?? '',
                'long_description' => $ai['long_description'] ?? '',
                'tags' => $ai['tags'] ?? '',
                'short_name' => $ai['short_name'] ?? '',
                'seo_title' => $ai['seo_title'] ?? '',
                'seo_description' => $ai['seo_description'] ?? '',
            ]);
        } catch (Throwable $e) {
            Log::warning('Quick product AI fill failed', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Açıklama şu an doldurulamadı. Kendiniz yazabilir veya daha sonra tekrar deneyebilirsiniz.',
            ], 500);
        }
    }

    private function approvedSeller()
    {
        $seller = Auth::guard('web')->user()->seller;

        if (!$seller || $seller->kyc_status !== 'approved') {
            return redirect()
                ->route('seller.kyc')
                ->with([
                    'messege' => 'Ürün ekleyebilmek için hesap doğrulamanızı (KYC) tamamlamanız gerekmektedir.',
                    'alert-type' => 'error',
                ]);
        }

        return $seller;
    }
}
