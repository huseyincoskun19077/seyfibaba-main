<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Controller;
use App\Models\VendorSentosSetting;
use App\Models\VendorSofttrSetting;
use App\Support\VendorCatalogIntegration;
use Auth;

/**
 * Seller integrations hub. Opt-in; one catalog integration at a time.
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

        $softtr = null;
        if (config('features.softtr_enabled', true)) {
            $softtr = VendorSofttrSetting::query()->where('vendor_id', $seller->id)->first();
        }

        $activeKey = VendorCatalogIntegration::activeKey((int) $seller->id);
        $vendorId = (int) $seller->id;

        $integrations = [
            [
                'key' => VendorCatalogIntegration::SENTOS,
                'name' => 'Sentos',
                'description' => 'Ürün çekme, stok/fiyat, ödenen sipariş aktarımı ve kargo durumu.',
                'enabled_globally' => (bool) config('features.sentos_enabled', true),
                'connected' => (bool) ($sentos?->is_enabled && $sentos?->hasCredentials()),
                'status_label' => $this->sentosStatusLabel($sentos),
                'route' => 'seller.sentos.index',
                'icon' => 'fas fa-store',
                'badge' => 'Hazır',
                'locked' => ! VendorCatalogIntegration::canUse($vendorId, VendorCatalogIntegration::SENTOS),
                'lock_message' => VendorCatalogIntegration::blockMessage($vendorId, VendorCatalogIntegration::SENTOS),
            ],
            [
                'key' => VendorCatalogIntegration::SOFTTR,
                'name' => 'Softtr',
                'description' => 'Softtr mağazadan ürün çekme; saatlik fiyat ve stok güncelleme (tek yön).',
                'enabled_globally' => (bool) config('features.softtr_enabled', true),
                'connected' => (bool) ($softtr?->is_enabled && $softtr?->hasCredentials()),
                'status_label' => $this->softtrStatusLabel($softtr),
                'route' => 'seller.softtr.index',
                'icon' => 'fas fa-box-open',
                'badge' => 'Hazır',
                'locked' => ! VendorCatalogIntegration::canUse($vendorId, VendorCatalogIntegration::SOFTTR),
                'lock_message' => VendorCatalogIntegration::blockMessage($vendorId, VendorCatalogIntegration::SOFTTR),
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
                'locked' => $activeKey !== null,
                'lock_message' => $activeKey
                    ? VendorCatalogIntegration::blockMessage($vendorId, 'other')
                    : '',
            ],
        ];

        return view('seller.integrations.index', [
            'seller' => $seller,
            'integrations' => $integrations,
            'activeIntegration' => $activeKey,
            'activeIntegrationLabel' => VendorCatalogIntegration::label($activeKey),
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

    private function softtrStatusLabel(?VendorSofttrSetting $softtr): string
    {
        if (! $softtr) {
            return 'Kurulmadı';
        }
        if (! $softtr->hasCredentials()) {
            return 'Bilgi girilmedi';
        }
        if ($softtr->last_test_status === 'success' && $softtr->is_enabled) {
            return 'Bağlı';
        }
        if ($softtr->last_test_status === 'failed') {
            return 'Test başarısız';
        }
        if ($softtr->is_enabled) {
            return 'Açık (test edilmedi)';
        }

        return 'Kapalı';
    }
}
