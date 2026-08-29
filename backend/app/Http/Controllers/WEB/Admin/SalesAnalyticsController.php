<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesAnalyticsController extends Controller
{
    public function index()
    {
        $filter = request()->get('filter', 'all');
        $dateRange = $this->getDateRange($filter);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        // Only real paid orders — avoid counting unpaid drafts as sales.
        $ordersQuery = Order::query()
            ->where('payment_status', 1)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $orders = (clone $ordersQuery)->with('orderProducts')->get();
        $orderIds = $orders->pluck('id');

        $orderProducts = OrderProduct::query()
            ->whereIn('order_id', $orderIds)
            ->get();

        $salesStats = $this->getSalesStats($orders, $orderProducts);
        $bestSellingProducts = $this->getBestSellingProducts($orderProducts);
        $salesByCategory = $this->getSalesByCategory($orderProducts);
        $dailySales = $this->getDailySales($orders);
        $conversionRates = $this->getConversionRates($bestSellingProducts);

        // Keep compact vars for existing blade.
        $orderAmounts = collect();
        $products = collect();

        return view('admin.sales_analytics', compact(
            'orders',
            'orderProducts',
            'orderAmounts',
            'products',
            'salesStats',
            'bestSellingProducts',
            'conversionRates',
            'salesByCategory',
            'dailySales',
            'filter',
            'startDate',
            'endDate'
        ));
    }

    private function getDateRange($filter): array
    {
        $now = Carbon::now();

        return match ($filter) {
            'daily' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'weekly' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'monthly' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'yearly' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            default => [
                'start' => Carbon::create(2000, 1, 1)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
        };
    }

    private function getSalesStats($orders, $orderProducts): array
    {
        $totalOrders = $orders->count();
        $completedOrders = $orders->where('order_status', 3)->count();
        $pendingOrders = $orders->whereIn('order_status', [0, 1])->count();
        $cancelledOrders = $orders->whereIn('order_status', [4])->count();

        $totalRevenue = (float) $orders->sum('total_amount');
        $productRevenue = (float) $orderProducts->sum(fn ($op) => (float) $op->unit_price * (int) $op->qty);
        $totalCommission = (float) $orderProducts->sum('commission_amount');
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;
        $totalItemsSold = (int) $orderProducts->sum('qty');

        return [
            'totalOrders' => $totalOrders,
            'completedOrders' => $completedOrders,
            'pendingOrders' => $pendingOrders,
            'cancelledOrders' => $cancelledOrders,
            'totalRevenue' => $totalRevenue,
            'productRevenue' => $productRevenue,
            'totalCommission' => $totalCommission,
            'avgOrderValue' => $avgOrderValue,
            'totalItemsSold' => $totalItemsSold,
        ];
    }

    private function getBestSellingProducts($orderProducts): array
    {
        if ($orderProducts->isEmpty()) {
            return [];
        }

        $grouped = $orderProducts->groupBy('product_id');
        $productIds = $grouped->keys()->filter()->values();
        $products = Product::with('category', 'seller', 'brand')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $productSales = [];
        foreach ($grouped as $productId => $rows) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }

            $productSales[] = [
                'product' => $product,
                'total_qty' => (int) $rows->sum('qty'),
                'total_revenue' => (float) $rows->sum(fn ($op) => (float) $op->unit_price * (int) $op->qty),
                'order_count' => $rows->count(),
            ];
        }

        usort($productSales, fn ($a, $b) => $b['total_qty'] <=> $a['total_qty']);

        return array_slice($productSales, 0, 20);
    }

    private function getConversionRates(array $bestSellingProducts): array
    {
        // Only products that actually sold — no zero-sale "analytics".
        $conversionData = [];
        foreach ($bestSellingProducts as $row) {
            $product = $row['product'];
            $views = 0;
            if (\Illuminate\Support\Facades\Schema::hasTable('product_views')) {
                $views = (int) (DB::table('product_views')->where('product_id', $product->id)->value('view_count') ?? 0);
            }
            $purchases = (int) $row['total_qty'];
            $conversionData[] = [
                'product' => $product,
                'views' => $views,
                'purchases' => $purchases,
                'conversion_rate' => $views > 0 ? round(($purchases / $views) * 100, 2) : 0,
            ];
        }

        usort($conversionData, fn ($a, $b) => $b['conversion_rate'] <=> $a['conversion_rate']);

        return $conversionData;
    }

    private function getSalesByCategory($orderProducts): array
    {
        if ($orderProducts->isEmpty()) {
            return [];
        }

        $productIds = $orderProducts->pluck('product_id')->unique()->filter()->values();
        $products = Product::with('category')->whereIn('id', $productIds)->get()->keyBy('id');

        $categorySales = [];
        foreach ($orderProducts as $op) {
            $product = $products->get($op->product_id);
            if (! $product || ! $product->category) {
                continue;
            }

            $cid = $product->category->id;
            if (! isset($categorySales[$cid])) {
                $categorySales[$cid] = [
                    'category' => $product->category,
                    'total_qty' => 0,
                    'total_revenue' => 0,
                    'product_count' => 0,
                ];
            }

            $categorySales[$cid]['total_qty'] += (int) $op->qty;
            $categorySales[$cid]['total_revenue'] += (float) $op->unit_price * (int) $op->qty;
        }

        foreach ($categorySales as &$row) {
            $row['product_count'] = $orderProducts
                ->filter(fn ($op) => optional($products->get($op->product_id))->category_id === $row['category']->id)
                ->pluck('product_id')
                ->unique()
                ->count();
        }
        unset($row);

        usort($categorySales, fn ($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

        return $categorySales;
    }

    private function getDailySales($orders): array
    {
        $dailySales = [];
        $ordersByDate = $orders->groupBy(fn ($order) => optional($order->created_at)->format('Y-m-d') ?: 'unknown');

        foreach ($ordersByDate as $date => $dayOrders) {
            $dailySales[] = [
                'date' => $date,
                'order_count' => $dayOrders->count(),
                'revenue' => (float) $dayOrders->sum('total_amount'),
                'items_sold' => (int) $dayOrders->flatMap(fn ($order) => $order->orderProducts)->sum('qty'),
            ];
        }

        return $dailySales;
    }
}
