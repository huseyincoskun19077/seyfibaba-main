<?php

namespace App\Http\Controllers\WEB\Seller;

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
        $this->middleware('auth:web');
    }

    public function create()
    {
        $seller = $this->approvedSeller();
        if ($seller instanceof \Illuminate\Http\RedirectResponse) {
            return $seller;
        }

        return view('seller.barcode_product', [
            'commissionRate' => $seller->getEffectiveCommissionRate() ?: 10,
        ]);
    }

    public function lookup(Request $request)
    {
        $seller = $this->approvedSeller();
        if ($seller instanceof \Illuminate\Http\RedirectResponse) {
            return response()->json(['ok' => false, 'message' => 'KYC gerekli'], 403);
        }

        $code = $this->catalog->normalizeBarcode($request->input('barcode'));
        if ($code === '') {
            return response()->json(['ok' => false, 'message' => 'Barkod girin.'], 422);
        }

        $item = $this->catalog->findByBarcode($code);
        if (! $item) {
            return response()->json([
                'ok' => false,
                'message' => 'Bu barkod katalogda yok. Elle ürün ekleyebilirsiniz.',
                'barcode' => $code,
            ], 404);
        }

        $item->load(['category:id,name', 'subCategory:id,name', 'childCategory:id,name', 'brand:id,name']);

        $existing = $this->catalog->sellerAlreadyHasBarcode($seller, $code);

        return response()->json([
            'ok' => true,
            'catalog' => [
                'id' => $item->id,
                'barcode' => $item->barcode,
                'name' => $item->name,
                'short_name' => $item->short_name,
                'short_description' => $item->short_description,
                'category' => $item->category?->name,
                'sub_category' => $item->subCategory?->name,
                'child_category' => $item->childCategory?->name,
                'brand' => $item->brand?->name,
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
        $seller = $this->approvedSeller();
        if ($seller instanceof \Illuminate\Http\RedirectResponse) {
            return $seller;
        }

        $validated = $request->validate([
            'barcode' => 'required|string|max:64',
            'price' => 'required|numeric|min:0.01|max:99999999',
            'quantity' => 'required|integer|min:0|max:999999',
            'offer_price' => 'nullable|numeric|min:0|max:99999999',
        ], [
            'barcode.required' => 'Barkod gerekli.',
            'price.required' => 'Satış fiyatı gerekli.',
            'quantity.required' => 'Stok gerekli.',
        ]);

        $item = $this->catalog->findByBarcode($validated['barcode']);
        if (! $item) {
            return back()->withInput()->with([
                'messege' => 'Barkod katalogda bulunamadı.',
                'alert-type' => 'error',
            ]);
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

        return redirect()
            ->route('seller.product.barcode-create')
            ->with([
                'barcode_product_success' => true,
                'barcode_product_id' => $product->id,
                'barcode_product_name' => $product->name,
                'messege' => 'Ürün barkod kataloğundan eklendi.',
                'alert-type' => 'success',
            ]);
    }

    private function approvedSeller()
    {
        $seller = Auth::guard('web')->user()->seller;

        if (! $seller || $seller->kyc_status !== 'approved') {
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
