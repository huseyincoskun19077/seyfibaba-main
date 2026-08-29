<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use App\Services\QuickProductService;
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
        $this->middleware('auth:api');
    }

    public function store(Request $request)
    {
        $seller = Auth::guard('api')->user()->seller;

        if (!$seller || $seller->kyc_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Ürün ekleyebilmek için KYC doğrulamanızı tamamlamanız gerekmektedir.',
            ], 403);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Seçilen alt kategori, ana kategori ile uyuşmuyor.',
                ], 422);
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
            'child_category_id' => $request->filled('child_category_id') ? (int) $validated['child_category_id'] : null,
            'colors' => app(\App\Services\SimpleProductColorService::class)->payloadFromRequest($request),
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

            return response()->json([
                'success' => true,
                'message' => 'Ürün yayına alındı.',
                'product' => [
                    'id' => $result['product']->id,
                    'name' => $result['product']->name,
                    'slug' => $result['product']->slug,
                ],
            ], 201);
        } catch (Throwable $e) {
            Log::error('API quick product failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Ürün eklenemedi.',
            ], 500);
        }
    }
}
