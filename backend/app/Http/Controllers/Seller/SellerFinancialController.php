<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\CommissionLedger;
use App\Models\OrderProduct;
use App\Models\Setting;
use App\Models\Vendor;
use Auth;

class SellerFinancialController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Satıcı finansal özet — IBAN, gelir/gider/komisyon tablosu
     */
    public function index()
    {
        $user = Auth::guard('api')->user();
        $seller = $user->seller;
        $setting = Setting::first();

        // Komisyon ledger kayıtları — sipariş bazlı
        $ledgerEntries = CommissionLedger::with(['order', 'orderProduct'])
            ->where('seller_id', $seller->id)
            ->orderBy('id', 'desc')
            ->get();

        // Özet hesaplamalar
        $totalGross = $ledgerEntries->sum('gross_amount');
        $totalCommission = $ledgerEntries->sum('commission_amount');
        $totalNet = $ledgerEntries->sum('seller_net_amount');
        $settledAmount = $ledgerEntries->where('status', 'settled')->sum('seller_net_amount');
        $pendingAmount = $ledgerEntries->where('status', 'pending')->sum('seller_net_amount');

        // Sipariş bazlı detay tablosu
        $transactions = $ledgerEntries->map(function ($entry) use ($setting) {
            return [
                'id' => $entry->id,
                'order_id' => $entry->order?->order_id ?? '-',
                'order_date' => $entry->created_at?->format('d.m.Y'),
                'product_name' => $entry->orderProduct?->product_name ?? '-',
                'qty' => $entry->orderProduct?->qty ?? 0,
                'gross_amount' => number_format($entry->gross_amount, 2),
                'commission_rate' => $entry->commission_rate . '%',
                'commission_amount' => number_format($entry->commission_amount, 2),
                'net_amount' => number_format($entry->seller_net_amount, 2),
                'status' => $entry->status,
                'status_label' => $entry->status === 'settled' ? 'Ödendi' : 'Beklemede',
            ];
        });

        return response()->json([
            'seller' => [
                'shop_name' => $seller->shop_name,
                'iban' => $seller->iban ?? null,
                'is_verified' => $seller->is_verified,
            ],
            'summary' => [
                'currency' => $setting->currency_icon ?? '₺',
                'total_gross' => number_format($totalGross, 2),
                'total_commission' => number_format($totalCommission, 2),
                'total_net' => number_format($totalNet, 2),
                'settled_amount' => number_format($settledAmount, 2),
                'pending_amount' => number_format($pendingAmount, 2),
                'commission_rate' => $seller->getEffectiveCommissionRate() . '%',
            ],
            'transactions' => $transactions,
        ]);
    }
}
