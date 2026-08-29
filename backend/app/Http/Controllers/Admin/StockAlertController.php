<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;

class StockAlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin-api');
    }

    public function settings()
    {
        $setting = Setting::first();

        return response()->json([
            'low_stock_threshold' => $setting->low_stock_threshold ?? 5,
            'stock_alert_enabled' => (bool) ($setting->stock_alert_enabled ?? true),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'low_stock_threshold' => 'required|integer|min:1|max:1000',
            'stock_alert_enabled' => 'required|boolean',
        ]);

        $setting = Setting::first();
        $setting->low_stock_threshold = $request->low_stock_threshold;
        $setting->stock_alert_enabled = $request->stock_alert_enabled;
        $setting->save();

        return response()->json(['message' => 'Stock alert settings updated']);
    }

    public function lowStockProducts(Request $request)
    {
        $setting = Setting::first();
        $threshold = $setting->low_stock_threshold ?? 5;

        $products = Product::where('qty', '<=', $threshold)
            ->where('status', 1)
            ->with('seller:id,shop_name,user_id')
            ->select('id', 'name', 'slug', 'qty', 'vendor_id', 'sku', 'thumb_image')
            ->orderBy('qty', 'asc')
            ->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'products' => $products,
            'threshold' => $threshold,
        ]);
    }
}
