<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CargoShipment;
use App\Models\Order;
use App\Services\GdeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin ve satıcı sipariş kargo (Geliver) uçları — aynı yanıt ve GdeliveryService akışı.
 */
trait HandlesOrderCargo
{
    abstract protected function resolveOrderForCargo(int $orderId): Order;

    /**
     * @return array{type: string, id: int}
     */
    abstract protected function cargoCreatedBy(): array;

    abstract protected function getGdeliveryService(): GdeliveryService;

    public function offers(int $orderId): JsonResponse
    {
        $order = $this->resolveOrderForCargo($orderId);

        try {
            return response()->json([
                'success' => true,
                'data' => $this->getGdeliveryService()->getShipmentOffers($order),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function createShipment(Request $request, int $orderId): JsonResponse
    {
        $order = $this->resolveOrderForCargo($orderId);
        $validated = $request->validate([
            'offer_id' => 'nullable|string',
            'seller_id' => 'nullable|integer',
        ]);

        $by = $this->cargoCreatedBy();

        try {
            $sellerId = $validated['seller_id'] ?? null;

            // Seller panel için seller_id otomatik belirlenir
            if ($by['type'] === 'seller') {
                $sellerId = (int) $by['id'];
            } else {
                // Admin: çok satıcılı siparişte seller_id zorunlu
                $distinctSellerCount = (int) $order->orderProducts()
                    ->where('seller_id', '!=', 0)
                    ->distinct('seller_id')
                    ->count('seller_id');

                if ($distinctSellerCount > 1 && !$sellerId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bu sipariş çok satıcılı. Lütfen seller_id seçerek kargo oluşturun.',
                    ], 422);
                }

                if (!$sellerId && $distinctSellerCount === 1) {
                    $sellerId = (int) $order->orderProducts()
                        ->where('seller_id', '!=', 0)
                        ->value('seller_id');
                }
            }

            $cargo = $this->getGdeliveryService()->createShipment(
                order: $order,
                offerId: $validated['offer_id'] ?? null,
                sellerId: $sellerId ?: null,
                createdByType: $by['type'],
                createdById: (int) $by['id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Kargo başarıyla oluşturuldu.',
                'data' => $this->formatCargoPayload($cargo),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(int $orderId): JsonResponse
    {
        $this->resolveOrderForCargo($orderId);

        $cargoQuery = CargoShipment::query()->where('order_id', $orderId);
        $by = $this->cargoCreatedBy();
        if ($by['type'] === 'seller') {
            $cargoQuery->where('seller_id', (int) $by['id']);
        }

        $cargo = $cargoQuery
            ->whereNotIn('status', ['cancelled'])
            ->latest()
            ->first();

        if (! $cargo) {
            return response()->json([
                'success' => false,
                'message' => 'Kargo kaydı bulunamadı.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatCargoPayload($cargo),
        ]);
    }

    public function cancel(int $orderId): JsonResponse
    {
        $this->resolveOrderForCargo($orderId);

        $cargoQuery = CargoShipment::query()->where('order_id', $orderId);
        $by = $this->cargoCreatedBy();
        if ($by['type'] === 'seller') {
            $cargoQuery->where('seller_id', (int) $by['id']);
        }

        $cargo = $cargoQuery
            ->whereNotIn('status', ['cancelled'])
            ->latest()
            ->firstOrFail();

        try {
            $this->getGdeliveryService()->cancelShipment($cargo);

            return response()->json([
                'success' => true,
                'message' => 'Kargo iptal edildi.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function formatCargoPayload(CargoShipment $cargo): array
    {
        return [
            'id' => $cargo->id,
            'order_id' => $cargo->order_id,
            'carrier_name' => $cargo->carrier_name,
            'barcode' => $cargo->barcode,
            'tracking_number' => $cargo->tracking_number,
            'tracking_url' => $cargo->tracking_url,
            'label_url' => $cargo->label_url,
            'status' => $cargo->status,
            'created_by_type' => $cargo->created_by_type,
            'created_at' => optional($cargo->created_at)->toDateTimeString(),
        ];
    }
}
