<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Models\Setting;
use App\Services\AiChatService;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function __construct(
        private AiChatService $aiChatService,
    ) {}

    /**
     * Check if AI chat is enabled (public).
     */
    public function status()
    {
        try {
            $settings = Setting::first();

            return response()->json([
                'success' => true,
                'data' => [
                    'enabled' => (bool) ($settings->ai_chat_enabled ?? false),
                    'guest_enabled' => (bool) ($settings->ai_chat_guest_enabled ?? false),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => true,
                'data' => [
                    'enabled' => false,
                    'guest_enabled' => false,
                ],
            ]);
        }
    }

    /**
     * Send message (guest - no auth required).
     */
    public function guestSend(Request $request)
    {
        $settings = Setting::first();

        if (!$settings->ai_chat_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'AI chat şu anda devre dışı.',
            ], 503);
        }

        if (!$settings->ai_chat_guest_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Misafir AI chat kullanılamaz. Lütfen giriş yapın.',
            ], 403);
        }

        $request->validate([
            'message' => 'required|string|max:2000',
            'session_id' => 'required|string|max:64',
        ]);

        try {
            $result = $this->aiChatService->sendMessage(
                $request->message,
                $request->session_id,
                null
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => $result['content'],
                    'conversation_id' => $result['conversation_id'],
                    'tokens_used' => $result['tokens_used'],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('AI Chat Error (Guest)', [
                'session_id' => $request->session_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'AI yanıt veremedi. Lütfen tekrar deneyin.',
            ], 500);
        }
    }

    /**
     * Send message (authenticated user).
     */
    public function send(Request $request)
    {
        $settings = Setting::first();

        if (!$settings->ai_chat_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'AI chat şu anda devre dışı.',
            ], 503);
        }

        $request->validate([
            'message' => 'required|string|max:2000',
            'session_id' => 'required|string|max:64',
        ]);

        $userId = auth('api')->id();

        try {
            $result = $this->aiChatService->sendMessage(
                $request->message,
                $request->session_id,
                $userId
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => $result['content'],
                    'conversation_id' => $result['conversation_id'],
                    'tokens_used' => $result['tokens_used'],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('AI Chat Error (User)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'AI yanıt veremedi. Lütfen tekrar deneyin.',
            ], 500);
        }
    }

    /**
     * Get conversation history (authenticated).
     */
    public function history(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string|max:64',
        ]);

        $userId = auth('api')->id();

        $conversation = AiChatConversation::where('session_id', $request->session_id)
            ->where('user_id', $userId)
            ->first();

        if (!$conversation) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $messages = AiChatMessage::where('conversation_id', $conversation->id)
            ->where('role', '!=', 'system')
            ->orderBy('created_at')
            ->get(['id', 'role', 'content', 'created_at']);

        return response()->json(['success' => true, 'data' => $messages]);
    }
}
