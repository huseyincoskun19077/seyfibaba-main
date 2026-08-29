<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\InstallmentService;
use Illuminate\Http\Request;

class InstallmentController extends Controller
{
    public function kontrol(Request $request)
    {
        // POST veya GET'ten cart al
        $cart = $request->input('cart', $request->json('cart', []));
        
        if (empty($cart)) {
            return response()->json([
                'taksit_olabilir' => true,
                'engellenen_urunler' => [],
                'mesaj' => ''
            ]);
        }
        
        // Frontend'den gelen format: [{product_id: 1, price: 100, qty: 2}, ...]
        // Service'in beklediği format: [1 => ['price' => 100, 'qty' => 2], ...]
        $formattedCart = [];
        
        if (isset($cart[0]) && is_array($cart[0])) {
            // Array formatında geldi
            foreach ($cart as $item) {
                if (isset($item['product_id'])) {
                    $formattedCart[$item['product_id']] = [
                        'price' => $item['price'] ?? 0,
                        'qty' => $item['qty'] ?? 1
                    ];
                }
            }
        } else {
            // Obje formatında geldi (key = product_id)
            $formattedCart = array_map(function ($item) {
                return [
                    'price' => $item['price'] ?? 0,
                    'qty' => $item['qty'] ?? 1
                ];
            }, $cart);
        }
        
        $result = InstallmentService::checkCartInstallmentStatus($formattedCart);
        
        return response()->json($result);
    }

    public function taksitliUrunler(Request $request)
    {
        $cart = $request->input('cart', $request->json('cart', []));
        
        if (empty($cart)) {
            return response()->json([
                'taksit_olan_urunler' => [],
                'taksit_sayisi' => 12
            ]);
        }
        
        // Format cart
        $formattedCart = [];
        
        if (isset($cart[0]) && is_array($cart[0])) {
            foreach ($cart as $item) {
                if (isset($item['product_id'])) {
                    $formattedCart[$item['product_id']] = [
                        'price' => $item['price'] ?? 0,
                        'qty' => $item['qty'] ?? 1
                    ];
                }
            }
        } else {
            $formattedCart = array_map(function ($item) {
                return [
                    'price' => $item['price'] ?? 0,
                    'qty' => $item['qty'] ?? 1
                ];
            }, $cart);
        }
        
        $result = InstallmentService::getInstallmentProducts($formattedCart);
        
        return response()->json($result);
    }

}