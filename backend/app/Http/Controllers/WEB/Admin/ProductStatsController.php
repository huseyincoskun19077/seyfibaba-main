<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\ProductViewSession;
use App\Models\ShoppingCart;
use App\Models\OrderProduct;
use App\Models\Setting;
use App\Models\Wishlist;
use Carbon\Carbon;

class ProductStatsController extends Controller
{
    public function index()
    {
        $filter = request()->get('filter', 'all');
        
        // Calculate date range based on filter
        $dateRange = $this->getDateRange($filter);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        
        $products = Product::with('category', 'seller', 'brand')
            ->orderBy('id', 'desc')
            ->get();
        
        $productViews = ProductView::all();
        $shoppingCarts = ShoppingCart::all();
        $orderProducts = OrderProduct::all();
        $wishlists = Wishlist::all();
        $setting = Setting::first();
        
        // Get view sessions with date filter
        $viewSessions = ProductViewSession::whereBetween('created_at', [$startDate, $endDate])->get();
        
        // Calculate filtered statistics based on date range
        $filteredStats = $this->getFilteredStats($products, $productViews, $shoppingCarts, $orderProducts, $startDate, $endDate);
        
        // View sessions statistics
        $sessionStats = $this->getSessionStats($products, $viewSessions, $startDate, $endDate);
        
        // Total statistics (all time)
        $totalProducts = $products->count();
        $totalViews = $productViews->sum('view_count');
        $dailyViews = $productViews->sum('daily_views');
        $monthlyViews = $productViews->sum('monthly_views');
        $yearlyViews = $productViews->sum('yearly_views');
        $totalCartAdds = $productViews->sum('add_to_cart_count');
        $totalPurchases = $productViews->sum('purchase_count');
        $inCarts = $shoppingCarts->count();
        $inWishlists = $wishlists->count();
        
        return view('admin.product_stats', compact(
            'products', 'productViews', 'shoppingCarts', 'orderProducts', 'wishlists', 'setting',
            'totalProducts', 'totalViews', 'dailyViews', 'monthlyViews', 'yearlyViews', 'totalCartAdds', 'totalPurchases', 'inCarts', 'inWishlists',
            'filteredStats', 'filter', 'startDate', 'endDate',
            'sessionStats', 'viewSessions'
        ));
    }
    
    private function getDateRange($filter)
    {
        $now = Carbon::now();
        
        switch ($filter) {
            case 'daily':
                return [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay()
                ];
            case 'weekly':
                return [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfWeek()
                ];
            case 'monthly':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth()
                ];
            case 'yearly':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfYear()
                ];
            default: // 'all'
                return [
                    'start' => Carbon::create(2000, 1, 1),
                    'end' => $now->copy()->endOfYear()
                ];
        }
    }
    
    private function getFilteredStats($products, $productViews, $shoppingCarts, $orderProducts, $startDate, $endDate)
    {
        $filteredProductIds = $products->pluck('id')->toArray();
        
        // Filter shopping carts by date
        $filteredCarts = $shoppingCarts->whereBetween('created_at', [$startDate, $endDate]);
        
        // Filter order products by date
        $filteredOrders = $orderProducts->whereBetween('created_at', [$startDate, $endDate]);
        
        // Calculate views from ProductView last_*_at fields
        $viewCount = 0;
        $cartAddCount = 0;
        $purchaseCount = 0;
        
        foreach ($productViews as $pv) {
            if ($pv->last_viewed_at && $pv->last_viewed_at >= $startDate && $pv->last_viewed_at <= $endDate) {
                $viewCount += 1;
            }
            if ($pv->last_cart_at && $pv->last_cart_at >= $startDate && $pv->last_cart_at <= $endDate) {
                $cartAddCount += 1;
            }
            if ($pv->last_purchase_at && $pv->last_purchase_at >= $startDate && $pv->last_purchase_at <= $endDate) {
                $purchaseCount += 1;
            }
        }
        
        // Also check shopping carts and order products for more accurate counts
        $cartAddCount = $filteredCarts->whereIn('product_id', $filteredProductIds)->count();
        $purchaseCount = $filteredOrders->whereIn('product_id', $filteredProductIds)->sum('qty');
        
        return [
            'views' => $viewCount,
            'cartAdds' => $cartAddCount,
            'purchases' => $purchaseCount,
            'inCarts' => $filteredCarts->whereIn('product_id', $filteredProductIds)->count()
        ];
    }
    
    private function getSessionStats($products, $viewSessions, $startDate, $endDate)
    {
        $filteredProductIds = $products->pluck('id')->toArray();
        $sessions = $viewSessions->whereIn('product_id', $filteredProductIds);
        
        // Total unique viewers (by session_id or ip)
        $uniqueViewers = $sessions->groupBy(function ($session) {
            return $session->session_id ?? $session->ip_address;
        })->count();
        
        // Total time spent on products (in seconds)
        $totalDuration = $sessions->sum('duration');
        
        // Average time per view
        $avgDuration = $sessions->count() > 0 ? round($totalDuration / $sessions->count()) : 0;
        
        // Engaged users (stayed more than 10 seconds)
        $engagedCount = $sessions->where('engaged', true)->count();
        
        return [
            'uniqueViewers' => $uniqueViewers,
            'totalDuration' => $totalDuration,
            'avgDuration' => $avgDuration,
            'engagedCount' => $engagedCount,
            'engagementRate' => $uniqueViewers > 0 ? round(($engagedCount / $uniqueViewers) * 100, 1) : 0
        ];
    }
}