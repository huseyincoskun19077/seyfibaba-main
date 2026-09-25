<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CheckoutLegalContractService;
use Illuminate\Http\Request;
use Auth;

class CheckoutLegalContractController extends Controller
{
    public function preview(Request $request, CheckoutLegalContractService $service)
    {
        $request->validate([
            'shipping_address_id' => 'nullable|integer',
            'billing_address_id' => 'nullable|integer',
            'shipping_address' => 'nullable|array',
            'billing_address' => 'nullable|array',
            'shipping_charge' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:120',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|integer',
            'items.*.qty' => 'nullable|integer|min:1',
        ]);

        $user = Auth::guard('api')->user();
        $payload = [
            'shipping_address_id' => $request->input('shipping_address_id'),
            'billing_address_id' => $request->input('billing_address_id'),
            'shipping_address' => $request->input('shipping_address'),
            'billing_address' => $request->input('billing_address'),
            'shipping_charge' => $request->input('shipping_charge', 0),
            'payment_method' => $request->input('payment_method'),
            'items' => $request->input('items'),
            'user_id' => $user?->id,
        ];

        return response()->json($service->build($payload));
    }
}
