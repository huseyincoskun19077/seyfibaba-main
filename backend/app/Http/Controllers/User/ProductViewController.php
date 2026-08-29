<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\UserProductView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductViewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $user = Auth::guard('api')->user();
        $productId = (int) $request->input('product_id');

        $product = Product::query()
            ->where('id', $productId)
            ->where('status', 1)
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $view = UserProductView::query()->firstOrNew([
            'user_id' => $user->id,
            'product_id' => $productId,
        ]);

        $view->view_count = (int) ($view->view_count ?? 0) + 1;
        $view->last_viewed_at = now();
        $view->save();

        return response()->json([
            'success' => true,
            'view_count' => $view->view_count,
        ]);
    }
}
