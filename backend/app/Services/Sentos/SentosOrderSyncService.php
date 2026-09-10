<?php

namespace App\Services\Sentos;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Vendor;
use App\Models\VendorSentosOrderMap;
use App\Models\VendorSentosProductMap;
use App\Models\VendorSentosSetting;
use Illuminate\Support\Facades\Log;

/**
 * Pushes paid Seyfibaba orders to Sentos for opted-in vendors only.
 */
class SentosOrderSyncService
{
    public function __construct(private SentosApiClient $client) {}

    public function pushPaidOrder(Order $order): void
    {
        $order->loadMissing(['orderProducts.product', 'orderAddress', 'user']);

        $sellerIds = $order->orderProducts
            ->pluck('seller_id')
            ->filter()
            ->unique()
            ->values();

        foreach ($sellerIds as $sellerId) {
            try {
                $this->pushForVendor($order, (int) $sellerId);
            } catch (\Throwable $e) {
                Log::warning('Sentos order push failed', [
                    'order_id' => $order->id,
                    'vendor_id' => $sellerId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function pushForVendor(Order $order, int $vendorId): ?VendorSentosOrderMap
    {
        $setting = VendorSentosSetting::query()->where('vendor_id', $vendorId)->first();
        if (! $setting || ! $setting->is_enabled || ! $setting->push_orders || ! $setting->hasCredentials()) {
            return null;
        }

        $existing = VendorSentosOrderMap::query()
            ->where('vendor_id', $vendorId)
            ->where('order_id', $order->id)
            ->first();

        if ($existing && $existing->sentos_order_id) {
            return $existing;
        }

        $lines = $order->orderProducts->where('seller_id', $vendorId)->values();
        if ($lines->isEmpty()) {
            return null;
        }

        $channelId = $this->resolveChannelId($setting);
        $warehouseId = $this->resolveWarehouseId($setting);
        $payload = $this->buildOrderPayload($order, $vendorId, $lines, $channelId, $warehouseId);

        $response = $this->client->createOrder($setting, $payload);
        if (! $response->successful()) {
            $message = 'HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 400);
            VendorSentosOrderMap::query()->updateOrCreate(
                ['vendor_id' => $vendorId, 'order_id' => $order->id],
                [
                    'sentos_external_order_id' => $payload['order_id'],
                    'last_sync_status' => 'failed',
                    'last_sync_message' => $message,
                    'last_synced_at' => now(),
                ]
            );
            throw new \RuntimeException($message);
        }

        $json = $response->json();
        $sentosOrder = is_array($json['order'] ?? null) ? $json['order'] : [];
        $sentosId = $sentosOrder['id'] ?? ($json['id'] ?? null);

        return VendorSentosOrderMap::query()->updateOrCreate(
            ['vendor_id' => $vendorId, 'order_id' => $order->id],
            [
                'sentos_external_order_id' => $payload['order_id'],
                'sentos_order_id' => $sentosId ? (int) $sentosId : null,
                'sentos_order_code' => isset($sentosOrder['order_code']) ? (string) $sentosOrder['order_code'] : ($payload['order_code'] ?? null),
                'last_sentos_status' => (int) ($sentosOrder['status'] ?? $payload['status'] ?? 2),
                'last_sync_status' => 'success',
                'last_sync_message' => 'Sipariş Sentos’a aktarıldı.',
                'last_synced_at' => now(),
            ]
        );
    }

    public function markShipped(Order $order, int $vendorId, string $carrier, string $trackingNumber, ?string $trackingUrl = null): void
    {
        $setting = VendorSentosSetting::query()->where('vendor_id', $vendorId)->first();
        if (! $setting || ! $setting->is_enabled || ! $setting->hasCredentials()) {
            return;
        }

        $map = VendorSentosOrderMap::query()
            ->where('vendor_id', $vendorId)
            ->where('order_id', $order->id)
            ->first();

        if (! $map || ! $map->sentos_order_id) {
            // Ensure order exists in Sentos first.
            $map = $this->pushForVendor($order, $vendorId);
        }

        if (! $map || ! $map->sentos_order_id) {
            return;
        }

        $url = $trackingUrl ?: ('https://seyfibaba.com/order/' . $order->order_id);
        $response = $this->client->updateOrderStatus($setting, (int) $map->sentos_order_id, [
            'status' => 5,
            'data' => [
                'cargo_company' => $carrier,
                'tracking_number' => $trackingNumber,
                'tracking_url' => $url,
            ],
        ]);

        $map->last_sentos_status = 5;
        $map->last_synced_at = now();
        if ($response->successful()) {
            $map->last_sync_status = 'success';
            $map->last_sync_message = 'Kargoya verildi olarak Sentos’a işlendi.';
        } else {
            $map->last_sync_status = 'failed';
            $map->last_sync_message = 'Kargo güncellemesi başarısız: HTTP ' . $response->status();
            Log::warning('Sentos ship status failed', [
                'order_id' => $order->id,
                'vendor_id' => $vendorId,
                'body' => mb_substr($response->body(), 0, 400),
            ]);
        }
        $map->save();
    }

    private function resolveChannelId(VendorSentosSetting $setting): int
    {
        if ($setting->channel_id) {
            return (int) $setting->channel_id;
        }

        $channels = $this->client->listSalesChannels($setting);
        foreach ($channels as $channel) {
            $name = mb_strtolower((string) ($channel['name'] ?? ''));
            if (str_contains($name, 'seyfibaba') || str_contains($name, 'pazaryeri')) {
                $setting->channel_id = (int) $channel['id'];
                $setting->save();

                return (int) $channel['id'];
            }
        }

        if ($channels !== []) {
            $setting->channel_id = (int) $channels[0]['id'];
            $setting->save();

            return (int) $channels[0]['id'];
        }

        $created = $this->client->createSalesChannel($setting, [
            'name' => 'Seyfibaba',
            'url' => 'seyfibaba.com',
            'payment_method' => 'online',
            'order_prefix' => 'SF-',
            'note' => 'Seyfibaba pazaryeri siparişleri',
        ]);

        $id = (int) ($created['saleschannel']['id'] ?? $created['id'] ?? 0);
        if ($id <= 0) {
            throw new \RuntimeException('Sentos satış kanalı oluşturulamadı.');
        }

        $setting->channel_id = $id;
        $setting->save();

        return $id;
    }

    private function resolveWarehouseId(VendorSentosSetting $setting): int
    {
        if ($setting->warehouse_id) {
            return (int) $setting->warehouse_id;
        }

        $warehouses = $this->client->listWarehouses($setting);
        if ($warehouses === []) {
            throw new \RuntimeException('Sentos deposu bulunamadı. Panelden depo tanımlayın.');
        }

        $setting->warehouse_id = (int) $warehouses[0]['id'];
        $setting->save();

        return (int) $warehouses[0]['id'];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, OrderProduct>  $lines
     * @return array<string, mixed>
     */
    private function buildOrderPayload(Order $order, int $vendorId, $lines, int $channelId, int $warehouseId): array
    {
        $address = $order->orderAddress;
        $user = $order->user;
        $phone = $this->normalizePhone(
            (string) ($address?->shipping_phone ?? $address?->billing_phone ?? $user?->phone ?? '905550000000')
        );
        $email = (string) ($address?->shipping_email ?? $address?->billing_email ?? $user?->email ?? 'noreply@seyfibaba.com');
        $name = (string) ($address?->shipping_name ?? $address?->billing_name ?? $user?->name ?? 'Musteri');
        $address = $address ?: (object) [];

        $sellerCount = $order->orderProducts->pluck('seller_id')->unique()->count();
        $shippingTotal = $sellerCount === 1 ? (float) ($order->shipping_cost ?? 0) : 0.0;

        $payloadLines = [];
        foreach ($lines as $line) {
            $map = VendorSentosProductMap::query()
                ->where('vendor_id', $vendorId)
                ->where('product_id', $line->product_id)
                ->first();

            $sku = $line->product?->sku ?: ('SF-P-' . $line->product_id);
            $item = [
                'warehouse_id' => $warehouseId,
                'quantity' => max(1, (int) $line->qty),
                'price' => (float) $line->unit_price,
                'name' => (string) ($line->product_name ?: $line->product?->name ?: 'Ürün'),
                'sku' => $sku,
                'vat_rate' => 20,
            ];
            if ($map && is_numeric($map->sentos_product_id)) {
                $item['product_id'] = (int) $map->sentos_product_id;
            }
            $payloadLines[] = $item;
        }

        $city = (string) ($address->shipping_state ?? $address->billing_state ?? 'Istanbul');
        $district = (string) ($address->shipping_city ?? $address->billing_city ?? 'Merkez');
        $street = (string) ($address->shipping_address ?? $address->billing_address ?? 'Adres');
        $country = (string) ($address->shipping_country ?? $address->billing_country ?? 'Türkiye');

        $invoiceName = (string) ($address->billing_name ?? $name);
        $invoicePhone = $this->normalizePhone(
            (string) ($address->billing_phone ?? $phone)
        );

        return [
            'order_id' => 'SF-' . $order->id . '-' . $vendorId,
            'order_code' => (string) ($order->order_id ?: ('ORD-' . $order->id)),
            'status' => 2,
            'channel_id' => $channelId,
            'order_date' => optional($order->created_at)->format('Y-m-d H:i:s') ?: now()->format('Y-m-d H:i:s'),
            'currency' => 'TL',
            'payment_method' => $this->mapPaymentMethod($order),
            'shipping_total' => $shippingTotal,
            'note' => 'Seyfibaba sipariş #' . ($order->order_id ?: $order->id),
            'customer' => [
                'name' => $name,
                'phone' => $phone,
                'mail_address' => $email,
            ],
            'invoice_address' => array_filter([
                'name' => $invoiceName,
                'phone' => $invoicePhone,
                'address' => (string) ($address->billing_address ?? $street),
                'country' => (string) ($address->billing_country ?? $country),
                'city' => (string) ($address->billing_state ?? $city),
                'district' => (string) ($address->billing_city ?? $district),
                'turkishIdentityNumber' => $this->optionalTc($address->tc_identity ?? null),
                'taxOffice' => ! empty($address->tax_office) ? (string) $address->tax_office : null,
                'taxNumber' => ! empty($address->tax_number) ? (string) $address->tax_number : null,
            ], static fn ($v) => $v !== null && $v !== ''),
            'shipment_address' => [
                'name' => $name,
                'phone' => $phone,
                'address' => $street,
                'country' => $country,
                'city' => $city,
                'district' => $district,
            ],
            'lines' => $payloadLines,
        ];
    }

    private function mapPaymentMethod(Order $order): string
    {
        // Sentos accepted values (HTTP 422 otherwise):
        // KREDIKARTI/BANKAKARTI, EFT/HAVALE, NAKIT, KAPIDAODEME/NAKIT, KAPIDAODEME/KREDIKARTI
        $method = strtolower((string) ($order->payment_method ?? ''));

        if ((int) ($order->cash_on_delivery ?? 0) === 1 || str_contains($method, 'cash_on_delivery')) {
            return 'KAPIDAODEME/NAKIT';
        }
        if (str_contains($method, 'bank') || str_contains($method, 'havale') || str_contains($method, 'eft') || str_contains($method, 'money')) {
            return 'EFT/HAVALE';
        }
        if (str_contains($method, 'cash') || str_contains($method, 'nakit')) {
            return 'NAKIT';
        }

        return 'KREDIKARTI/BANKAKARTI';
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if ($digits === '') {
            return '905550000000';
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = '9' . $digits;
        }
        if (strlen($digits) === 10) {
            $digits = '90' . $digits;
        }
        if (strlen($digits) < 11) {
            $digits = str_pad($digits, 11, '0');
        }
        if (strlen($digits) > 13) {
            $digits = substr($digits, 0, 13);
        }

        return $digits;
    }

    private function optionalTc(mixed $tc): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $tc) ?: '';
        if (strlen($digits) !== 11) {
            return null;
        }

        return $digits;
    }
}
