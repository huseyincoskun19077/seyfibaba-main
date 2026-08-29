<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionLedger;
use App\Models\Setting;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin-api');
    }

    public function settings()
    {
        $setting = Setting::query()->first();

        return response()->json([
            'default_commission_rate' => (float) ($setting?->default_commission_rate ?? 0),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'default_commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $setting = Setting::query()->firstOrFail();
        $setting->default_commission_rate = $validated['default_commission_rate'];
        $setting->save();

        return response()->json([
            'message' => 'Global commission rate updated successfully',
            'default_commission_rate' => (float) $setting->default_commission_rate,
        ]);
    }

    public function vendors(Request $request)
    {
        $setting = Setting::query()->first();
        $perPage = max(1, min((int) $request->get('per_page', 20), 100));

        $vendors = Vendor::query()
            ->with('user:id,name,email')
            ->orderBy('shop_name', 'asc')
            ->paginate($perPage)
            ->through(function (Vendor $vendor) use ($setting) {
                return [
                    'id' => $vendor->id,
                    'shop_name' => $vendor->shop_name,
                    'user' => $vendor->user,
                    'commission_rate' => $vendor->commission_rate !== null ? (float) $vendor->commission_rate : null,
                    'effective_commission_rate' => (float) $vendor->getEffectiveCommissionRate(),
                    'uses_global_rate' => $vendor->commission_rate === null,
                    'global_default_commission_rate' => (float) ($setting?->default_commission_rate ?? 0),
                ];
            });

        return response()->json(['vendors' => $vendors]);
    }

    public function updateVendorRate(Request $request, $id)
    {
        $validated = $request->validate([
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $vendor = Vendor::query()->findOrFail($id);
        $vendor->commission_rate = $validated['commission_rate'];
        $vendor->save();

        return response()->json([
            'message' => 'Vendor commission rate updated successfully',
            'vendor' => [
                'id' => $vendor->id,
                'commission_rate' => $vendor->commission_rate !== null ? (float) $vendor->commission_rate : null,
                'effective_commission_rate' => (float) $vendor->getEffectiveCommissionRate(),
            ],
        ]);
    }

    public function resetVendorRate($id)
    {
        $vendor = Vendor::query()->findOrFail($id);
        $vendor->commission_rate = null;
        $vendor->save();

        return response()->json([
            'message' => 'Vendor commission rate reset to global default',
            'vendor' => [
                'id' => $vendor->id,
                'commission_rate' => null,
                'effective_commission_rate' => (float) $vendor->getEffectiveCommissionRate(),
            ],
        ]);
    }

    public function report(Request $request)
    {
        $sellerId = $request->integer('seller_id');

        $ledgerQuery = CommissionLedger::query()
            ->when($sellerId, fn ($query) => $query->where('seller_id', $sellerId));

        $summary = (clone $ledgerQuery)
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as total_gross')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as total_commission')
            ->selectRaw('COALESCE(SUM(seller_net_amount), 0) as total_seller_net')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN seller_net_amount ELSE 0 END), 0) as pending_seller_net")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'settled' THEN seller_net_amount ELSE 0 END), 0) as settled_seller_net")
            ->first();

        $sellerBreakdown = CommissionLedger::query()
            ->select('seller_id')
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as total_gross')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as total_commission')
            ->selectRaw('COALESCE(SUM(seller_net_amount), 0) as total_seller_net')
            ->with('vendor:id,shop_name,user_id')
            ->when($sellerId, fn ($query) => $query->where('seller_id', $sellerId))
            ->groupBy('seller_id')
            ->orderByDesc(DB::raw('COALESCE(SUM(commission_amount), 0)'))
            ->get()
            ->map(function (CommissionLedger $ledger) {
                return [
                    'seller_id' => $ledger->seller_id,
                    'shop_name' => $ledger->vendor?->shop_name,
                    'total_gross' => (float) $ledger->total_gross,
                    'total_commission' => (float) $ledger->total_commission,
                    'total_seller_net' => (float) $ledger->total_seller_net,
                ];
            });

        return response()->json([
            'summary' => [
                'total_gross' => (float) ($summary->total_gross ?? 0),
                'total_commission' => (float) ($summary->total_commission ?? 0),
                'total_seller_net' => (float) ($summary->total_seller_net ?? 0),
                'pending_seller_net' => (float) ($summary->pending_seller_net ?? 0),
                'settled_seller_net' => (float) ($summary->settled_seller_net ?? 0),
            ],
            'seller_breakdown' => $sellerBreakdown,
        ]);
    }

    public function ledger(Request $request)
    {
        $perPage = max(1, min((int) $request->get('per_page', 20), 100));
        $status = $request->get('status');
        $sellerId = $request->integer('seller_id');

        $ledger = CommissionLedger::query()
            ->with([
                'order:id,order_id,payment_status,order_status',
                'orderProduct:id,order_id,product_name,qty,unit_price,seller_id',
                'vendor:id,shop_name',
            ])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($sellerId, fn ($query) => $query->where('seller_id', $sellerId))
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json(['ledger' => $ledger]);
    }
}
