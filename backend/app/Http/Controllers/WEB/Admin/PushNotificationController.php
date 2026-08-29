<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Notifications\AdminBroadcastNotification;
use App\Services\PushBroadcastService;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        return view('admin.push_notifications');
    }

    public function store(Request $request, PushBroadcastService $broadcast)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:500'],
            'target' => ['required', 'in:all_buyers,all_sellers,single_user'],
            'user_email' => ['nullable', 'email', 'required_if:target,single_user'],
            'product_slug' => ['nullable', 'string', 'max:255'],
            'campaign_slug' => ['nullable', 'string', 'max:255'],
        ]);

        $extra = array_filter([
            'product_slug' => $this->normalizeSlug($request->input('product_slug')),
            'campaign_slug' => $this->normalizeSlug($request->input('campaign_slug')),
        ]);

        $notification = new AdminBroadcastNotification(
            $request->input('title'),
            $request->input('message'),
            $extra
        );

        $result = match ($request->input('target')) {
            'all_sellers' => $broadcast->sendToAllSellers($notification),
            'single_user' => $broadcast->sendToUserByEmail((string) $request->input('user_email'), $notification),
            default => $broadcast->sendToAllBuyers($notification),
        };

        $message = "Uygulama icinde: {$result['inbox']}, push: {$result['push']} kullanici.";
        if (($result['push'] ?? 0) === 0 && ! empty($result['push_reason'])) {
            $message .= ' '.$this->pushFailureHint($result['push_reason']);
        }

        return redirect()->back()->with([
            'messege' => $message,
            'alert-type' => $result['push'] > 0 ? 'success' : 'warning',
        ]);
    }

    private function pushFailureHint(string $reason): string
    {
        return match ($reason) {
            'no_token' => 'Push icin cihaz tokeni yok. Uygulamayi acip tekrar giris yapin.',
            'not_configured' => 'Firebase credentials okunamiyor: '.(string) config('firebase.credentials').' — www-data izni veya php artisan config:cache gerekli.',
            'auth_failed' => 'Firebase OAuth token alinamadi. Once: php artisan cache:forget fcm_oauth_access_token — sonra storage/logs/laravel.log kontrol edin.',
            'send_failed' => 'FCM gonderimi basarisiz. storage/logs/laravel.log kontrol edin.',
            'user_not_found' => 'Kullanici bulunamadi.',
            default => 'Push gonderilemedi.',
        };
    }

    private function normalizeSlug(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (str_contains($value, '://') || str_contains($value, '/')) {
            $path = parse_url($value, PHP_URL_PATH) ?? $value;
            $segments = array_values(array_filter(explode('/', trim((string) $path, '/'))));

            return $segments !== [] ? (string) end($segments) : $value;
        }

        return $value;
    }
}
