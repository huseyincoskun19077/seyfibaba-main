<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Vendor;
use App\Models\Subscriber;
use App\Models\User;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Brand;
use App\Models\OrderProduct;
use App\Models\SellerWithdraw;
use App\Services\CommissionService;
use Carbon\Carbon;
use Auth;
use App\Support\SellerLoginUrl;
class SellerDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index(){

        $user = Auth::guard('web')->user();
        $seller = $user->seller;

        if (! $seller) {
            Auth::guard('web')->logout();

            return redirect()->away(SellerLoginUrl::public())
                ->with([
                    'messege' => 'Satıcı hesabınız bulunamadı. Lütfen tekrar giriş yapın.',
                    'alert-type' => 'error',
                ]);
        }

        $sellerId = $seller->id;
        $commissionService = app(CommissionService::class);

        $statsToday = $commissionService->sellerPeriodStats(
            $sellerId,
            Carbon::now()->startOfDay(),
            Carbon::now()->endOfDay()
        );
        $statsWeek = $commissionService->sellerPeriodStats(
            $sellerId,
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        );
        $statsMonth = $commissionService->sellerPeriodStats(
            $sellerId,
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );
        $statsYear = $commissionService->sellerPeriodStats(
            $sellerId,
            Carbon::now()->startOfYear(),
            Carbon::now()->endOfYear()
        );
        $statsTotal = $commissionService->sellerPeriodStats($sellerId);

        $todayOrders = Order::with(['user', 'orderProducts'])
            ->forSeller($sellerId)
            ->paidRealized()
            ->orderBy('id','desc')
            ->whereDay('created_at', now()->day)
            ->get();

        $totalOrders = Order::with(['user', 'orderProducts'])
            ->forSeller($sellerId)
            ->paidRealized()
            ->orderBy('id','desc')
            ->get();

        $weeklyOrders = Order::with(['user', 'orderProducts'])
            ->forSeller($sellerId)
            ->paidRealized()
            ->orderBy('id','desc')
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->get();

        $weeklyEarning = $statsWeek['earned'];
        $weeklyPendingEarning = $statsWeek['pending_earning'];
        $weeklyProductSale = $statsWeek['sold_qty'];

        $monthlyOrders = Order::with(['user', 'orderProducts'])
            ->forSeller($sellerId)
            ->paidRealized()
            ->orderBy('id','desc')
            ->whereMonth('created_at', now()->month)
            ->get();

        $yearlyOrders = Order::with(['user', 'orderProducts'])
            ->forSeller($sellerId)
            ->paidRealized()
            ->orderBy('id','desc')
            ->whereYear('created_at', now()->year)
            ->get();

        $setting = Setting::first();
        if (! $setting) {
            $setting = new Setting(['currency_icon' => '₺']);
        }
        $products = Product::where('vendor_id', $seller->id)->get();
        $publishedProductCount = $products->where('status', 1)->count();
        $draftProductCount = $products->where('status', 0)->count();
        $stockoutProductCount = $products->where('qty', '<=', 0)->count();

        $topProducts = OrderProduct::with('product')
            ->where('seller_id', $seller->id)
            ->whereHas('order', function($q) {
                $q->paidCompleted()->whereMonth('created_at', now()->month);
            })
            ->selectRaw('product_id, SUM(qty) as total_qty, SUM(unit_price * qty) as total_revenue')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        $refundedByProduct = \App\Models\ReturnRequest::query()
            ->where('seller_id', $seller->id)
            ->where('status', \App\Models\ReturnRequest::STATUS_REFUNDED)
            ->whereBetween('refunded_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->with('orderProduct:id,product_id')
            ->get()
            ->groupBy(fn ($r) => optional($r->orderProduct)->product_id)
            ->map(fn ($rows) => (int) $rows->sum('qty'));

        foreach ($topProducts as $topProduct) {
            $deductQty = (int) ($refundedByProduct[$topProduct->product_id] ?? 0);
            $topProduct->total_qty = max(0, (int) $topProduct->total_qty - $deductQty);
        }
        $topProducts = $topProducts->filter(fn ($p) => (int) $p->total_qty > 0)->values();

        $reviews = ProductReview::where('product_vendor_id', $seller->id)->get();

        $totalWithdraw = SellerWithdraw::where('seller_id',$seller->id)->where('status',1)->sum('withdraw_amount');
        $totalPendingWithdraw = SellerWithdraw::where('seller_id',$seller->id)->where('status',0)->sum('withdraw_amount');
        $totalDeclinedOrder = $statsTotal['cancelled_order_count'];

        $todayEarning = $statsToday['earned'];
        $todayPendingEarning = $statsToday['pending_earning'];
        $todayProductSale = $statsToday['sold_qty'];
        $thisMonthEarning = $statsMonth['earned'];
        $thisMonthPendingEarning = $statsMonth['pending_earning'];
        $thisMonthProductSale = $statsMonth['sold_qty'];
        $thisYearEarning = $statsYear['earned'];
        $thisYearPendingEarning = $statsYear['pending_earning'];
        $thisYearProductSale = $statsYear['sold_qty'];
        $totalEarning = $statsTotal['earned'];
        $totalPendingEarning = $statsTotal['pending_earning'];
        $totalProductSale = $statsTotal['sold_qty'];

        return view('seller.dashboard', compact(
            'todayOrders', 'totalOrders', 'setting',
            'monthlyOrders', 'yearlyOrders',
            'weeklyOrders', 'weeklyEarning', 'weeklyPendingEarning', 'weeklyProductSale',
            'products', 'publishedProductCount', 'draftProductCount', 'stockoutProductCount',
            'topProducts', 'reviews', 'seller',
            'totalWithdraw', 'totalPendingWithdraw', 'totalDeclinedOrder',
            'todayEarning', 'todayPendingEarning', 'todayProductSale',
            'thisMonthEarning', 'thisMonthPendingEarning', 'thisMonthProductSale',
            'thisYearEarning', 'thisYearPendingEarning', 'thisYearProductSale',
            'totalEarning', 'totalPendingEarning', 'totalProductSale',
            'statsToday', 'statsWeek', 'statsMonth', 'statsYear', 'statsTotal'
        ));
    }
}
