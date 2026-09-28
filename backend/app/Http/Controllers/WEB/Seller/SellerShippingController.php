<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Controller;
use App\Models\VendorShippingTier;
use App\Services\VendorShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerShippingController extends Controller
{
    public function edit()
    {
        $user = Auth::guard('web')->user();
        $seller = $user->seller;
        $tiers = VendorShippingTier::query()
            ->where('vendor_id', $seller->id)
            ->orderBy('sort_order')
            ->orderBy('min_amount')
            ->get();

        if ($tiers->isEmpty()) {
            $tiers = collect(VendorShippingService::defaultTierTemplate())
                ->map(fn ($row) => (object) $row);
        }

        return view('seller.shipping_tiers', compact('seller', 'tiers'));
    }

    public function update(Request $request)
    {
        $user = Auth::guard('web')->user();
        $seller = $user->seller;

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
                return redirect()->back()
                    ->withInput()
                    ->with(['messege' => 'Üst tutar alt tutardan küçük olamaz.', 'alert-type' => 'error']);
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

        return redirect()->route('seller.shipping-tiers.edit')
            ->with(['messege' => 'Kargo kademeleri kaydedildi.', 'alert-type' => 'success']);
    }
}
