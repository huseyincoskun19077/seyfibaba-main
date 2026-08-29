<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use App\Services\StockAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockAlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function lowStockProducts(Request $request)
    {
        $user = Auth::guard('api')->user();
        $vendor = $user->seller;

        if (!$vendor) {
            return response()->json(['message' => 'Seller account not found'], 403);
        }

        $setting = Setting::query()->first();

        $products = Product::query()
            ->where('vendor_id', $vendor->id)
            ->where('status', 1)
            ->select('id', 'name', 'slug', 'qty', 'initial_qty', 'sku', 'thumb_image')
            ->orderBy('qty', 'asc')
            ->get()
            ->filter(function (Product $product) use ($setting) {
                return (int) $product->qty <= StockAlertService::relativeThreshold($product, $setting);
            })
            ->values();

        return response()->json([
            'products' => $products,
            'relative_percent' => (int) ($setting->low_stock_relative_percent ?? 20),
        ]);
    }
}
