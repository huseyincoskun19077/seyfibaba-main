<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Category;
use App\Models\ProductView;
use App\Models\ShoppingCart;
use App\Models\Coupon;
use App\Models\ProductReport;
use App\Models\FlashSaleProduct;
use App\Models\FlashSale;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        return $this->stockReport();
    }

    // 1. STOK RAPORU
    public function stockReport()
    {
        $lowStock = Product::where('qty', '>', 0)
            ->where('qty', '<=', 10)
            ->with('category', 'brand', 'seller')
            ->orderBy('qty', 'asc')
            ->paginate(20);

        $outOfStock = Product::where('qty', 0)
            ->with('category', 'brand', 'seller')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        $stockByCategory = Product::select('category_id', DB::raw('SUM(qty) as total_qty'), DB::raw('COUNT(*) as product_count'))
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->orderByDesc('total_qty')
            ->get();

        $totalStock = Product::sum('qty');
        $totalValue = Product::selectRaw('SUM(qty * COALESCE(offer_price, price)) as total')->first()->total ?? 0;

        return view('admin.analytics.stock', compact('lowStock', 'outOfStock', 'stockByCategory', 'totalStock', 'totalValue'));
    }

    // 2. GELİR RAPORU
    public function revenueReport(Request $request)
    {
        $period = $request->get('period', 'month');

        $query = Order::where('order_status', '!=', 0);

        if ($period == 'today') {
            $query->whereDate('created_at', today());
        } elseif ($period == 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period == 'month') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        } elseif ($period == 'year') {
            $query->whereYear('created_at', now()->year);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        $totalRevenue = $orders->sum('total_amount');
        $totalOrders = $orders->count();
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        $dailyRevenue = Order::selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as orders')
            ->where('order_status', '!=', 0)
            ->whereBetween('created_at', [now()->subDays(30), now()])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topProducts = OrderProduct::selectRaw('product_id, product_name, SUM(qty) as qty, SUM(unit_price * qty) as revenue')
            ->whereIn('order_id', $orders->pluck('id'))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('revenue')
            ->take(10)
            ->get();

        return view('admin.analytics.revenue', compact('orders', 'totalRevenue', 'totalOrders', 'avgOrderValue', 'dailyRevenue', 'topProducts', 'period'));
    }

    // 3. KARGO RAPORU
    public function cargoReport()
    {
        $orders = Order::where('order_status', '!=', 0)
            ->with('orderProducts.product')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $byStatus = Order::selectRaw('order_status, COUNT(*) as count, SUM(total_amount) as revenue')
            ->where('order_status', '!=', 0)
            ->groupBy('order_status')
            ->get();

        return view('admin.analytics.cargo', compact('orders', 'byStatus'));
    }

    // 4. KUPON KULLANIMI
    public function couponReport()
    {
        $coupons = Coupon::with(['orders' => function($q) {
            $q->where('order_status', '!=', 0);
        }])
        ->get()
        ->map(function($coupon) {
            $coupon->used_count = $coupon->orders->count();
            $coupon->total_discount = $coupon->orders->sum('coupon_coast');
            return $coupon;
        })
        ->sortByDesc('used_count');

        $totalUsed = $coupons->sum('used_count');
        $totalDiscount = $coupons->sum('total_discount');

        return view('admin.analytics.coupon', compact('coupons', 'totalUsed', 'totalDiscount'));
    }

    // 5. KULLANICI ANALITIĞI
    public function userAnalytics()
    {
        $totalUsers = User::count();
        $newUsersToday = User::whereDate('created_at', today())->count();
        $newUsersThisMonth = User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        $usersByMonth = User::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->take(12)
            ->get();

        $activeUsers = User::whereHas('orders', function($q) {
            $q->where('order_status', '!=', 0)->where('created_at', '>=', now()->subDays(30));
        })->count();

        return view('admin.analytics.user', compact('totalUsers', 'newUsersToday', 'newUsersThisMonth', 'usersByMonth', 'activeUsers'));
    }

    // 6. ABANDONED CART
    public function abandonedCart()
    {
        $abandonedCarts = ShoppingCart::with('product', 'user')
            ->whereHas('user')
            ->where('created_at', '<=', now()->subHours(24))
            ->get()
            ->groupBy('user_id')
            ->map(function($group) {
                $first = $group->first();
                $first->item_count = $group->count();
                $first->total_value = $group->sum(function($item) {
                    return ($item->product->offer_price ?? $item->product->price) * $item->qty;
                });
                $first->last_activity = $group->max('created_at');
                return $first;
            })
            ->values()
            ->sortByDesc('last_activity')
            ->take(50);

        $totalAbandoned = $abandonedCarts->sum('total_value');
        $totalCount = $abandonedCarts->count();

        return view('admin.analytics.abandoned_cart', compact('abandonedCarts', 'totalAbandoned', 'totalCount'));
    }

    // 7. ARAMA ANALITIĞI
    public function searchAnalytics()
    {
        return view('admin.analytics.search');
    }

    // 8. FLASH SALE PERFORMANS
    public function flashSaleReport()
    {
        $flashSales = FlashSale::with(['products.product.category', 'products.product.brand'])
            ->where('status', 1)
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(function($sale) {
                $sale->total_sold = $sale->products->sum('qty');
                $sale->total_revenue = $sale->products->sum(function($p) {
                    return $p->qty * ($p->product->offer_price ?? $p->product->price);
                });
                return $sale;
            });

        return view('admin.analytics.flash_sale', compact('flashSales'));
    }

    // 9. SATICI PERFORMANS
    public function sellerPerformance()
    {
        $sellers = Vendor::with(['products', 'orders' => function($q) {
            $q->where('order_status', '!=', 0);
        }])
        ->get()
        ->map(function($seller) {
            $seller->total_orders = $seller->orders->count();
            $seller->total_revenue = $seller->orders->sum('total_amount');
            $seller->product_count = $seller->products->count();
            return $seller;
        })
        ->sortByDesc('total_revenue');

        return view('admin.analytics.seller', compact('sellers'));
    }

    // 10. İADE/ŞIKAYET RAPORU
    public function returnReport()
    {
        $reports = ProductReport::with('product', 'user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $byReason = ProductReport::selectRaw('reason, COUNT(*) as count')
            ->groupBy('reason')
            ->orderByDesc('count')
            ->get();

        return view('admin.analytics.returns', compact('reports', 'byReason'));
    }
}