<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\SellerAiAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerAiAssistantController extends Controller
{
    public function __construct(private SellerAiAssistantService $assistant)
    {
        $this->middleware('auth:api');
        $this->middleware('checkseller');
    }

    public function chat(Request $request)
    {
        $seller = Auth::guard('api')->user()->seller;
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Satıcı hesabı gerekli.'], 403);
        }

        $request->validate([
            'message' => 'required|string|max:2000',
            'history' => 'nullable|array|max:20',
            'history.*.role' => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:5000',
        ]);

        $result = $this->assistant->chat(
            $seller,
            $request->input('message'),
            $request->input('history', [])
        );

        return response()->json([
            'success' => true,
            'reply' => $result['reply'],
            'action_taken' => $result['action_taken'],
            'history' => $result['history'],
        ]);
    }
}
