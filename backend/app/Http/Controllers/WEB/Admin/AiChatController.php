<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiChatConversation;
use App\Models\AiChatKnowledge;
use App\Models\AiChatMessage;
use App\Models\Setting;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    /**
     * AI Chat settings page.
     */
    public function settings()
    {
        $setting = Setting::first();
        $knowledge = AiChatKnowledge::orderBy('sort_order')->orderBy('id')->get();
        $conversations = AiChatConversation::with(['user', 'messages' => fn($q) => $q->orderBy('created_at')])
            ->withCount('messages')
            ->orderBy('updated_at', 'desc')
            ->take(50)
            ->get();

        return view('admin.ai_chat_settings', compact('setting', 'knowledge', 'conversations'));
    }

    /**
     * Update AI Chat settings.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'ai_chat_enabled' => 'nullable',
            'ai_chat_max_tokens' => 'required|integer|min:100|max:4096',
            'ai_chat_temperature' => 'required|numeric|min:0|max:2',
            'ai_chat_system_prompt' => 'nullable|string',
            'ai_chat_guest_enabled' => 'nullable',
        ]);

        $setting = Setting::first();
        $setting->update([
            'ai_chat_enabled' => $request->has('ai_chat_enabled') ? 1 : 0,
            'ai_chat_max_tokens' => $request->ai_chat_max_tokens,
            'ai_chat_temperature' => $request->ai_chat_temperature,
            'ai_chat_system_prompt' => $request->ai_chat_system_prompt,
            'ai_chat_guest_enabled' => $request->has('ai_chat_guest_enabled') ? 1 : 0,
        ]);

        $notification = trans('admin_validation.Updated Successfully');
        return redirect()->back()->withSuccess($notification);
    }

    /**
     * Knowledge base CRUD.
     */
    public function storeKnowledge(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:50',
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        AiChatKnowledge::create([
            'category' => $request->category,
            'question' => $request->question,
            'answer' => $request->answer,
            'is_active' => $request->has('is_active') ? 1 : 0,
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->back()->withSuccess('Bilgi bankası kaydı eklendi.');
    }

    public function updateKnowledge(Request $request, $id)
    {
        $request->validate([
            'category' => 'required|string|max:50',
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $entry = AiChatKnowledge::findOrFail($id);
        $entry->update([
            'category' => $request->category,
            'question' => $request->question,
            'answer' => $request->answer,
            'is_active' => $request->has('is_active') ? 1 : 0,
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->back()->withSuccess('Bilgi bankası kaydı güncellendi.');
    }

    public function deleteKnowledge($id)
    {
        AiChatKnowledge::findOrFail($id)->delete();
        return redirect()->back()->withSuccess('Bilgi bankası kaydı silindi.');
    }

    public function toggleKnowledge($id)
    {
        $entry = AiChatKnowledge::findOrFail($id);
        $entry->update(['is_active' => !$entry->is_active]);

        return redirect()->back()->withSuccess('Durum güncellendi.');
    }

    /**
     * View conversation messages.
     */
    public function conversationMessages($id)
    {
        $conversation = AiChatConversation::with('user')->findOrFail($id);
        $messages = AiChatMessage::where('conversation_id', $id)
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }
}
