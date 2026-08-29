<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;

class SellerFaqController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('checkseller');
    }

    public function index()
    {
        return response()->json(config('seller_faq', [
            'intro' => '',
            'sections' => [],
        ]));
    }
}
