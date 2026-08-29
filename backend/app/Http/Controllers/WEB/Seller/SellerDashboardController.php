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

        $weeklyEarning = 0;
        $weeklyProductSale = 0;
        foreach ($weeklyOrders->where('order_status', 3) as $weeklyOrder) {
            $orderProducts = $weeklyOrder->orderProducts->where('seller_id', $sellerId);
            foreach ($orderProducts as $orderProduct) {
                $price = $orderProduct->seller_net_amount > 0 ? $orderProduct->seller_net_amount : ($orderProduct->unit_price * $orderProduct->qty);
                $weeklyEarning += $price;
                $weeklyProductSale += $orderProduct->qty;
            }
        }

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

        $reviews = ProductReview::where('product_vendor_id', $seller->id)->get();

        $totalWithdraw = SellerWithdraw::where('seller_id',$seller->id)->where('status',1)->sum('withdraw_amount');
        $totalPendingWithdraw = SellerWithdraw::where('seller_id',$seller->id)->where('status',0)->sum('withdraw_amount');
        $totalDeclinedOrder = Order::forSeller($sellerId)->where('order_status', 4)->count();

        return view('seller.dashboard', compact(
            'todayOrders', 'totalOrders', 'setting',
            'monthlyOrders', 'yearlyOrders',
            'weeklyOrders', 'weeklyEarning', 'weeklyProductSale',
            'products', 'publishedProductCount', 'draftProductCount', 'stockoutProductCount',
            'topProducts', 'reviews', 'seller',
            'totalWithdraw', 'totalPendingWithdraw', 'totalDeclinedOrder'
        ));
    }
}
