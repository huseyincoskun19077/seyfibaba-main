<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\CommissionLedger;
use App\Models\SellerWithdraw;
use App\Models\Vendor;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class CommissionService
{
    /**
     * Calculate and record commission for a single order product.
     */
    public function recordCommission(OrderProduct $orderProduct, Order $order): ?CommissionLedger
    {
        // Skip if admin product (seller_id = 0)
        if ($orderProduct->seller_id == 0) {
            $orderProduct->update([
                'commission_rate' => 0,
                'commission_amount' => 0,
                'seller_net_amount' => $orderProduct->unit_price * $orderProduct->qty
            ]);
            return null;
        }

        $vendorResource = Vendor::where('id', $orderProduct->seller_id)->first();
        if (!$vendorResource) {
            Log::error("Vendor not found for order product {$orderProduct->id}");
            return null;
        }

        $rate = $vendorResource->getEffectiveCommissionRate();
        $grossAmount = $orderProduct->unit_price * $orderProduct->qty;
        $commissionAmount = $grossAmount * ($rate / 100);
        $netAmount = $grossAmount - $commissionAmount;

        // Update OrderProduct snapshot
        $orderProduct->update([
            'commission_rate' => $rate,
            'commission_amount' => $commissionAmount,
            'seller_net_amount' => $netAmount
        ]);

        // Create Ledger entry
        return CommissionLedger::create([
            'order_id' => $order->id,
            'order_product_id' => $orderProduct->id,
            'seller_id' => $vendorResource->id,
            'gross_amount' => $grossAmount,
            'commission_rate' => $rate,
            'commission_amount' => $commissionAmount,
            'seller_net_amount' => $netAmount,
            'status' => 'pending'
        ]);
    }

    /**
     * Mark commissions as settled when an order is completed.
     */
    public function settleCommissions(Order $order): void
    {
        CommissionLedger::where('order_id', $order->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'settled',
                'settled_at' => now()
            ]);
    }

    /**
     * Get the withdrawable balance for a seller.
     * Balance = settled net − approved withdrawals − pending withdrawal requests (status 0).
     */
    public function getSellerBalance(int $sellerId): float
    {
        $totalNet = CommissionLedger::where('seller_id', $sellerId)
            ->where('status', 'settled')
            ->sum('seller_net_amount');

        $totalApproved = SellerWithdraw::where('seller_id', $sellerId)
            ->where('status', 1)
            ->sum('total_amount');

        $totalPendingRequests = SellerWithdraw::where('seller_id', $sellerId)
            ->where('status', 0)
            ->sum('total_amount');

        return max(0, (float) $totalNet - (float) $totalApproved - (float) $totalPendingRequests);
    }

    /**
     * Admin onayı: bu talep hâlâ deftere göre güvenli mi? (sipariş iadesi vb. sonrası)
     */
    public function canApproveSellerWithdraw(SellerWithdraw $withdraw): bool
    {
        if ((int) $withdraw->status !== 0) {
            return false;
        }

        $sellerId = $withdraw->seller_id;
        $settled = (float) CommissionLedger::where('seller_id', $sellerId)
            ->where('status', 'settled')
            ->sum('seller_net_amount');
        $approved = (float) SellerWithdraw::where('seller_id', $sellerId)
            ->where('status', 1)
            ->sum('total_amount');
        $pendingOther = (float) SellerWithdraw::where('seller_id', $sellerId)
            ->where('status', 0)
            ->where('id', '!=', $withdraw->id)
            ->sum('total_amount');

        $ceiling = $settled - $approved - $pendingOther;

        return round((float) $withdraw->total_amount, 2) <= round($ceiling, 2);
    }

    /**
     * Satıcı paneli: komisyon, net ve çekilebilir tutar özeti.
     * Not: Kargo ücreti sipariş satırında değil; Iyzico sepetinde ayrı kalem olarak ana üye hesabına gider.
     */
    public function getSellerEarningsSummary(int $sellerId): array
    {
        $pending = CommissionLedger::where('seller_id', $sellerId)->where('status', 'pending');
        $settled = CommissionLedger::where('seller_id', $sellerId)->where('status', 'settled');

        $pendingNet = (float) $pending->sum('seller_net_amount');
        $pendingGross = (float) $pending->sum('gross_amount');
        $pendingCommission = (float) $pending->sum('commission_amount');

        $settledNet = (float) $settled->sum('seller_net_amount');
        $settledGross = (float) $settled->sum('gross_amount');
        $settledCommission = (float) $settled->sum('commission_amount');

        $approvedWithdraw = (float) SellerWithdraw::where('seller_id', $sellerId)->where('status', 1)->sum('total_amount');
        $pendingWithdrawRequests = (float) SellerWithdraw::where('seller_id', $sellerId)->where('status', 0)->sum('total_amount');

        return [
            'pending_gross' => $pendingGross,
            'pending_commission' => $pendingCommission,
            'pending_net' => $pendingNet,
            'settled_gross' => $settledGross,
            'settled_commission' => $settledCommission,
            'settled_net' => $settledNet,
            'approved_withdraw_total' => $approvedWithdraw,
            'pending_withdraw_total' => $pendingWithdrawRequests,
            'withdrawable_balance' => $this->getSellerBalance($sellerId),
        ];
    }

    /**
     * Record a return in the ledger (negative amounts).
     */
    public function recordReturn(\App\Models\ReturnRequest $returnRequest): CommissionLedger
    {
        $orderProduct = $returnRequest->orderProduct;
        
        $rate = $orderProduct->commission_rate;
        $order = $returnRequest->order ?: $orderProduct->order;
        $productRefund = (float) $orderProduct->unit_price * (int) $returnRequest->qty;
        if ($order) {
            $order->loadMissing('orderProducts');
            $calc = $order->suggestedReturnRefund(
                $orderProduct,
                (int) $returnRequest->qty,
                $returnRequest->id
            );
            $productRefund = (float) $calc['product_refund'];
        }
        $refundGross = $productRefund;
        $refundCommission = $refundGross * ($rate / 100);
        $refundNet = $refundGross - $refundCommission;

        // Create negative Ledger entry
        return CommissionLedger::create([
            'order_id' => $returnRequest->order_id,
            'order_product_id' => $returnRequest->order_product_id,
            'seller_id' => $returnRequest->seller_id,
            'gross_amount' => -$refundGross,
            'commission_rate' => $rate,
            'commission_amount' => -$refundCommission,
            'seller_net_amount' => -$refundNet,
            'status' => 'settled', // Settlement is immediate for returns
            'settled_at' => now(),
            'notes' => 'Return Refund for Request #' . $returnRequest->id
        ]);
    }
}
