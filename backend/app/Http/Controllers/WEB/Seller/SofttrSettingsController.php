<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Controller;
use App\Models\VendorSofttrProductMap;
use App\Models\VendorSofttrSetting;
use App\Services\Softtr\SofttrApiClient;
use App\Services\Softtr\SofttrProductSyncService;
use App\Support\VendorCatalogIntegration;
use Auth;
use Illuminate\Http\Request;

/**
 * Softtr seller settings: credentials, test, product pull (opt-in, exclusive vs Sentos).
 */
class SofttrSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index(SofttrApiClient $client)
    {
        if (! config('features.softtr_enabled', true)) {
            abort(404);
        }

        $seller = Auth::guard('web')->user()?->seller;
        if (! $seller) {
            abort(403);
        }

        $settings = VendorSofttrSetting::query()->where('vendor_id', $seller->id)->first();
        $mappedProducts = VendorSofttrProductMap::query()
            ->where('vendor_id', $seller->id)
            ->with([
                'product:id,name,short_name,sku,price,offer_price,qty,status,thumb_image,category_id,sub_category_id,child_category_id',
                'product.category:id,name',
                'product.subCategory:id,name',
                'product.childCategory:id,name',
            ])
            ->orderByDesc('last_synced_at')
            ->orderByDesc('id')
            ->paginate(30);

        return view('seller.softtr_settings', [
            'seller' => $seller,
            'settings' => $settings,
            'mappedProducts' => $mappedProducts,
            'normalizedPreview' => $settings
                ? $client->normalizeBaseUrl((string) $settings->api_base_url)
                : '',
            'integrationBlocked' => ! VendorCatalogIntegration::canUse((int) $seller->id, VendorCatalogIntegration::SOFTTR),
            'blockMessage' => VendorCatalogIntegration::blockMessage((int) $seller->id, VendorCatalogIntegration::SOFTTR),
        ]);
    }

    public function update(Request $request, SofttrApiClient $client)
    {
        if (! config('features.softtr_enabled', true)) {
            abort(404);
        }

        $seller = Auth::guard('web')->user()?->seller;
        if (! $seller) {
            abort(403);
        }

        $settings = VendorSofttrSetting::query()->firstOrNew(['vendor_id' => $seller->id]);

        $data = $request->validate([
            'api_base_url' => ['required', 'string', 'max:255'],
            'api_user' => [$settings->exists ? 'nullable' : 'required', 'string', 'max:500'],
            'api_password' => [$settings->exists ? 'nullable' : 'required', 'string', 'max:500'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $normalizedUrl = $client->normalizeBaseUrl($data['api_base_url']);
        if ($normalizedUrl === '' || ! filter_var($normalizedUrl, FILTER_VALIDATE_URL)) {
            return back()->with([
                'messege' => 'Geçersiz Softtr adresi. Örnek: magazaadi.com veya https://www.magazaadi.com',
                'alert-type' => 'error',
            ])->withInput();
        }

        $wantEnable = $request->boolean('is_enabled');
        if ($wantEnable && ! VendorCatalogIntegration::canUse((int) $seller->id, VendorCatalogIntegration::SOFTTR)) {
            return back()->with([
                'messege' => VendorCatalogIntegration::blockMessage((int) $seller->id, VendorCatalogIntegration::SOFTTR),
                'alert-type' => 'error',
            ])->withInput();
        }

        $settings->api_base_url = $normalizedUrl;
        if (! empty($data['api_user'])) {
            $settings->api_user = trim($data['api_user']);
        }
        if (! empty($data['api_password'])) {
            $settings->api_password = trim($data['api_password']);
        }
        $settings->is_enabled = $wantEnable;
        $settings->vendor_id = $seller->id;
        $settings->save();

        return back()->with([
            'messege' => 'Softtr ayarları kaydedildi.',
            'alert-type' => 'success',
        ]);
    }

    public function test(SofttrApiClient $client)
    {
        if (! config('features.softtr_enabled', true)) {
            abort(404);
        }

        $seller = Auth::guard('web')->user()?->seller;
        if (! $seller) {
            abort(403);
        }

        $settings = VendorSofttrSetting::query()->where('vendor_id', $seller->id)->first();
        if (! $settings || ! $settings->hasCredentials()) {
            return back()->with([
                'messege' => 'Önce Softtr API bilgilerini kaydedin.',
                'alert-type' => 'error',
            ]);
        }

        $result = $client->testConnection($settings);
        $settings->last_tested_at = now();
        $settings->last_test_status = $result['ok'] ? 'success' : 'failed';
        $settings->last_test_message = trim(
            $result['message'] . ($result['product_hint'] ? ' ' . $result['product_hint'] : '')
        );
        $settings->save();

        return back()->with([
            'messege' => $settings->last_test_message,
            'alert-type' => $result['ok'] ? 'success' : 'error',
        ]);
    }

    public function disable()
    {
        if (! config('features.softtr_enabled', true)) {
            abort(404);
        }

        $seller = Auth::guard('web')->user()?->seller;
        if (! $seller) {
            abort(403);
        }

        $settings = VendorSofttrSetting::query()->where('vendor_id', $seller->id)->first();
        if ($settings) {
            $settings->is_enabled = false;
            $settings->save();
        }

        return back()->with([
            'messege' => 'Softtr entegrasyonu kapatıldı. Mevcut Seyfibaba ürünleri etkilenmedi.',
            'alert-type' => 'success',
        ]);
    }

    public function syncProducts(SofttrProductSyncService $syncService)
    {
        if (! config('features.softtr_enabled', true)) {
            abort(404);
        }

        $seller = Auth::guard('web')->user()?->seller;
        if (! $seller) {
            abort(403);
        }

        $settings = VendorSofttrSetting::query()->where('vendor_id', $seller->id)->first();
        if (! $settings || ! $settings->hasCredentials()) {
            return back()->with([
                'messege' => 'Önce Softtr API bilgilerini kaydedin.',
                'alert-type' => 'error',
            ]);
        }

        if (! $settings->is_enabled) {
            return back()->with([
                'messege' => 'Senkron için Softtr entegrasyonunu açmanız gerekir.',
                'alert-type' => 'error',
            ]);
        }

        if (! VendorCatalogIntegration::canUse((int) $seller->id, VendorCatalogIntegration::SOFTTR)) {
            return back()->with([
                'messege' => VendorCatalogIntegration::blockMessage((int) $seller->id, VendorCatalogIntegration::SOFTTR),
                'alert-type' => 'error',
            ]);
        }

        $result = $syncService->syncVendor($settings);

        return back()->with([
            'messege' => $result['message'],
            'alert-type' => $result['ok'] ? 'success' : 'error',
        ]);
    }
}
