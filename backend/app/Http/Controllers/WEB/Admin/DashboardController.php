<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Product;
use App\Models\ProductReport;
use App\Models\ProductReview;
use App\Models\Vendor;
use App\Models\Subscriber;
use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductViewSession;
use App\Models\ShoppingCart;
use App\Models\SecondHandListing;
use App\Support\AdminReportPeriod;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function dashobard(Request $request)
    {
        $setting = Setting::first();

        extract(AdminReportPeriod::resolve($request));

        // --- Filtrelenmiş siparişler ---
        $filteredOrders = Order::whereBetween('created_at', [$dateFrom, $dateTo])->get();

        // Sipariş sayıları
        $filteredTotal = $filteredOrders->count();
        $filteredPending = $filteredOrders->where('order_status', 0)->count();
        $filteredProgress = $filteredOrders->where('order_status', 1)->count();
        $filteredDelivered = $filteredOrders->where('order_status', 2)->count();
        $filteredComplete = $filteredOrders->where('order_status', 3)->count();
        $filteredDeclined = $filteredOrders->where('order_status', 4)->count();

        // Gelir (yalnızca ödenmiş siparişler)
        $filteredPaidOrders = $filteredOrders->where('payment_status', 1);
        $filteredEarning = round($filteredPaidOrders->sum('total_amount'), 2);
        $filteredPendingEarning = round($filteredOrders->where('payment_status', 0)->sum('total_amount'), 2);
        $filteredProductSale = $filteredPaidOrders->where('order_status', 3)->sum('product_qty');

        // --- Komisyon (filtrelenmiş, yalnızca ödenmiş siparişler) ---
        $commissionStats = DB::table('commission_ledger')
            ->join('orders', 'orders.id', '=', 'commission_ledger.order_id')
            ->where('orders.payment_status', 1)
            ->whereBetween('commission_ledger.created_at', [$dateFrom, $dateTo])
            ->selectRaw("
                COALESCE(SUM(commission_ledger.commission_amount), 0) as total_commission,
                COALESCE(SUM(CASE WHEN commission_ledger.status = 'pending' THEN commission_ledger.commission_amount ELSE 0 END), 0) as pending_commission,
                COALESCE(SUM(CASE WHEN commission_ledger.status = 'settled' THEN commission_ledger.commission_amount ELSE 0 END), 0) as settled_commission,
                COALESCE(SUM(CASE WHEN commission_ledger.status = 'refunded' THEN commission_ledger.commission_amount ELSE 0 END), 0) as refunded_commission,
                COALESCE(SUM(commission_ledger.gross_amount), 0) as total_gross,
                COALESCE(SUM(commission_ledger.seller_net_amount), 0) as total_seller_net,
                COALESCE(SUM(CASE WHEN commission_ledger.status = 'settled' THEN commission_ledger.seller_net_amount ELSE 0 END), 0) as settled_seller_net
            ")->first();

        // --- Tüm zamanlar toplam (her zaman göster) ---
        $allTimeStats = DB::table('commission_ledger')
            ->join('orders', 'orders.id', '=', 'commission_ledger.order_id')
            ->where('orders.payment_status', 1)
            ->selectRaw("
                COALESCE(SUM(commission_ledger.commission_amount), 0) as total_commission,
                COALESCE(SUM(commission_ledger.seller_net_amount), 0) as total_seller_net,
                COALESCE(SUM(CASE WHEN commission_ledger.status = 'settled' THEN commission_ledger.commission_amount ELSE 0 END), 0) as settled_commission,
                COALESCE(SUM(CASE WHEN commission_ledger.status = 'settled' THEN commission_ledger.seller_net_amount ELSE 0 END), 0) as settled_seller_net
            ")->first();
        $allTimeEarning = round(Order::where('payment_status', 1)->sum('total_amount'), 2);
        $allTimeTotalOrder = Order::count();
        $allTimePaidOrder = Order::where('payment_status', 1)->count();
        $allTimePendingOrder = Order::where('payment_status', 0)->count();

        // --- En Çok Satan Ürünler (filtrelenmiş) ---
        $topProducts = DB::table('order_products')
            ->join('products', 'products.id', '=', 'order_products.product_id')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
            ->where('orders.payment_status', 1)
            ->select(
                'products.id', 'products.name', 'products.thumb_image',
                DB::raw('SUM(order_products.qty) as total_sold'),
                DB::raw('SUM(order_products.qty * order_products.unit_price) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.thumb_image')
            ->orderByDesc('total_sold')
            ->limit(5)->get();

        // --- En Çok Satan Satıcılar (filtrelenmiş) ---
        $topSellers = DB::table('commission_ledger')
            ->join('orders', 'orders.id', '=', 'commission_ledger.order_id')
            ->join('vendors', 'vendors.id', '=', 'commission_ledger.seller_id')
            ->where('orders.payment_status', 1)
            ->whereBetween('commission_ledger.created_at', [$dateFrom, $dateTo])
            ->select(
                'vendors.id', 'vendors.shop_name',
                DB::raw('SUM(commission_ledger.gross_amount) as total_sales'),
                DB::raw('SUM(commission_ledger.commission_amount) as total_commission'),
                DB::raw('SUM(commission_ledger.seller_net_amount) as total_net'),
                DB::raw('COUNT(DISTINCT commission_ledger.order_id) as order_count')
            )
            ->groupBy('vendors.id', 'vendors.shop_name')
            ->orderByDesc('total_sales')
            ->limit(5)->get();

        // --- Grafik: dönem içi günlük satış + komisyon ---
        $chartLabels = [];
        $chartSales = [];
        $chartCommissions = [];
        $chartStart = $dateFrom->copy();
        $chartEnd = min($dateTo->copy(), now());
        $dayCount = $chartStart->diffInDays($chartEnd) + 1;
        $dayCount = min($dayCount, 90); // max 90 gün göster

        for ($i = 0; $i < $dayCount; $i++) {
            $date = $chartStart->copy()->addDays($i)->format('Y-m-d');
            $chartLabels[] = Carbon::parse($date)->format('d.m');
            $chartSales[] = round(
                Order::whereDate('created_at', $date)->where('payment_status', 1)->sum('total_amount'),
                2
            );
            $chartCommissions[] = round(
                DB::table('commission_ledger')
                    ->join('orders', 'orders.id', '=', 'commission_ledger.order_id')
                    ->where('orders.payment_status', 1)
                    ->whereDate('commission_ledger.created_at', $date)
                    ->sum('commission_ledger.commission_amount'),
                2
            );
        }

        // --- Son siparişler (bugünün) ---
        $todayOrders = Order::with('user', 'orderProducts', 'orderAddress')
            ->orderBy('id', 'desc')
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->get();

        // --- Genel sayılar ---
        $totalProduct = Product::count();
        $reviews = ProductReview::count();
        $reports = ProductReport::count();
        $users = User::count();
        $sellers = Vendor::count();
        $subscribers = Subscriber::where('is_verified', 1)->count();
        $categories = Category::count();
        $brands = Brand::count();
        $todayProductViews = ProductViewSession::whereDate('created_at', now()->toDateString())->count();
        $todayCartAdds = ShoppingCart::whereDate('created_at', now()->toDateString())->count();
        $mobileActiveUsers = 0;
        if (Schema::hasColumn('users', 'last_seen_at')) {
            $q = User::query()->where('last_seen_at', '>=', now()->subMinutes(15));
            if (Schema::hasColumn('users', 'last_seen_platform')) {
                $q->where('last_seen_platform', 'mobile');
            }
            $mobileActiveUsers = $q->count();
        }
        $secondHandActive = SecondHandListing::where('status', SecondHandListing::STATUS_ACTIVE)->count();
        $secondHandPending = SecondHandListing::where('status', SecondHandListing::STATUS_PENDING)->count();

        return view('admin.dashboard', compact(
            'setting', 'period', 'periodLabel', 'dateFrom', 'dateTo',
            'filteredTotal', 'filteredPending', 'filteredProgress',
            'filteredDelivered', 'filteredComplete', 'filteredDeclined',
            'filteredEarning', 'filteredPendingEarning', 'filteredProductSale',
            'commissionStats',
            'allTimeStats', 'allTimeEarning', 'allTimeTotalOrder', 'allTimePaidOrder', 'allTimePendingOrder',
            'topProducts', 'topSellers',
            'chartLabels', 'chartSales', 'chartCommissions',
            'todayOrders',
            'totalProduct', 'reviews', 'reports',
            'users', 'sellers', 'subscribers', 'categories', 'brands',
            'todayProductViews', 'todayCartAdds', 'mobileActiveUsers',
            'secondHandActive', 'secondHandPending'
        ));
    }
}
