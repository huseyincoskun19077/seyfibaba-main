<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\ProductViewSession;
use Carbon\Carbon;

class ProductViewsController extends Controller
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
        
        // Get view sessions with date filter
        $viewSessions = ProductViewSession::whereBetween('created_at', [$startDate, $endDate])->get();
        
        // Get product views
        $productViews = ProductView::all();
        
        // Calculate session statistics
        $sessionStats = $this->getSessionStats($products, $viewSessions, $startDate, $endDate);
        
        // Get most viewed products
        $mostViewedProducts = $this->getMostViewedProducts($products, $viewSessions, $productViews, $startDate, $endDate);
        
        // Get all sessions for detailed table
        $sessionsByProduct = $viewSessions->groupBy('product_id');
        
        return view('admin.product_views', compact(
            'products', 'productViews', 'viewSessions',
            'sessionStats', 'mostViewedProducts', 'sessionsByProduct',
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
            'engagementRate' => $uniqueViewers > 0 ? round(($engagedCount / $uniqueViewers) * 100, 1) : 0,
            'totalViews' => $sessions->count()
        ];
    }
    
    private function getMostViewedProducts($products, $viewSessions, $productViews, $startDate, $endDate)
    {
        $filteredProductIds = $products->pluck('id')->toArray();
        $sessions = $viewSessions->whereIn('product_id', $filteredProductIds);
        
        $productStats = [];
        
        foreach ($products as $product) {
            $productSessions = $sessions->where('product_id', $product->id);
            $productView = $productViews->firstWhere('product_id', $product->id);
            
            // Count unique viewers for this product
            $uniqueViewers = $productSessions->groupBy(function ($session) {
                return $session->session_id ?? $session->ip_address;
            })->count();
            
            // Total duration
            $totalDuration = $productSessions->sum('duration');
            
            // Engaged count
            $engagedCount = $productSessions->where('engaged', true)->count();
            
            // Get total view count from product_views table
            $totalViewCount = $productView ? $productView->view_count : 0;
            
            $productStats[] = [
                'product' => $product,
                'unique_viewers' => $uniqueViewers,
                'total_duration' => $totalDuration,
                'avg_duration' => $uniqueViewers > 0 ? round($totalDuration / $uniqueViewers) : 0,
                'engaged_count' => $engagedCount,
                'engagement_rate' => $uniqueViewers > 0 ? round(($engagedCount / $uniqueViewers) * 100, 1) : 0,
                'total_views' => $totalViewCount,
                'session_count' => $productSessions->count()
            ];
        }
        
        // Sort by unique viewers (most viewed first)
        usort($productStats, function ($a, $b) {
            return $b['unique_viewers'] - $a['unique_viewers'];
        });
        
        return $productStats;
    }
}