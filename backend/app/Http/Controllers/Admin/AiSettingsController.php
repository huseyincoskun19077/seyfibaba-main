<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class AiSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin-api');
    }

    public function show()
    {
        $setting = Setting::first();

        return response()->json([
            'openai_api_key' => $setting->openai_api_key ? $this->maskKey($setting->openai_api_key) : null,
            'openai_model' => $setting->openai_model ?? 'gpt-4o-mini',
            'openai_timeout' => $setting->openai_timeout ?? 60,
            'openai_enabled' => (bool) $setting->openai_enabled,
            'claude_api_key' => $setting->claude_api_key ? $this->maskKey($setting->claude_api_key) : null,
            'claude_model' => $setting->claude_model ?? 'claude-sonnet-4-5-20250929',
            'claude_timeout' => $setting->claude_timeout ?? 60,
            'claude_enabled' => (bool) $setting->claude_enabled,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'openai_api_key' => 'nullable|string|max:500',
            'openai_model' => 'nullable|string|max:100',
            'openai_timeout' => 'nullable|integer|min:10|max:300',
            'openai_enabled' => 'nullable|boolean',
            'claude_api_key' => 'nullable|string|max:500',
            'claude_model' => 'nullable|string|max:100',
            'claude_timeout' => 'nullable|integer|min:10|max:300',
            'claude_enabled' => 'nullable|boolean',
        ]);

        $setting = Setting::first();

        // Only update API keys if not masked (contains ****)
        $fields = ['openai_model', 'openai_timeout', 'openai_enabled', 'claude_model', 'claude_timeout', 'claude_enabled'];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $setting->$field = $request->$field;
            }
        }

        if ($request->filled('openai_api_key') && !str_contains($request->openai_api_key, '****')) {
            $setting->openai_api_key = $request->openai_api_key;
        }
        if ($request->filled('claude_api_key') && !str_contains($request->claude_api_key, '****')) {
            $setting->claude_api_key = $request->claude_api_key;
        }

        $setting->save();

        return response()->json(['message' => 'AI ayarları güncellendi.']);
    }

    private function maskKey(string $key): string
    {
        if (strlen($key) <= 8) {
            return '****';
        }

        return substr($key, 0, 4) . '****' . substr($key, -4);
    }
}
