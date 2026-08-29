<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use App\Services\StockAlertService;
use Illuminate\Http\Request;

class StockAlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $setting = Setting::query()->first();

        $products = Product::query()
            ->with('seller:id,shop_name,user_id')
            ->where('status', 1)
            ->orderBy('qty', 'asc')
            ->get()
            ->filter(function (Product $product) use ($setting) {
                $threshold = StockAlertService::relativeThreshold($product, $setting);

                return (int) $product->qty <= $threshold;
            })
            ->values();

        return view('admin.stock_alerts', compact('setting', 'products'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'low_stock_relative_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'low_stock_min_qty' => ['required', 'integer', 'min:1', 'max:1000'],
            'stock_alert_enabled' => ['required', 'boolean'],
            'product_view_reminder_count' => ['required', 'integer', 'min:1', 'max:20'],
            'product_view_reminder_cooldown_days' => ['required', 'integer', 'min:1', 'max:90'],
        ]);

        $setting = Setting::query()->firstOrFail();
        $setting->low_stock_relative_percent = (int) $request->input('low_stock_relative_percent');
        $setting->low_stock_min_qty = (int) $request->input('low_stock_min_qty');
        $setting->stock_alert_enabled = (bool) $request->input('stock_alert_enabled');
        $setting->product_view_reminder_count = (int) $request->input('product_view_reminder_count');
        $setting->product_view_reminder_cooldown_days = (int) $request->input('product_view_reminder_cooldown_days');
        $setting->save();

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }
}