<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VendorShippingTier;
use App\Services\VendorShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Satıcı API + genel sepet kargo önizleme.
 */
class VendorShippingController extends Controller
{
    public function preview(Request $request, VendorShippingService $shipping)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $result = $shipping->calculateForCart($request->items);

        return response()->json($result);
    }

    public function sellerTiers()
    {
        $user = Auth::guard('api')->user() ?? Auth::user();
        $seller = $user?->seller;
        if (! $seller) {
            return response()->json(['message' => 'Satıcı bulunamadı.'], 403);
        }

        $tiers = VendorShippingTier::query()
            ->where('vendor_id', $seller->id)
            ->orderBy('sort_order')
            ->orderBy('min_amount')
            ->get();

        return response()->json([
            'tiers' => $tiers,
            'defaults' => VendorShippingService::defaultTierTemplate(),
        ]);
    }

    public function saveSellerTiers(Request $request)
    {
        $user = Auth::guard('api')->user() ?? Auth::user();
        $seller = $user?->seller;
        if (! $seller) {
            return response()->json(['message' => 'Satıcı bulunamadı.'], 403);
        }

        $request->validate([
            'tiers' => 'required|array|min:1|max:20',
            'tiers.*.min_amount' => 'required|numeric|min:0',
            'tiers.*.max_amount' => 'nullable|numeric|min:0',
            'tiers.*.shipping_fee' => 'required|numeric|min:0',
        ]);

        $rows = [];
        foreach (array_values($request->tiers) as $i => $tier) {
            $min = round((float) ($tier['min_amount'] ?? 0), 2);
            $maxRaw = $tier['max_amount'] ?? null;
            $max = ($maxRaw === null || $maxRaw === '') ? null : round((float) $maxRaw, 2);
            if ($max !== null && $max < $min) {
                return response()->json(['message' => 'Üst tutar alt tutardan küçük olamaz.'], 422);
            }
            $rows[] = [
                'vendor_id' => $seller->id,
                'min_amount' => $min,
                'max_amount' => $max,
                'shipping_fee' => round((float) ($tier['shipping_fee'] ?? 0), 2),
                'sort_order' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        VendorShippingTier::query()->where('vendor_id', $seller->id)->delete();
        VendorShippingTier::query()->insert($rows);

        return response()->json([
            'message' => 'Kargo kademeleri kaydedildi.',
            'tiers' => VendorShippingTier::query()
                ->where('vendor_id', $seller->id)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }
}
