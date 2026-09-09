<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Controller;
use App\Models\VendorSentosSetting;
use Auth;

/**
 * Seller integrations hub. Opt-in plugins only; does not alter marketplace core.
 */
class SellerIntegrationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index()
    {
        $seller = Auth::guard('web')->user()?->seller;
        if (! $seller) {
            abort(403);
        }

        $sentos = null;
        if (config('features.sentos_enabled', true)) {
            $sentos = VendorSentosSetting::query()->where('vendor_id', $seller->id)->first();
        }

        $integrations = [
            [
                'key' => 'sentos',
                'name' => 'Sentos',
                'description' => 'Ürün, stok, fiyat ve sipariş senkronu için Sentos API bağlantısı.',
                'enabled_globally' => (bool) config('features.sentos_enabled', true),
                'connected' => (bool) ($sentos?->is_enabled && $sentos?->hasCredentials()),
                'status_label' => $this->sentosStatusLabel($sentos),
                'route' => 'seller.sentos.index',
                'icon' => 'fas fa-store',
                'badge' => 'Aktif eklenti',
            ],
            [
                'key' => 'softtr',
                'name' => 'Softtr',
                'description' => 'Yakında: Softtr mağaza entegrasyonu.',
                'enabled_globally' => false,
                'connected' => false,
                'status_label' => 'Yakında',
                'route' => null,
                'icon' => 'fas fa-box-open',
                'badge' => 'Planlandı',
            ],
            [
                'key' => 'other',
                'name' => 'Diğer entegrasyonlar',
                'description' => 'Trendyol, Hepsiburada ve diğer kanallar buradan eklenecek.',
                'enabled_globally' => false,
                'connected' => false,
                'status_label' => 'Yakında',
                'route' => null,
                'icon' => 'fas fa-puzzle-piece',
                'badge' => 'Planlandı',
            ],
        ];

        return view('seller.integrations.index', [
            'seller' => $seller,
            'integrations' => $integrations,
        ]);
    }

    private function sentosStatusLabel(?VendorSentosSetting $sentos): string
    {
        if (! $sentos) {
            return 'Kurulmadı';
        }

        if (! $sentos->hasCredentials()) {
            return 'Bilgi girilmedi';
        }

        if ($sentos->last_test_status === 'success' && $sentos->is_enabled) {
            return 'Bağlı';
        }

        if ($sentos->last_test_status === 'failed') {
            return 'Test başarısız';
        }

        if ($sentos->is_enabled) {
            return 'Açık (test edilmedi)';
        }

        return 'Kapalı';
    }
}
