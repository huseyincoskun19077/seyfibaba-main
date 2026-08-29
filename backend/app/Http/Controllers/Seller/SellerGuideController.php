<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;

class SellerGuideController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('checkseller');
    }

    public function index()
    {
        return response()->json(config('seller_guide', [
            'title' => 'Satıcı Şartlar ve Tanıtım',
            'subtitle' => '',
            'hero' => '',
            'highlights' => [],
            'sections' => [],
            'contact' => [],
        ]));
    }
}
