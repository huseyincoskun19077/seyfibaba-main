<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\CommissionLedger;
use App\Models\SellerWithdraw;
use App\Models\Vendor;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
     * Satıcı bakiye dökümü: İyzico havuzu vs havale çekilebilir tutar.
     */
    public function getSellerBalanceBreakdown(int $sellerId): array
    {
        $holdDays = app(PayoutSettingsService::class)->payoutHoldDays();

        // Negatif iade satırları dahil (seller_net_amount > 0 filtresi iadeyi bakiyeden düşürmüyordu).
        $lines = CommissionLedger::query()
            ->from('commission_ledger as cl')
            ->join('orders as o', 'o.id', '=', 'cl.order_id')
            ->leftJoin('order_products as op', 'op.id', '=', 'cl.order_product_id')
            ->where('cl.seller_id', $sellerId)
            ->where('cl.status', 'settled')
            ->where('cl.seller_net_amount', '!=', 0)
            ->select([
                'cl.seller_net_amount',
                'o.payment_method',
                'o.order_status',
                'o.payout_status',
                'o.payout_blocked_at',
                'o.payout_hold_until',
                'op.iyzico_approved_at',
            ])
            ->get();

        $iyzicoPool = 0.0;
        $iyzicoTransferred = 0.0;
        $bankPending = 0.0;
        $bankGrossWithdrawable = 0.0;

        foreach ($lines as $line) {
            $net = (float) $line->seller_net_amount;
            $method = strtolower((string) $line->payment_method);

            if ($method === 'iyzico') {
                if ($line->iyzico_approved_at) {
                    $iyzicoTransferred += $net;
                } elseif ((int) $line->order_status === 3) {
                    $iyzicoPool += $net;
                }
                continue;
            }

            if ($method === 'bankpayment' && (int) $line->order_status === 3) {
                if ($this->ledgerLineBankWithdrawable($line)) {
                    $bankGrossWithdrawable += $net;
                } else {
                    $bankPending += $net;
                }
            }
        }

        $approvedWithdraw = (float) SellerWithdraw::where('seller_id', $sellerId)
            ->where('status', 1)
            ->sum('total_amount');
        $pendingWithdrawRequests = (float) SellerWithdraw::where('seller_id', $sellerId)
            ->where('status', 0)
            ->sum('total_amount');

        $bankWithdrawable = max(0, $bankGrossWithdrawable - $approvedWithdraw - $pendingWithdrawRequests);

        return [
            'payout_hold_days' => $holdDays,
            'iyzico_pool_balance' => round($iyzicoPool, 2),
            'iyzico_transferred_balance' => round($iyzicoTransferred, 2),
            'bank_pending_hold_balance' => round($bankPending, 2),
            'bank_gross_withdrawable' => round($bankGrossWithdrawable, 2),
            'bank_withdrawable_balance' => round($bankWithdrawable, 2),
            'withdrawable_balance' => round($bankWithdrawable, 2),
            'withdraw_request_allowed' => $bankWithdrawable > 0.009,
            'total_in_platform' => round($iyzicoPool + $bankPending + $bankGrossWithdrawable, 2),
            'approved_withdraw_total' => round($approvedWithdraw, 2),
            'pending_withdraw_total' => round($pendingWithdrawRequests, 2),
            'channel_note' => 'Kredi kartı (İyzico) ödemeleri çekim talebi ile alınamaz. Bekleme süresi dolduğunda İyzico üzerinden satıcı hesabınıza otomatik aktarılır. Havale siparişlerinde çekim talebi kullanılır.',
        ];
    }

    protected function ledgerLineBankWithdrawable(object $line): bool
    {
        if ($line->payout_blocked_at) {
            return false;
        }

        if ($line->payout_hold_until && now()->lt(Carbon::parse($line->payout_hold_until))) {
            return false;
        }

        return ($line->payout_status ?? 'pending') === 'completed';
    }

    /**
     * Get the withdrawable balance for a seller (yalnızca havale kanalı).
     */
    public function getSellerBalance(int $sellerId): float
    {
        return (float) $this->getSellerBalanceBreakdown($sellerId)['bank_withdrawable_balance'];
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
        $breakdown = $this->getSellerBalanceBreakdown($sellerId);
        $available = (float) $breakdown['bank_withdrawable_balance'];
        if ((int) $withdraw->status === 0) {
            $available += (float) $withdraw->total_amount;
        }

        return round((float) $withdraw->total_amount, 2) <= round($available, 2);
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

        $breakdown = $this->getSellerBalanceBreakdown($sellerId);

        return array_merge([
            'pending_gross' => $pendingGross,
            'pending_commission' => $pendingCommission,
            'pending_net' => $pendingNet,
            'settled_gross' => $settledGross,
            'settled_commission' => $settledCommission,
            'settled_net' => $settledNet,
            'approved_withdraw_total' => $approvedWithdraw,
            'pending_withdraw_total' => $pendingWithdrawRequests,
        ], $breakdown);
    }

    /**
     * Satıcıya yansıyacak iade etkisi (komisyon sonrası net).
     * Müşteriye giden refund_amount değil; ürün brütü (kupon sonrası) üzerinden platform komisyonu düşülür.
     */
    public function calculateReturnImpact(\App\Models\ReturnRequest $returnRequest): array
    {
        $orderProduct = $returnRequest->orderProduct;
        if (! $orderProduct) {
            return [
                'gross' => 0.0,
                'commission_rate' => 0.0,
                'commission' => 0.0,
                'seller_net' => 0.0,
            ];
        }

        $rate = (float) ($orderProduct->commission_rate ?? 0);
        if ($rate <= 0 && $returnRequest->seller_id) {
            $vendor = Vendor::query()->find($returnRequest->seller_id);
            $rate = $vendor ? (float) $vendor->getEffectiveCommissionRate() : 10.0;
        }

        $productRefund = round((float) $orderProduct->unit_price * (int) $returnRequest->qty, 2);
        $order = $returnRequest->order ?: $orderProduct->order;
        if ($order) {
            $order->loadMissing('orderProducts');
            $calc = $order->suggestedReturnRefund(
                $orderProduct,
                (int) $returnRequest->qty,
                $returnRequest->id
            );
            $productRefund = round((float) $calc['product_refund'], 2);
        }

        $commission = round($productRefund * ($rate / 100), 2);
        $net = round($productRefund - $commission, 2);

        return [
            'gross' => $productRefund,
            'commission_rate' => $rate,
            'commission' => $commission,
            'seller_net' => $net,
        ];
    }

    /**
     * Tamamlanan iadelerin satıcı istatistiklerinden düşülecek net/adet özeti.
     *
     * @return array{net_adjustment: float, qty: int}
     */
    public function sellerRefundedStats(int $sellerId, $from = null, $to = null): array
    {
        $ledger = CommissionLedger::query()
            ->where('seller_id', $sellerId)
            ->where('status', 'settled')
            ->where('seller_net_amount', '<', 0);

        if ($from) {
            $ledger->where('settled_at', '>=', $from);
        }
        if ($to) {
            $ledger->where('settled_at', '<=', $to);
        }

        $netAdjustment = (float) $ledger->sum('seller_net_amount');

        $returns = \App\Models\ReturnRequest::query()
            ->where('seller_id', $sellerId)
            ->where('status', \App\Models\ReturnRequest::STATUS_REFUNDED);

        if ($from) {
            $returns->where(function ($query) use ($from) {
                $query->where('refunded_at', '>=', $from)
                    ->orWhere(function ($inner) use ($from) {
                        $inner->whereNull('refunded_at')->where('updated_at', '>=', $from);
                    });
            });
        }
        if ($to) {
            $returns->where(function ($query) use ($to) {
                $query->where('refunded_at', '<=', $to)
                    ->orWhere(function ($inner) use ($to) {
                        $inner->whereNull('refunded_at')->where('updated_at', '<=', $to);
                    });
            });
        }

        return [
            'net_adjustment' => round($netAdjustment, 2),
            'qty' => (int) $returns->sum('qty'),
        ];
    }

    /**
     * Satıcı dönem özeti: satış adedi/sipariş vs gerçekleşen kazanç (İyzico/admin onayı) vs bekleyen.
     *
     * @return array{
     *   order_count: int,
     *   sold_qty: int,
     *   earned: float,
     *   pending_earning: float,
     *   refunded_qty: int,
     *   cancelled_order_count: int
     * }
     */
    public function sellerPeriodStats(int $sellerId, $from = null, $to = null): array
    {
        $orderBase = Order::query()
            ->forSeller($sellerId)
            ->where('payment_status', 1);

        $activeOrders = (clone $orderBase)
            ->whereIn('order_status', [1, 2, 3]);
        if ($from) {
            $activeOrders->where('created_at', '>=', $from);
        }
        if ($to) {
            $activeOrders->where('created_at', '<=', $to);
        }
        $orderCount = (int) $activeOrders->count();

        $cancelledOrders = (clone $orderBase)->where('order_status', 4);
        if ($from) {
            $cancelledOrders->where('created_at', '>=', $from);
        }
        if ($to) {
            $cancelledOrders->where('created_at', '<=', $to);
        }
        $cancelledOrderCount = (int) $cancelledOrders->count();

        $soldQtyQuery = OrderProduct::query()
            ->where('seller_id', $sellerId)
            ->where('seller_status', '!=', 4)
            ->whereHas('order', function ($q) use ($from, $to) {
                $q->where('payment_status', 1)
                    ->whereIn('order_status', [1, 2, 3]);
                if ($from) {
                    $q->where('created_at', '>=', $from);
                }
                if ($to) {
                    $q->where('created_at', '<=', $to);
                }
            });
        $soldQty = (int) $soldQtyQuery->sum('qty');

        $refundStats = $this->sellerRefundedStats($sellerId, $from, $to);
        $soldQty = max(0, $soldQty - (int) $refundStats['qty']);

        // Gerçekleşen kazanç: İyzico item approve veya havale payout paid
        $earnedQuery = OrderProduct::query()
            ->where('seller_id', $sellerId)
            ->where(function ($q) use ($from, $to) {
                $q->where(function ($iyz) use ($from, $to) {
                    $iyz->whereNotNull('iyzico_approved_at');
                    if ($from) {
                        $iyz->where('iyzico_approved_at', '>=', $from);
                    }
                    if ($to) {
                        $iyz->where('iyzico_approved_at', '<=', $to);
                    }
                })->orWhere(function ($bank) use ($from, $to) {
                    $bank->where('payout_status', 'paid')
                        ->whereHas('order', fn ($o) => $o->where('payment_method', 'bankpayment'));
                    if ($from) {
                        $bank->where(function ($inner) use ($from) {
                            $inner->where('payout_processed_at', '>=', $from)
                                ->orWhere(function ($fallback) use ($from) {
                                    $fallback->whereNull('payout_processed_at')
                                        ->where('updated_at', '>=', $from);
                                });
                        });
                    }
                    if ($to) {
                        $bank->where(function ($inner) use ($to) {
                            $inner->where('payout_processed_at', '<=', $to)
                                ->orWhere(function ($fallback) use ($to) {
                                    $fallback->whereNull('payout_processed_at')
                                        ->where('updated_at', '<=', $to);
                                });
                        });
                    }
                });
            });
        $earned = (float) $earnedQuery->sum(\DB::raw(
            'CASE WHEN seller_net_amount > 0 THEN seller_net_amount ELSE unit_price * qty END'
        ));
        $earned = max(0, round($earned + (float) $refundStats['net_adjustment'], 2));

        // Bekleyen: tamamlanmış, onaylanmamış, iptal/iade değil
        $pendingLines = OrderProduct::query()
            ->with('order')
            ->where('seller_id', $sellerId)
            ->where('seller_status', '!=', 4)
            ->whereNull('iyzico_approved_at')
            ->where(function ($q) {
                $q->whereNull('payout_status')
                    ->orWhereNotIn('payout_status', ['paid', 'blocked']);
            })
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 1)
                    ->where('order_status', 3)
                    ->where(function ($inner) {
                        $inner->whereNull('payout_status')
                            ->orWhereNotIn('payout_status', ['cancelled', 'paid', 'completed']);
                    })
                    ->where(function ($inner) {
                        $inner->whereNull('payout_block_reason')
                            ->orWhere('payout_block_reason', '!=', SellerPayoutService::PAYOUT_BLOCK_FULL_RETURN);
                    });
            })
            ->get();

        $pending = 0.0;
        foreach ($pendingLines as $line) {
            $order = $line->order;
            if ($order && $order->isFullyRefundedForSeller($sellerId)) {
                continue;
            }
            $net = $line->seller_net_amount > 0
                ? (float) $line->seller_net_amount
                : (float) $line->unit_price * (int) $line->qty;
            $pending += $net;
        }

        return [
            'order_count' => $orderCount,
            'sold_qty' => $soldQty,
            'earned' => round(max(0, $earned), 2),
            'pending_earning' => round(max(0, $pending), 2),
            'refunded_qty' => (int) $refundStats['qty'],
            'cancelled_order_count' => $cancelledOrderCount,
        ];
    }

    /**
     * Record a return in the ledger (negative amounts).
     */
    public function recordReturn(\App\Models\ReturnRequest $returnRequest): CommissionLedger
    {
        $note = 'Return Refund for Request #'.$returnRequest->id;
        $existing = CommissionLedger::query()
            ->where('notes', $note)
            ->where('seller_id', $returnRequest->seller_id)
            ->first();
        if ($existing) {
            return $existing;
        }

        $impact = $this->calculateReturnImpact($returnRequest);

        return CommissionLedger::create([
            'order_id' => $returnRequest->order_id,
            'order_product_id' => $returnRequest->order_product_id,
            'seller_id' => $returnRequest->seller_id,
            'gross_amount' => -$impact['gross'],
            'commission_rate' => $impact['commission_rate'],
            'commission_amount' => -$impact['commission'],
            'seller_net_amount' => -$impact['seller_net'],
            'status' => 'settled',
            'settled_at' => now(),
            'notes' => $note,
        ]);
    }
}
