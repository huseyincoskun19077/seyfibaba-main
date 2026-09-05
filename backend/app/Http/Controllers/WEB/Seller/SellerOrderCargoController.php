<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Concerns\HandlesOrderCargo;
use App\Http\Controllers\Controller;
use App\Models\CargoShipment;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Services\GdeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerOrderCargoController extends Controller
{
    use HandlesOrderCargo;

    public function __construct(private GdeliveryService $gdeliveryService)
    {
        $this->middleware('auth:web');
    }

    protected function getGdeliveryService(): GdeliveryService
    {
        return $this->gdeliveryService;
    }

    protected function resolveOrderForCargo(int $orderId): Order
    {
        $seller = Auth::guard('web')->user()->seller;

        $order = Order::query()
            ->where('id', $orderId)
            ->whereHas('orderProducts', function ($query) use ($seller) {
                $query->where('seller_id', $seller->id);
            })
            ->first();

        if (! $order) {
            abort(403, 'Bu siparişe erişim yetkiniz yok.');
        }

        return $order;
    }

    protected function cargoCreatedBy(): array
    {
        $seller = Auth::guard('web')->user()->seller;

        return [
            'type' => 'seller',
            'id' => (int) $seller->id,
        ];
    }

    /**
     * Manuel kargo: satıcı kendi anlaşmalı kargosunu girer.
     * Geliver kullanmayan satıcılar için.
     */
    public function manualShip(Request $request, int $orderId)
    {
        $order = $this->resolveOrderForCargo($orderId);
        $seller = Auth::guard('web')->user()->seller;

        $validated = $request->validate([
            'carrier_name' => ['required', 'string', 'max:255'],
            'tracking_number' => ['required', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:2000'],
        ]);

        $cargo = CargoShipment::query()
            ->where('order_id', $order->id)
            ->where('seller_id', $seller->id)
            ->whereNotIn('status', ['cancelled'])
            ->latest()
            ->first();

        if (! $cargo) {
            $cargo = CargoShipment::create([
                'order_id' => $order->id,
                'seller_id' => $seller->id,
                'carrier_name' => $validated['carrier_name'],
                'tracking_number' => $validated['tracking_number'],
                'tracking_url' => $validated['tracking_url'] ?? null,
                'status' => 'shipped',
                'created_by_type' => 'seller',
                'created_by_id' => (int) $seller->id,
                'raw_response' => [
                    'manual' => true,
                    'created_at' => now()->toIso8601String(),
                ],
            ]);
        } else {
            $cargo->update([
                'carrier_name' => $validated['carrier_name'],
                'tracking_number' => $validated['tracking_number'],
                'tracking_url' => $validated['tracking_url'] ?? $cargo->tracking_url,
                'status' => $cargo->status === 'delivered' ? 'delivered' : 'shipped',
            ]);
        }

        // Satır bazlı shipped işaretle
        $orderProducts = OrderProduct::query()
            ->where('order_id', $order->id)
            ->where('seller_id', $seller->id)
            ->get();

        foreach ($orderProducts as $op) {
            if ((int) $op->seller_status < 2) {
                $op->seller_status = 2;
            }
            $op->shipped_at = $op->shipped_at ?: now();
            $op->save();
        }

        \App\Support\OrderFulfillmentSync::sync($order);

        return redirect()->back()->with([
            'messege' => 'Manuel kargo bilgisi kaydedildi ve kargoya verildi olarak işaretlendi.',
            'alert-type' => 'success',
        ]);
    }

    public function manualDelivered(Request $request, int $orderId)
    {
        $order = $this->resolveOrderForCargo($orderId);
        $seller = Auth::guard('web')->user()->seller;

        $cargo = CargoShipment::query()
            ->where('order_id', $order->id)
            ->where('seller_id', $seller->id)
            ->whereNotIn('status', ['cancelled'])
            ->latest()
            ->first();

        if ($cargo) {
            $cargo->update(['status' => 'delivered']);
        }

        $orderProducts = OrderProduct::query()
            ->where('order_id', $order->id)
            ->where('seller_id', $seller->id)
            ->get();

        foreach ($orderProducts as $op) {
            $op->delivered_at = $op->delivered_at ?: now();
            $op->save();
        }

        // Sipariş seviyesinde "Teslim Edildi" — tüm satırlar teslim olduysa
        $allDelivered = OrderProduct::query()
            ->where('order_id', $order->id)
            ->whereNull('delivered_at')
            ->count() === 0;

        if ($allDelivered && (int) $order->order_status < 2) {
            $order->order_status = 2;
            $order->order_delivered_date = date('Y-m-d');
            $order->save();
        }

        return redirect()->back()->with([
            'messege' => 'Satır(lar) teslim edildi olarak işaretlendi.',
            'alert-type' => 'success',
        ]);
    }
}
