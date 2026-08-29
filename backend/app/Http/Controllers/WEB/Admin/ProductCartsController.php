<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ShoppingCart;
use App\Models\ShoppingCartVariant;
use Carbon\Carbon;

class ProductCartsController extends Controller
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
        
        // Get shopping carts with date filter
        $shoppingCarts = ShoppingCart::whereBetween('created_at', [$startDate, $endDate])->get();
        $shoppingCartVariants = ShoppingCartVariant::whereBetween('created_at', [$startDate, $endDate])->get();
        
        // Calculate statistics
        $cartStats = $this->getCartStats($products, $shoppingCarts, $shoppingCartVariants, $startDate, $endDate);
        
        // Get products added to cart (most added first)
        $mostAddedProducts = $this->getMostAddedProducts($products, $shoppingCarts, $shoppingCartVariants, $startDate, $endDate);
        
        return view('admin.product_carts', compact(
            'products', 'shoppingCarts', 'shoppingCartVariants',
            'cartStats', 'mostAddedProducts',
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
    
    private function getCartStats($products, $shoppingCarts, $shoppingCartVariants, $startDate, $endDate)
    {
        $filteredProductIds = $products->pluck('id')->toArray();
        $carts = $shoppingCarts->whereIn('product_id', $filteredProductIds);
        
        // Total items in carts
        $totalItems = $carts->sum('qty');
        
        // Total unique carts (users)
        $uniqueCarts = $carts->groupBy(function ($cart) {
            return $cart->user_id ?? $cart->session_id ?? $cart->ip_address;
        })->count();
        
        // Total unique products added
        $uniqueProducts = $carts->pluck('product_id')->unique()->count();
        
        // Cart items with variants
        $variantCarts = $shoppingCartVariants->whereIn('product_id', $filteredProductIds);
        $variantItems = $variantCarts->sum('qty');
        
        return [
            'totalItems' => $totalItems,
            'uniqueCarts' => $uniqueCarts,
            'uniqueProducts' => $uniqueProducts,
            'variantItems' => $variantItems,
            'totalCarts' => $carts->count()
        ];
    }
    
    private function getMostAddedProducts($products, $shoppingCarts, $shoppingCartVariants, $startDate, $endDate)
    {
        $filteredProductIds = $products->pluck('id')->toArray();
        $carts = $shoppingCarts->whereIn('product_id', $filteredProductIds);
        
        $productStats = [];
        
        foreach ($products as $product) {
            $productCarts = $carts->where('product_id', $product->id);
            $productVariantCarts = $shoppingCartVariants->where('product_id', $product->id);
            
            // Count unique users who added this product
            $uniqueUsers = $productCarts->groupBy(function ($cart) {
                return $cart->user_id ?? $cart->session_id ?? $cart->ip_address;
            })->count();
            
            // Total quantity added
            $totalQty = $productCarts->sum('qty');
            $variantQty = $productVariantCarts->sum('qty');
            
            $productStats[] = [
                'product' => $product,
                'unique_users' => $uniqueUsers,
                'total_qty' => $totalQty + $variantQty,
                'cart_count' => $productCarts->count()
            ];
        }
        
        // Sort by unique users (most added first)
        usort($productStats, function ($a, $b) {
            return $b['unique_users'] - $a['unique_users'];
        });
        
        return $productStats;
    }
}