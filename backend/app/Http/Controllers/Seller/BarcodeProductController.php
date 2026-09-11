<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\BarcodeCatalogService;
use App\Support\ProductImageUrl;
use Auth;
use Illuminate\Http\Request;

class BarcodeProductController extends Controller
{
    public function __construct(
        private BarcodeCatalogService $catalog
    ) {
        $this->middleware('auth:api');
    }

    public function lookup(Request $request)
    {
        $seller = Auth::guard('api')->user()?->seller;
        if (! $seller || $seller->kyc_status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'KYC gerekli'], 403);
        }

        $code = $this->catalog->normalizeBarcode($request->input('barcode'));
        if ($code === '') {
            return response()->json(['success' => false, 'message' => 'Barkod girin.'], 422);
        }

        $item = $this->catalog->findByBarcode($code);
        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Bu barkod katalogda yok.',
                'barcode' => $code,
            ]);
        }

        $item->load(['category:id,name']);

        $existing = $this->catalog->sellerAlreadyHasBarcode($seller, $code);

        return response()->json([
            'success' => true,
            'catalog' => [
                'id' => $item->id,
                'barcode' => $item->barcode,
                'name' => $item->name,
                'short_name' => $item->short_name,
                'short_description' => $item->short_description,
                'category' => $item->category?->name,
                'thumb_url' => product_image_url($item->thumb_image),
                'has_image' => ProductImageUrl::hasImage($item->thumb_image),
            ],
            'already_owned' => $existing ? [
                'id' => $existing->id,
                'name' => $existing->name,
            ] : null,
        ]);
    }

    public function store(Request $request)
    {
        $seller = Auth::guard('api')->user()?->seller;
        if (! $seller || $seller->kyc_status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'KYC gerekli'], 403);
        }

        $validated = $request->validate([
            'barcode' => 'required|string|max:64',
            'price' => 'required|numeric|min:0.01|max:99999999',
            'quantity' => 'required|integer|min:0|max:999999',
            'offer_price' => 'nullable|numeric|min:0|max:99999999',
        ]);

        $item = $this->catalog->findByBarcode($validated['barcode']);
        if (! $item) {
            return response()->json(['success' => false, 'message' => 'Barkod katalogda yok.'], 404);
        }

        $offer = $request->filled('offer_price') ? (float) $request->offer_price : null;
        if ($offer !== null && $offer >= (float) $validated['price']) {
            $offer = null;
        }

        $product = $this->catalog->createSellerProduct(
            $seller,
            $item,
            (float) $validated['price'],
            (int) $validated['quantity'],
            $offer
        );

        return response()->json([
            'success' => true,
            'message' => 'Ürün barkod kataloğundan eklendi.',
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'price' => $product->price,
                'qty' => $product->qty,
                'status' => $product->status,
            ],
        ]);
    }
}
