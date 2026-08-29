<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerContactAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('checkseller');
    }

    public function index()
    {
        $user = Auth::guard('api')->user();
        $messages = ContactMessage::query()
            ->where('email', $user->email)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $user = Auth::guard('api')->user();
        $seller = $user->seller;

        $msg = new ContactMessage();
        $msg->name = $user->name;
        $msg->email = $user->email;
        $msg->phone = $seller->phone ?? $user->phone;
        $msg->subject = $request->subject;
        $msg->message = $request->message;
        $msg->save();

        return response()->json([
            'message' => 'Mesajınız admin\'e iletildi.',
            'item' => $msg,
        ], 201);
    }
}
