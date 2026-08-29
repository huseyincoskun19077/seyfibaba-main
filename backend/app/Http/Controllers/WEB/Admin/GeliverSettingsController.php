<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\GdeliveryService;
use Illuminate\Http\Request;

class GeliverSettingsController extends Controller
{
    public function __construct(private GdeliveryService $gdelivery)
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $setting = Setting::first();

        return view('admin.geliver_settings', [
            'setting' => $setting,
            'webhookUrl' => url('/api/v1/webhooks/geliver'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'geliver_api_token' => 'nullable|string|max:500',
            'geliver_sender_address_id' => ['nullable', 'string', 'max:100', 'regex:/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'],
            'geliver_webhook_header_name' => 'nullable|string|max:100',
            'geliver_webhook_header_secret' => 'nullable|string|max:255',
        ], [
            'geliver_sender_address_id.regex' => 'Gönderici Adres ID geçerli bir UUID formatında olmalıdır (örn: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx).',
        ]);

        $setting = Setting::firstOrFail();
        $setting->geliver_api_token = $validated['geliver_api_token'] ?? null;
        $setting->geliver_test_mode = $request->has('geliver_test_mode') ? 'on' : '';
        $setting->geliver_sender_address_id = $validated['geliver_sender_address_id'] ?? null;
        $setting->geliver_webhook_header_name = $validated['geliver_webhook_header_name'] ?? null;
        $setting->geliver_webhook_header_secret = $validated['geliver_webhook_header_secret'] ?? null;
        $setting->save();

        $this->updateEnvValue('GELIVER_API_TOKEN', $setting->geliver_api_token ?? '');
        $this->updateEnvValue('GELIVER_TEST_MODE', $setting->geliver_test_mode === 'on' ? 'true' : 'false');
        $this->updateEnvValue('GELIVER_SENDER_ADDRESS_ID', $setting->geliver_sender_address_id ?? '');

        $notification = [
            'messege' => 'Geliver kargo ayarları başarıyla güncellendi.',
            'alert-type' => 'success',
        ];

        return redirect()->back()->with($notification);
    }

    public function createSenderAddress(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:30',
            'neighborhood' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city_code' => 'required|string|max:10',
            'city_name' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'zip' => 'nullable|string|max:20',
        ]);

        try {
            $address = $this->gdelivery->createSenderAddress($validated);
            $newId = $address['data']['id'] ?? $address['id'] ?? null;

            if ($newId) {
                $setting = Setting::firstOrFail();
                $setting->geliver_sender_address_id = $newId;
                $setting->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Gönderici adresi oluşturuldu.',
                'data' => $address,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function updateEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $env = file_get_contents($envPath);
        $pattern = '/^' . preg_quote($key, '/') . '=.*/m';
        $replacement = $key . '=' . $value;

        if (preg_match($pattern, $env)) {
            $env = preg_replace($pattern, $replacement, $env);
        } else {
            $env .= PHP_EOL . $replacement;
        }

        file_put_contents($envPath, $env);
    }
}
