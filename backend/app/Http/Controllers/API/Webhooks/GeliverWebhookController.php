<?php

namespace App\Http\Controllers\API\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\CargoShipment;
use App\Models\OrderProduct;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GeliverWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        Log::channel('geliver_webhook')->info('Geliver webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'ip' => $request->ip(),
        ]);

        if (! $this->verifyWebhook($request)) {
            Log::channel('geliver_webhook')->warning('Webhook verification failed', ['ip' => $request->ip()]);
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $payload = $request->all();
        if (($payload['event'] ?? null) !== 'TRACK_UPDATED') {
            return response()->json(['status' => 'ok']);
        }

        $shipmentData = $payload['data'] ?? [];
        $geliverShipmentId = $shipmentData['id'] ?? null;
        $trackingNumber = $shipmentData['trackingNumber'] ?? null;
        $trackingUrl = $shipmentData['trackingUrl'] ?? null;
        $trackingStatus = $shipmentData['trackingStatus'] ?? [];
        $trackingStatusCode = $trackingStatus['trackingStatusCode'] ?? null;

        if (! $geliverShipmentId) {
            return response()->json(['status' => 'error', 'message' => 'Missing shipment ID'], 400);
        }

        $cargoShipment = CargoShipment::query()->where('geliver_shipment_id', $geliverShipmentId)->first();
        if (! $cargoShipment && $trackingNumber) {
            $cargoShipment = CargoShipment::query()->where('tracking_number', $trackingNumber)->first();
        }

        if (! $cargoShipment) {
            Log::channel('geliver_webhook')->warning('CargoShipment not found', [
                'geliver_shipment_id' => $geliverShipmentId,
                'tracking_number' => $trackingNumber,
            ]);
            return response()->json(['status' => 'ok']);
        }

        $oldStatus = $cargoShipment->status;
        $newStatus = $this->mapGeliverStatus($trackingStatusCode);
        $rawResponse = $cargoShipment->raw_response ?? [];
        $rawResponse['latest_webhook'] = [
            'received_at' => now()->toIso8601String(),
            'tracking_status_code' => $trackingStatusCode,
            'tracking_url' => $trackingUrl,
            'full_payload' => $payload,
        ];

        $cargoShipment->update([
            'status' => $newStatus,
            'tracking_number' => $cargoShipment->tracking_number ?: $trackingNumber,
            'tracking_url' => $trackingUrl ?: $cargoShipment->tracking_url,
            'raw_response' => $rawResponse,
        ]);

        if ($cargoShipment->order && $oldStatus !== $newStatus) {
            // Satır bazlı güncelleme: kargo durumunu sadece ilgili satıcıya uygula
            if ($cargoShipment->seller_id) {
                $orderProducts = OrderProduct::query()
                    ->where('order_id', $cargoShipment->order_id)
                    ->where('seller_id', $cargoShipment->seller_id)
                    ->get();

                if ($newStatus === 'shipped') {
                    foreach ($orderProducts as $op) {
                        if ((int) $op->seller_status < 2) {
                            $op->seller_status = 2; // kargoya verildi
                        }
                        $op->shipped_at = $op->shipped_at ?: now();
                        $op->save();
                    }
                    \App\Support\OrderFulfillmentSync::sync($cargoShipment->order);
                }

                if ($newStatus === 'delivered') {
                    foreach ($orderProducts as $op) {
                        $op->delivered_at = $op->delivered_at ?: now();
                        $op->save();
                    }

                    // Sipariş seviyesinde "Teslim Edildi" — tüm satırlar teslim olduysa
                    $allDelivered = OrderProduct::query()
                        ->where('order_id', $cargoShipment->order_id)
                        ->whereNull('delivered_at')
                        ->count() === 0;

                    if ($allDelivered && (int) $cargoShipment->order->order_status < 2) {
                        $cargoShipment->order->order_status = 2;
                        $cargoShipment->order->order_delivered_date = date('Y-m-d');
                        $cargoShipment->order->save();
                    }
                }
            } else {
                // Geriye uyumluluk: seller_id yoksa sadece order-level işaretleme
                if ($newStatus === 'delivered') {
                    $cargoShipment->order->order_status = 2;
                    $cargoShipment->order->order_delivered_date = date('Y-m-d');
                    $cargoShipment->order->save();
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function verifyWebhook(Request $request): bool
    {
        $setting = Setting::query()->first();
        $expectedHeaderName = trim((string) ($setting?->geliver_webhook_header_name ?? ''));
        $expectedHeaderValue = trim((string) ($setting?->geliver_webhook_header_secret ?? ''));

        if (! $expectedHeaderName) {
            Log::channel('geliver_webhook')->error('Geliver webhook rejected: verification header not configured');
            return false;
        }

        if (! $request->hasHeader($expectedHeaderName)) {
            return false;
        }

        if ($expectedHeaderValue !== '') {
            return hash_equals($expectedHeaderValue, (string) $request->header($expectedHeaderName));
        }

        return true;
    }

    private function mapGeliverStatus(?string $statusCode): string
    {
        $code = mb_strtolower($statusCode ?? '', 'UTF-8');

        if (str_contains($code, 'delivered') || $code === 'delivery') {
            return 'delivered';
        }
        if (str_contains($code, 'cancel') || str_contains($code, 'return') || str_contains($code, 'exception')) {
            return 'cancelled';
        }
        if (str_contains($code, 'transit') || str_contains($code, 'in_transit')
            || str_contains($code, 'out_for_delivery') || str_contains($code, 'hub')
            || str_contains($code, 'transfer') || str_contains($code, 'on_the_way')) {
            return 'in_transit';
        }
        if (str_contains($code, 'picked_up') || str_contains($code, 'pickup')
            || str_contains($code, 'shipped') || str_contains($code, 'accepted')) {
            return 'shipped';
        }
        if (str_contains($code, 'pending') || str_contains($code, 'info')
            || str_contains($code, 'created') || str_contains($code, 'label')) {
            return 'pending';
        }

        return 'in_transit';
    }
}
