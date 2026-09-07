<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReport;
use App\Models\ProductReview;
use App\Models\SellerWithdraw;
use App\Services\CommissionService;
use Carbon\Carbon;
use Auth;

class SellerDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        $user = Auth::guard('api')->user();
        $seller = $user->seller;
        $sellerId = $seller->id;
        $commissionService = app(CommissionService::class);

        $today = $commissionService->sellerPeriodStats(
            $sellerId,
            Carbon::now()->startOfDay(),
            Carbon::now()->endOfDay()
        );
        $month = $commissionService->sellerPeriodStats(
            $sellerId,
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );
        $year = $commissionService->sellerPeriodStats(
            $sellerId,
            Carbon::now()->startOfYear(),
            Carbon::now()->endOfYear()
        );
        $all = $commissionService->sellerPeriodStats($sellerId);

        $todayOrders = Order::with(['user', 'orderProducts'])
            ->forSeller($sellerId)
            ->paidRealized()
            ->orderBy('id', 'desc')
            ->whereDay('created_at', now()->day)
            ->get();

        $products = Product::where('vendor_id', $seller->id)->get();
        $reviews = ProductReview::where('product_vendor_id', $seller->id)->get();
        $reports = ProductReport::where('seller_id', $seller->id)->get();

        $totalWithdraw = SellerWithdraw::where('seller_id', $seller->id)->where('status', 1)->sum('withdraw_amount');
        $totalPendingWithdraw = SellerWithdraw::where('seller_id', $seller->id)->where('status', 0)->sum('withdraw_amount');

        return response()->json([
            'todayTotalOrder' => $today['order_count'],
            'todayOrders' => $todayOrders,
            'todayEarning' => $today['earned'],
            'todayPendingEarning' => $today['pending_earning'],
            'todayProductSale' => $today['sold_qty'],
            'monthlyTotalOrder' => $month['order_count'],
            'thisMonthEarning' => $month['earned'],
            'thisMonthPendingEarning' => $month['pending_earning'],
            'thisMonthProductSale' => $month['sold_qty'],
            'yearlyTotalOrder' => $year['order_count'],
            'thisYearEarning' => $year['earned'],
            'thisYearPendingEarning' => $year['pending_earning'],
            'thisYearProductSale' => $year['sold_qty'],
            'totalOrder' => $all['order_count'],
            'totalPendingOrder' => 0,
            'totalDeclinedOrder' => $all['cancelled_order_count'],
            'totalCompleteOrder' => Order::forSeller($sellerId)->paidCompleted()->count(),
            'totalEarning' => $all['earned'],
            'totalPendingEarning' => $all['pending_earning'],
            'totalProductSale' => $all['sold_qty'],
            'total_product' => $products->count(),
            'reviews' => $reviews->count(),
            'reports' => $reports->count(),
            'seller' => $seller,
            'totalWithdraw' => $totalWithdraw,
            'totalPendingWithdraw' => $totalPendingWithdraw,
            'earning_note' => 'Kazanç yalnızca İyzico/admin onayı sonrası sayılır. Bekleyen tutar henüz kazanç değildir.',
        ]);
    }
}
