<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Product;
use App\Models\ProductReport;
use App\Models\ProductReview;
use App\Models\OrderProduct;
use App\Models\SellerWithdraw;
use Carbon\Carbon;
use Auth;
class SellerDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(){
        $user = Auth::guard('api')->user();
        $seller = $user->seller;
        $sellerId = $seller->id;

        $todayOrders = Order::with(['user', 'orderProducts'])
            ->forSeller($sellerId)
            ->paidRealized()
            ->orderBy('id','desc')
            ->whereDay('created_at', now()->day)
            ->get();

        $todayTotalOrder = $todayOrders->count();

        $todayEarning = 0;
        $todayProductSale = 0;
        foreach ($todayOrders->where('order_status', 3) as $todayOrder) {
            $orderProducts = $todayOrder->orderProducts->where('seller_id', $sellerId);
            foreach ($orderProducts as $orderProduct) {
                $price = $orderProduct->seller_net_amount > 0 ? $orderProduct->seller_net_amount : ($orderProduct->unit_price * $orderProduct->qty);
                $todayEarning = $todayEarning + $price;
                $todayProductSale = $todayProductSale + $orderProduct->qty;
            }
        }

        $todayPendingEarning = 0;

        $totalOrders = Order::with(['user', 'orderProducts'])
            ->forSeller($sellerId)
            ->paidRealized()
            ->orderBy('id','desc')
            ->get();

        $totalOrder = $totalOrders->count();
        $totalPendingOrder = 0;
        $totalDeclinedOrder = Order::forSeller($sellerId)->where('order_status', 4)->count();
        $totalCompleteOrder = $totalOrders->where('order_status', 3)->count();

        $totalEarning = 0;
        $totalProductSale = 0;
        foreach ($totalOrders->where('order_status', 3) as $totalOrderItem) {
            $orderProducts = $totalOrderItem->orderProducts->where('seller_id', $sellerId);
            foreach ($orderProducts as $orderProduct) {
                $price = $orderProduct->seller_net_amount > 0 ? $orderProduct->seller_net_amount : ($orderProduct->unit_price * $orderProduct->qty);
                $totalEarning = $totalEarning + $price;
                $totalProductSale = $totalProductSale + $orderProduct->qty;
            }
        }

        $monthlyOrders = Order::with(['user', 'orderProducts'])
            ->forSeller($sellerId)
            ->paidRealized()
            ->orderBy('id','desc')
            ->whereMonth('created_at', now()->month)
            ->get();

        $monthlyTotalOrder = $monthlyOrders->count();
        $thisMonthEarning = 0;
        $thisMonthProductSale = 0;
        foreach ($monthlyOrders->where('order_status', 3) as $monthlyOrder) {
            $orderProducts = $monthlyOrder->orderProducts->where('seller_id', $sellerId);
            foreach ($orderProducts as $orderProduct) {
                $price = $orderProduct->seller_net_amount > 0 ? $orderProduct->seller_net_amount : ($orderProduct->unit_price * $orderProduct->qty);
                $thisMonthEarning = $thisMonthEarning + $price;
                $thisMonthProductSale = $thisMonthProductSale + $orderProduct->qty;
            }
        }

        $yearlyOrders = Order::with(['user', 'orderProducts'])
            ->forSeller($sellerId)
            ->paidRealized()
            ->orderBy('id','desc')
            ->whereYear('created_at', now()->year)
            ->get();

        $yearlyTotalOrder = $yearlyOrders->count();
        $thisYearEarning = 0;
        $thisYearProductSale = 0;
        foreach ($yearlyOrders->where('order_status', 3) as $yearlyOrder) {
            $orderProducts = $yearlyOrder->orderProducts->where('seller_id', $sellerId);
            foreach ($orderProducts as $orderProduct) {
                $price = $orderProduct->seller_net_amount > 0 ? $orderProduct->seller_net_amount : ($orderProduct->unit_price * $orderProduct->qty);
                $thisYearEarning = $thisYearEarning + $price;
                $thisYearProductSale = $thisYearProductSale + $orderProduct->qty;
            }
        }

        $setting = Setting::first();
        $products = Product::where('vendor_id', $seller->id)->get();

        $reviews = ProductReview::where('product_vendor_id', $seller->id)->get();
        $reports = ProductReport::where('seller_id', $seller->id)->get();

        $totalWithdraw = SellerWithdraw::where('seller_id',$seller->id)->where('status',1)->sum('withdraw_amount');
        $totalPendingWithdraw = SellerWithdraw::where('seller_id',$seller->id)->where('status',0)->sum('withdraw_amount');

        return response()->json([
            'todayTotalOrder' => $todayTotalOrder,
            'todayOrders' => $todayOrders,
            'todayEarning' => $todayEarning,
            'todayPendingEarning' => $todayPendingEarning,
            'todayProductSale' => $todayProductSale,
            'monthlyTotalOrder' => $monthlyTotalOrder,
            'thisMonthEarning' => $thisMonthEarning,
            'thisMonthProductSale' => $thisMonthProductSale,
            'yearlyTotalOrder' => $yearlyTotalOrder,
            'thisYearEarning' => $thisYearEarning,
            'thisYearProductSale' => $thisYearProductSale,
            'totalOrder' => $totalOrder,
            'totalPendingOrder' => $totalPendingOrder,
            'totalDeclinedOrder' => $totalDeclinedOrder,
            'totalCompleteOrder' => $totalCompleteOrder,
            'totalEarning' => $totalEarning,
            'totalProductSale' => $totalProductSale,
            'total_product' => $products->count(),
            'reviews' => $reviews->count(),
            'reports' => $reports->count(),
            'seller' => $seller,
            'totalWithdraw' => $totalWithdraw,
            'totalPendingWithdraw' => $totalPendingWithdraw
        ]);
    }
}
