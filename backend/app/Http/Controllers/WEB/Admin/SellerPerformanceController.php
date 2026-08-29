<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\OrderProduct;
use App\Models\Order;
use App\Models\SellerWithdraw;
use App\Models\CommissionLedger;
use Carbon\Carbon;

class SellerPerformanceController extends Controller
{
    public function index()
    {
        $filter = request()->get('filter', 'all');
        
        // Get date range
        $dateRange = $this->getDateRange($filter);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        
        // Get all vendors with relations
        $vendors = Vendor::with('user', 'products')->get();
        
        // Get orders and order products in date range
        $orderProducts = OrderProduct::whereBetween('created_at', [$startDate, $endDate])->get();
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])->get();
        
        // Get all product reviews
        $reviews = ProductReview::all();
        
        // Get commission ledgers
        $commissionLedgers = CommissionLedger::whereBetween('created_at', [$startDate, $endDate])->get();
        
        // Get withdrawals
        $withdrawals = SellerWithdraw::whereBetween('created_at', [$startDate, $endDate])->get();
        
        // Calculate vendor performance
        $vendorPerformance = $this->calculateVendorPerformance($vendors, $orderProducts, $orders, $reviews, $commissionLedgers, $withdrawals, $startDate, $endDate);
        
        // Sort by total score
        usort($vendorPerformance, function ($a, $b) {
            return $b['total_score'] - $a['total_score'];
        });
        
        return view('admin.seller_performance', compact(
            'vendors', 'vendorPerformance',
            'filter', 'startDate', 'endDate'
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
    
    private function calculateVendorPerformance($vendors, $orderProducts, $orders, $reviews, $commissionLedgers, $withdrawals, $startDate, $endDate)
    {
        $performance = [];
        
        foreach ($vendors as $vendor) {
            // Get vendor's order products
            $vendorOrderProducts = $orderProducts->where('seller_id', $vendor->id);
            
            // Get vendor's orders
            $vendorOrderIds = $vendorOrderProducts->pluck('order_id')->unique();
            $vendorOrders = $orders->whereIn('id', $vendorOrderIds);
            
            // Total sales
            $totalSales = $vendorOrderProducts->sum(function ($op) {
                return $op->unit_price * $op->qty;
            });
            
            // Total orders count
            $orderCount = $vendorOrderIds->count();
            
            // Products sold
            $productsSold = $vendorOrderProducts->sum('qty');
            
            // Get vendor products
            $vendorProducts = $vendor->products;
            $totalProducts = $vendorProducts->count();
            $activeProducts = $vendorProducts->where('status', 1)->count();
            $inactiveProducts = $vendorProducts->where('status', 0)->count();
            
            // Get reviews for vendor's products
            $vendorProductIds = $vendorProducts->pluck('id');
            $vendorReviews = $reviews->filter(function ($review) use ($vendorProductIds) {
                return $vendorProductIds->contains($review->product_id);
            });
            $avgRating = $vendorReviews->avg('rating') ?? 0;
            $totalReviews = $vendorReviews->count();
            
            // Pending orders
            $pendingOrders = $vendorOrders->where('order_status', 'pending')->count();
            $completedOrders = $vendorOrders->where('order_status', 'completed')->count();
            $cancelledOrders = $vendorOrders->whereIn('order_status', ['declined', 'cancelled'])->count();
            
            // Commission earned
            $vendorLedgers = $commissionLedgers->where('vendor_id', $vendor->id);
            $totalCommission = $vendorLedgers->sum('commission_amount');
            $sellerNetAmount = $vendorLedgers->sum('seller_net_amount');
            
            // Withdrawals
            $vendorWithdrawals = $withdrawals->where('vendor_id', $vendor->id);
            $totalWithdrawn = $vendorWithdrawals->where('status', 'approved')->sum('amount');
            $pendingWithdrawal = $vendorWithdrawals->where('status', 'pending')->sum('amount');
            
            // Calculate scores (out of 100)
            $salesScore = $this->calculateScore($totalSales, 1000000, 100); // 1M = 100 points
            $orderScore = $this->calculateScore($orderCount, 500, 100); // 500 orders = 100 points
            $ratingScore = $avgRating * 20; // 5 stars = 100 points
            $productScore = $this->calculateScore($activeProducts, 100, 100); // 100 products = 100 points
            $completionScore = $orderCount > 0 ? round(($completedOrders / $orderCount) * 100) : 0;
            
            $totalScore = round(($salesScore + $orderScore + $ratingScore + $productScore + $completionScore) / 5);
            
            $performance[] = [
                'vendor' => $vendor,
                'total_sales' => $totalSales,
                'order_count' => $orderCount,
                'products_sold' => $productsSold,
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'inactive_products' => $inactiveProducts,
                'avg_rating' => round($avgRating, 2),
                'total_reviews' => $totalReviews,
                'pending_orders' => $pendingOrders,
                'completed_orders' => $completedOrders,
                'cancelled_orders' => $cancelledOrders,
                'total_commission' => $totalCommission,
                'seller_net_amount' => $sellerNetAmount,
                'total_withdrawn' => $totalWithdrawn,
                'pending_withdrawal' => $pendingWithdrawal,
                'sales_score' => $salesScore,
                'order_score' => $orderScore,
                'rating_score' => $ratingScore,
                'product_score' => $productScore,
                'completion_score' => $completionScore,
                'total_score' => $totalScore
            ];
        }
        
        return $performance;
    }
    
    private function calculateScore($value, $maxValue, $maxScore)
    {
        if ($maxValue == 0) return 0;
        $score = ($value / $maxValue) * $maxScore;
        return min(round($score), $maxScore);
    }
}