<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\CommissionLedger;
use App\Models\OrderProduct;
use App\Models\SellerWithdraw;
use App\Services\CommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EarningsController extends Controller
{
    public function __construct(private CommissionService $commissionService)
    {
        $this->middleware('auth:api');
        $this->middleware('checkseller');
    }

    public function summary()
    {
        $seller = Auth::guard('api')->user()->seller;

        $ledgerSummary = CommissionLedger::query()
            ->where('seller_id', $seller->id)
            ->where('status', 'settled')
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as total_gross')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as total_commission')
            ->selectRaw('COALESCE(SUM(seller_net_amount), 0) as total_net')
            ->first();

        $breakdown = $this->commissionService->getSellerBalanceBreakdown($seller->id);

        return response()->json(array_merge([
            'total_gross' => (float) ($ledgerSummary->total_gross ?? 0),
            'total_commission' => (float) ($ledgerSummary->total_commission ?? 0),
            'total_net' => (float) ($ledgerSummary->total_net ?? 0),
            'commission_rate' => (float) $seller->getEffectiveCommissionRate(),
            'total_withdrawn' => (float) $breakdown['approved_withdraw_total'],
            'pending_withdrawals' => (float) $breakdown['pending_withdraw_total'],
        ], $breakdown));
    }

    public function orders(Request $request)
    {
        $seller = Auth::guard('api')->user()->seller;
        $perPage = max(1, min((int) $request->get('per_page', 20), 100));

        $orders = OrderProduct::query()
            ->with([
                'order:id,order_id,payment_status,order_status,created_at,order_completed_date',
            ])
            ->where('seller_id', $seller->id)
            ->whereHas('order', function ($q) {
                $q->paidCompleted();
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(function (OrderProduct $orderProduct) {
                $grossAmount = (float) ($orderProduct->unit_price * $orderProduct->qty);
                $commissionAmount = (float) ($orderProduct->commission_amount ?? 0);
                $netAmount = (float) ($orderProduct->seller_net_amount > 0 ? $orderProduct->seller_net_amount : $grossAmount);

                return [
                    'id' => $orderProduct->id,
                    'order_id' => $orderProduct->order_id,
                    'seller_id' => $orderProduct->seller_id,
                    'product_name' => $orderProduct->product_name,
                    'qty' => (int) $orderProduct->qty,
                    'unit_price' => (float) $orderProduct->unit_price,
                    'gross_amount' => $grossAmount,
                    'commission_rate' => (float) ($orderProduct->commission_rate ?? 0),
                    'commission_amount' => $commissionAmount,
                    'seller_net_amount' => $netAmount,
                    'order' => $orderProduct->order,
                ];
            });

        return response()->json(['orders' => $orders]);
    }
}
