<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Support\NotificationAudience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();

        $notifications = NotificationAudience::sellerQuery($user->notifications())
            ->when($request->filled('unread_only'), fn ($q) => $q->whereNull('read_at'))
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 20));

        $unreadCount = NotificationAudience::sellerQuery($user->unreadNotifications())->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead($id)
    {
        $user = Auth::guard('api')->user();
        $notification = NotificationAudience::sellerQuery($user->notifications())
            ->where('id', $id)
            ->first();

        if (! $notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead()
    {
        $user = Auth::guard('api')->user();
        NotificationAudience::sellerQuery($user->unreadNotifications())
            ->get()
            ->each(fn ($notification) => $notification->markAsRead());

        return response()->json(['message' => 'All notifications marked as read']);
    }
}
