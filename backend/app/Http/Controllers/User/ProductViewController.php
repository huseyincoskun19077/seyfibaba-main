<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductViewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->except(['storeGuest']);
    }

    public function store(Request $request, RecommendationService $recommendations)
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
            return response()->json(['message' => 'Ürün bulunamadı.'], 404);
        }

        $recommendations->recordUserView($user, $productId);

        return response()->json([
            'success' => true,
            'message' => 'Görüntüleme kaydedildi.',
        ]);
    }

    /**
     * Misafir görüntüleme — yalnızca çerez/pazarlama izni ile.
     */
    public function storeGuest(Request $request, RecommendationService $recommendations)
    {
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'guest_key' => ['required', 'string', 'max:64'],
            'consent' => ['required', 'boolean'],
        ]);

        if (! $request->boolean('consent')) {
            return response()->json([
                'success' => false,
                'message' => 'Kişiselleştirme izni yok; görüntüleme kaydedilmedi.',
            ]);
        }

        $productId = (int) $request->input('product_id');
        $product = Product::query()->where('id', $productId)->where('status', 1)->first();
        if (! $product) {
            return response()->json(['message' => 'Ürün bulunamadı.'], 404);
        }

        $recommendations->recordGuestView(
            (string) $request->input('guest_key'),
            $productId,
            true
        );

        return response()->json([
            'success' => true,
            'message' => 'Görüntüleme kaydedildi.',
        ]);
    }
}
