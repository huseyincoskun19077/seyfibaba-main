<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Controller;
use App\Models\VendorSentosSetting;
use App\Services\Sentos\SentosApiClient;
use Auth;
use Illuminate\Http\Request;

/**
 * Phase 1 only: save Sentos credentials + test connection.
 * Does not create/update products or orders.
 */
class SentosSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index(SentosApiClient $client)
    {
        if (! config('features.sentos_enabled', true)) {
            abort(404);
        }

        $seller = Auth::guard('web')->user()?->seller;
        if (! $seller) {
            abort(403);
        }

        $settings = VendorSentosSetting::query()->where('vendor_id', $seller->id)->first();

        return view('seller.sentos_settings', [
            'seller' => $seller,
            'settings' => $settings,
            'normalizedPreview' => $settings
                ? $client->normalizeBaseUrl((string) $settings->api_base_url)
                : '',
        ]);
    }

    public function update(Request $request, SentosApiClient $client)
    {
        if (! config('features.sentos_enabled', true)) {
            abort(404);
        }

        $seller = Auth::guard('web')->user()?->seller;
        if (! $seller) {
            abort(403);
        }

        $settings = VendorSentosSetting::query()->firstOrNew(['vendor_id' => $seller->id]);

        $rules = [
            'api_base_url' => ['required', 'string', 'max:255'],
            'api_key' => [$settings->exists ? 'nullable' : 'required', 'string', 'max:500'],
            'api_secret' => [$settings->exists ? 'nullable' : 'required', 'string', 'max:500'],
            'is_enabled' => ['nullable', 'boolean'],
        ];

        $data = $request->validate($rules);

        $normalizedUrl = $client->normalizeBaseUrl($data['api_base_url']);
        if ($normalizedUrl === '' || ! filter_var($normalizedUrl, FILTER_VALIDATE_URL)) {
            return back()->with([
                'messege' => 'Geçersiz Sentos adresi. Örnek: firma-adi veya https://firma-adi.sentos.com.tr',
                'alert-type' => 'error',
            ])->withInput();
        }

        $settings->api_base_url = $normalizedUrl;

        if (! empty($data['api_key'])) {
            $settings->api_key = trim($data['api_key']);
        }
        if (! empty($data['api_secret'])) {
            $settings->api_secret = trim($data['api_secret']);
        }

        $settings->is_enabled = $request->boolean('is_enabled');
        $settings->vendor_id = $seller->id;
        $settings->save();

        return back()->with([
            'messege' => 'Sentos ayarları kaydedildi. Bağlantıyı test edebilirsiniz.',
            'alert-type' => 'success',
        ]);
    }

    public function test(SentosApiClient $client)
    {
        if (! config('features.sentos_enabled', true)) {
            abort(404);
        }

        $seller = Auth::guard('web')->user()?->seller;
        if (! $seller) {
            abort(403);
        }

        $settings = VendorSentosSetting::query()->where('vendor_id', $seller->id)->first();
        if (! $settings || ! $settings->hasCredentials()) {
            return back()->with([
                'messege' => 'Önce Sentos API bilgilerini kaydedin.',
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
        if (! config('features.sentos_enabled', true)) {
            abort(404);
        }

        $seller = Auth::guard('web')->user()?->seller;
        if (! $seller) {
            abort(403);
        }

        $settings = VendorSentosSetting::query()->where('vendor_id', $seller->id)->first();
        if ($settings) {
            $settings->is_enabled = false;
            $settings->save();
        }

        return back()->with([
            'messege' => 'Sentos entegrasyonu bu satıcı için kapatıldı. Mevcut Seyfibaba ürünleri etkilenmedi.',
            'alert-type' => 'success',
        ]);
    }
}
