<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AdminAdAssistantService;
use Illuminate\Http\Request;

class AdAssistantController extends Controller
{
    private const SESSION_KEY = 'admin_ad_assistant_history';

    public function index()
    {
        $setting = Setting::first();
        $history = session(self::SESSION_KEY, []);
        $openaiReady = $setting
            && $setting->openai_enabled
            && trim((string) ($setting->openai_api_key ?? '')) !== ''
            && ! str_starts_with(trim((string) $setting->openai_api_key), 'gsk_');

        $knowledge = config('admin_ad_assistant.knowledge', []);
        $chatModel = config('admin_ad_assistant.chat_model');
        $imageModel = config('admin_ad_assistant.image_model');

        return view('admin.ad_assistant', compact(
            'setting',
            'history',
            'openaiReady',
            'knowledge',
            'chatModel',
            'imageModel'
        ));
    }

    public function chat(Request $request, AdminAdAssistantService $service)
    {
        $request->validate([
            'message' => 'required|string|min:2|max:4000',
        ]);

        $message = trim($request->input('message'));
        $history = session(self::SESSION_KEY, []);

        try {
            $result = $service->chat($history, $message);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $history[] = ['role' => 'user', 'content' => $message];
        $history[] = ['role' => 'assistant', 'content' => $result['reply']];
        $max = (int) config('admin_ad_assistant.max_history', 20) * 2;
        session([self::SESSION_KEY => array_slice($history, -$max)]);

        return response()->json([
            'success' => true,
            'reply' => $result['reply'],
            'model' => $result['model'],
        ]);
    }

    public function generateImage(Request $request, AdminAdAssistantService $service)
    {
        $request->validate([
            'prompt' => 'required|string|min:8|max:2000',
            'size' => 'nullable|string|in:1024x1024,1024x1536,1536x1024,1024x1792,1792x1024',
        ]);

        try {
            $result = $service->generateImage(
                $request->input('prompt'),
                $request->input('size', '1024x1024')
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $note = 'Görsel üretildi: ' . $result['url'];
        $history = session(self::SESSION_KEY, []);
        $history[] = ['role' => 'user', 'content' => '[Görsel] ' . $request->input('prompt')];
        $history[] = ['role' => 'assistant', 'content' => $note];
        session([self::SESSION_KEY => array_slice($history, -40)]);

        return response()->json([
            'success' => true,
            'url' => $result['url'],
            'model' => $result['model'],
            'prompt' => $result['prompt'],
        ]);
    }

    public function clear(Request $request)
    {
        session()->forget(self::SESSION_KEY);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.ad-assistant')->withSuccess('Sohbet temizlendi.');
    }
}
