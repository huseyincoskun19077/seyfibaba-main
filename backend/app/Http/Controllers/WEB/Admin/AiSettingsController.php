<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class AiSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function settings()
    {
        $setting = Setting::first();

        return view('admin.ai_settings', compact('setting'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'openai_api_key' => 'nullable|string|max:500',
            'openai_model' => 'nullable|string|max:100',
            'openai_timeout' => 'nullable|integer|min:10|max:300',
            'openai_enabled' => 'nullable',
            'claude_api_key' => 'nullable|string|max:500',
            'claude_model' => 'nullable|string|max:100',
            'claude_timeout' => 'nullable|integer|min:10|max:300',
            'claude_enabled' => 'nullable',
        ]);

        $setting = Setting::first();

        // Toggle fields
        $setting->openai_enabled = $request->has('openai_enabled') ? 1 : 0;
        $setting->claude_enabled = $request->has('claude_enabled') ? 1 : 0;

        // Model & timeout
        $setting->openai_model = $request->openai_model;
        $setting->openai_timeout = $request->openai_timeout ?? 60;
        $setting->claude_model = $request->claude_model;
        $setting->claude_timeout = $request->claude_timeout ?? 60;

        // Only update API keys if not masked
        if ($request->filled('openai_api_key') && !str_contains($request->openai_api_key, '****')) {
            $setting->openai_api_key = $request->openai_api_key;
        }
        if ($request->filled('claude_api_key') && !str_contains($request->claude_api_key, '****')) {
            $setting->claude_api_key = $request->claude_api_key;
        }

        $setting->save();

        $notification = array('messege' => 'AI ayarları başarıyla güncellendi.', 'alert-type' => 'success');
        return redirect()->back()->with($notification);
    }

    public function testConnection(Request $request)
    {
        $setting = Setting::first();
        $provider = $request->input('provider', 'openai');

        try {
            if ($provider === 'openai') {
                $apiKey = trim($setting->openai_api_key ?? '');
                if (!$apiKey) {
                    return response()->json(['success' => false, 'message' => 'OpenAI API anahtarı tanımlı değil.']);
                }

                $endpoint = str_starts_with($apiKey, 'gsk_')
                    ? 'https://api.groq.com/openai/v1/models'
                    : 'https://api.openai.com/v1/models';

                $response = \Illuminate\Support\Facades\Http::timeout(10)
                    ->withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                    ->get($endpoint);

                if ($response->successful()) {
                    $providerName = str_starts_with($apiKey, 'gsk_') ? 'Groq' : 'OpenAI';
                    return response()->json(['success' => true, 'message' => "{$providerName} bağlantısı başarılı!"]);
                }

                return response()->json(['success' => false, 'message' => 'API yanıt hatası: ' . $response->status()]);
            }

            if ($provider === 'claude') {
                $apiKey = trim($setting->claude_api_key ?? '');
                if (!$apiKey) {
                    return response()->json(['success' => false, 'message' => 'Claude API anahtarı tanımlı değil.']);
                }

                $response = \Illuminate\Support\Facades\Http::timeout(10)
                    ->withHeaders([
                        'x-api-key' => $apiKey,
                        'anthropic-version' => '2023-06-01',
                        'Content-Type' => 'application/json',
                    ])->post('https://api.anthropic.com/v1/messages', [
                        'model' => $setting->claude_model ?? 'claude-sonnet-4-5-20250929',
                        'max_tokens' => 10,
                        'messages' => [['role' => 'user', 'content' => 'test']],
                    ]);

                if ($response->successful()) {
                    return response()->json(['success' => true, 'message' => 'Claude bağlantısı başarılı!']);
                }

                return response()->json(['success' => false, 'message' => 'API yanıt hatası: ' . $response->status()]);
            }

            return response()->json(['success' => false, 'message' => 'Geçersiz provider.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Bağlantı hatası: ' . $e->getMessage()]);
        }
    }

    public function generateContent(Request $request)
    {
        return app(\App\Http\Controllers\AiContentController::class)->generate($request);
    }
}
