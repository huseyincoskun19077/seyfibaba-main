<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivity;
use App\Models\Order;
use App\Models\ShoppingCart;
use App\Models\Wishlist;
use Carbon\Carbon;

class UserAnalyticsController extends Controller
{
    public function index()
    {
        $filter = request()->get('filter', 'all');
        
        // Calculate date range based on filter
        $dateRange = $this->getDateRange($filter);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        
        // Get all users
        $users = User::with('seller')->orderBy('id', 'desc')->get();
        
        // Get user activities with date filter
        $activities = UserActivity::whereBetween('created_at', [$startDate, $endDate])->get();
        
        // Get orders
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])->get();
        
        // Calculate statistics
        $userStats = $this->getUserStats($users, $activities, $orders, $startDate, $endDate);
        
        // Get new vs returning users
        $userSegments = $this->getUserSegments($users, $activities, $orders, $startDate, $endDate);
        
        // Get repeat purchase data
        $repeatPurchaseData = $this->getRepeatPurchaseData($users, $orders, $startDate, $endDate);
        
        // Get top customers
        $topCustomers = $this->getTopCustomers($users, $orders, $startDate, $endDate);
        
        return view('admin.user_analytics', compact(
            'users', 'activities', 'orders',
            'userStats', 'userSegments', 'repeatPurchaseData', 'topCustomers',
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
    
    private function getUserStats($users, $activities, $orders, $startDate, $endDate)
    {
        // Total registered users
        $totalUsers = $users->count();
        
        // New users in date range
        $newUsers = $users->filter(function ($user) use ($startDate, $endDate) {
            return $user->created_at && $user->created_at >= $startDate && $user->created_at <= $endDate;
        })->count();
        
        // Users with activities (guests + registered)
        $activeUsers = $activities->groupBy(function ($activity) {
            return $activity->user_id ?? $activity->session_id ?? $activity->ip_address;
        })->count();
        
        // Orders count
        $totalOrders = $orders->count();
        
        // Completed orders
        $completedOrders = $orders->where('order_status', 'completed')->count();
        
        return [
            'totalUsers' => $totalUsers,
            'newUsers' => $newUsers,
            'activeUsers' => $activeUsers,
            'totalOrders' => $totalOrders,
            'completedOrders' => $completedOrders
        ];
    }
    
    private function getUserSegments($users, $activities, $orders, $startDate, $endDate)
    {
        // Registered users who made purchase
        $registeredPurchasers = $orders->whereNotNull('user_id')->pluck('user_id')->unique()->count();
        
        // Guest users (no user_id)
        $guestPurchasers = $orders->whereNull('user_id')->count();
        
        // New users (registered in this period)
        $newUsers = $users->filter(function ($user) use ($startDate, $endDate) {
            return $user->created_at && $user->created_at >= $startDate && $user->created_at <= $endDate;
        });
        
        // Existing users
        $existingUsers = $users->filter(function ($user) use ($startDate, $endDate) {
            return $user->created_at && $user->created_at < $startDate;
        });
        
        // New user conversion
        $newUserOrders = $orders->filter(function ($order) use ($newUsers) {
            return $order->user_id && $newUsers->pluck('id')->contains($order->user_id);
        })->count();
        
        $existingUserOrders = $orders->filter(function ($order) use ($existingUsers) {
            return $order->user_id && $existingUsers->pluck('id')->contains($order->user_id);
        })->count();
        
        return [
            'registeredPurchasers' => $registeredPurchasers,
            'guestPurchasers' => $guestPurchasers,
            'newUsersCount' => $newUsers->count(),
            'existingUsersCount' => $existingUsers->count(),
            'newUserOrders' => $newUserOrders,
            'existingUserOrders' => $existingUserOrders
        ];
    }
    
    private function getRepeatPurchaseData($users, $orders, $startDate, $endDate)
    {
        // Get users with multiple orders
        $userOrderCounts = $orders->whereNotNull('user_id')
            ->groupBy('user_id')
            ->map(function ($userOrders) {
                return $userOrders->count();
            });
        
        // First time buyers (1 order)
        $firstTimeBuyers = $userOrderCounts->filter(function ($count) {
            return $count == 1;
        })->count();
        
        // Repeat buyers (2+ orders)
        $repeatBuyers = $userOrderCounts->filter(function ($count) {
            return $count >= 2;
        })->count();
        
        // Calculate repeat rate
        $totalBuyers = $userOrderCounts->count();
        $repeatRate = $totalBuyers > 0 ? round(($repeatBuyers / $totalBuyers) * 100, 1) : 0;
        
        // Orders by purchase count
        $ordersByCount = [
            '1' => $firstTimeBuyers,
            '2' => $userOrderCounts->filter(function ($count) { return $count == 2; })->count(),
            '3' => $userOrderCounts->filter(function ($count) { return $count == 3; })->count(),
            '4+' => $userOrderCounts->filter(function ($count) { return $count >= 4; })->count()
        ];
        
        return [
            'firstTimeBuyers' => $firstTimeBuyers,
            'repeatBuyers' => $repeatBuyers,
            'repeatRate' => $repeatRate,
            'ordersByCount' => $ordersByCount
        ];
    }
    
    private function getTopCustomers($users, $orders, $startDate, $endDate)
    {
        // Get order totals by user
        $userOrderTotals = $orders->whereNotNull('user_id')
            ->groupBy('user_id')
            ->map(function ($userOrders) {
                return [
                    'order_count' => $userOrders->count(),
                    'total_spent' => $userOrders->sum('total_amount')
                ];
            });
        
        $topCustomers = [];
        
        foreach ($userOrderTotals as $userId => $data) {
            $user = $users->find($userId);
            if ($user) {
                $topCustomers[] = [
                    'user' => $user,
                    'order_count' => $data['order_count'],
                    'total_spent' => $data['total_spent']
                ];
            }
        }
        
        // Sort by total spent
        usort($topCustomers, function ($a, $b) {
            return $b['total_spent'] - $a['total_spent'];
        });
        
        return array_slice($topCustomers, 0, 20);
    }
}