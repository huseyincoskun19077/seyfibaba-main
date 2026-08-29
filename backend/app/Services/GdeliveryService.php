<?php

namespace App\Services;

use App\Models\CargoShipment;
use App\Models\City;
use App\Models\CountryState;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\Setting;
use Geliver\Client;
use Illuminate\Support\Facades\Cache;

class GdeliveryService
{
    private ?Client $client = null;
    private bool $testMode;

    public function __construct()
    {
        $setting = Setting::query()->first();
        $apiToken = $setting?->geliver_api_token ?: config('services.geliver.api_token');
        $this->testMode = ($setting?->geliver_test_mode ?? '') === 'on';

        if ($apiToken) {
            $this->client = new Client($apiToken);
        }
    }

    private function getClient(): Client
    {
        if (! $this->client) {
            throw new \Exception('Geliver API token tanımlı değil. Lütfen Geliver Kargo Yapılandırması ekranından API token girin.');
        }

        return $this->client;
    }

    public function getShipmentOffers(Order $order): array
    {
        $shipment = $this->createGeliverShipment($this->buildShipmentData($order));
        $offers = $this->normalizeOffers($shipment['offers'] ?? null);

        if (empty($offers)) {
            throw new \Exception('Geliver\'dan teklif alınamadı. Lütfen tekrar deneyin.');
        }

        foreach ($offers as $offer) {
            if (! empty($offer['id'])) {
                Cache::put(
                    $this->offerCacheKey($order->id, (string) $offer['id']),
                    ['carrier_name' => $offer['carrier_name'] ?? null],
                    now()->addMinutes(15)
                );
            }
        }

        return [
            'shipment_id' => $shipment['id'] ?? null,
            'offers' => $offers,
            'default_offer_id' => $this->resolveDefaultOfferId($shipment, $offers),
        ];
    }

    public function createShipment(
        Order $order,
        ?string $offerId = null,
        ?int $sellerId = null,
        string $createdByType = 'admin',
        int $createdById = 0
    ): CargoShipment {
        $existing = CargoShipment::query()
            ->where('order_id', $order->id)
            ->whereNotIn('status', ['cancelled'])
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        $shipment = null;
        $selectedOfferId = null;
        $carrierName = null;

        if (! empty($offerId)) {
            $selectedOfferId = (string) $offerId;
            $offerMeta = Cache::get($this->offerCacheKey($order->id, $selectedOfferId), []);
            $carrierName = $offerMeta['carrier_name'] ?? null;
            $txResponse = $this->getClient()->transactions()->acceptOffer($selectedOfferId);
            $transaction = $txResponse['data'] ?? $txResponse;
        } else {
            $shipment = $this->createGeliverShipment($this->buildShipmentData($order));
            $offers = $this->normalizeOffers($shipment['offers'] ?? null);

            if (empty($offers)) {
                throw new \Exception('Geliver\'dan teklif alınamadı. Lütfen tekrar deneyin.');
            }

            $selectedOfferId = $this->resolveDefaultOfferId($shipment, $offers);
            if (! $selectedOfferId) {
                throw new \Exception('Uygun kargo teklifi bulunamadı.');
            }

            $selectedOffer = collect($offers)->firstWhere('id', $selectedOfferId);
            $carrierName = $selectedOffer['carrier_name'] ?? null;

            $txResponse = $this->getClient()->transactions()->acceptOffer($selectedOfferId);
            $transaction = $txResponse['data'] ?? $txResponse;
        }

        $transactionShipment = $transaction['shipment'] ?? [];
        // Seller resolution:
        // - seller panel: createdByType=seller, createdById = vendor_id
        // - admin: sellerId must be provided for multi-vendor orders; if single-vendor, caller can omit
        if ($createdByType === 'seller' && !$sellerId) {
            $sellerId = $createdById ?: null;
        }

        $cargoShipment = CargoShipment::create([
            'order_id' => $order->id,
            'seller_id' => $sellerId,
            'geliver_shipment_id' => $transactionShipment['id'] ?? ($shipment['id'] ?? null),
            'geliver_transaction_id' => $transaction['id'] ?? null,
            'carrier_name' => $carrierName
                ?? ($transactionShipment['carrier']['name'] ?? null)
                ?? ($transaction['carrier']['name'] ?? null),
            'barcode' => $transactionShipment['barcode'] ?? null,
            'tracking_number' => $transactionShipment['trackingNumber'] ?? null,
            'tracking_url' => $transactionShipment['trackingUrl'] ?? null,
            'label_url' => $transactionShipment['labelURL'] ?? null,
            'status' => 'shipped',
            'created_by_type' => $createdByType,
            'created_by_id' => $createdById,
            'raw_response' => [
                'shipment' => $shipment,
                'transaction' => $transaction,
                'selected_offer_id' => $selectedOfferId,
            ],
        ]);

        if ((int) $order->order_status === 0) {
            $order->order_status = 1;
            $order->order_approval_date = date('Y-m-d');
            $order->save();
        }

        return $cargoShipment;
    }

    public function cancelShipment(CargoShipment $cargoShipment): bool
    {
        if ($cargoShipment->geliver_shipment_id) {
            try {
                $this->getClient()->shipments()->cancel($cargoShipment->geliver_shipment_id);
            } catch (\Exception $e) {
                \Log::warning('Geliver cancel API failed (local cancel still applied)', [
                    'cargo_id' => $cargoShipment->id,
                    'geliver_shipment_id' => $cargoShipment->geliver_shipment_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $cargoShipment->update(['status' => 'cancelled']);

        return true;
    }

    public function getTrackingStatus(CargoShipment $cargoShipment): array
    {
        if (! $cargoShipment->geliver_shipment_id) {
            return ['status' => $cargoShipment->status];
        }

        return $this->getClient()->shipments()->get($cargoShipment->geliver_shipment_id);
    }

    public function createSenderAddress(array $data): array
    {
        $address1 = trim($data['neighborhood'] . ' Mahallesi, ' . $data['address']);

        return $this->getClient()->addresses()->createSender([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $this->normalizePhone($data['phone']),
            'address1' => $address1,
            'countryCode' => 'TR',
            'cityCode' => $data['city_code'],
            'cityName' => $data['city_name'],
            'districtName' => $data['district'],
            'zip' => $data['zip'] ?? '',
        ]);
    }

    public function listCities(): array
    {
        return $this->getClient()->geo()->listCities('TR');
    }

    private function buildShipmentData(Order $order): array
    {
        $orderAddress = OrderAddress::query()->where('order_id', $order->id)->first();

        if (! $orderAddress) {
            throw new \Exception('Sipariş teslimat adresi bulunamadı.');
        }

        $setting = Setting::query()->first();
        $senderAddressId = $setting?->geliver_sender_address_id ?: config('services.geliver.default_sender_address_id');

        if (! $senderAddressId) {
            throw new \Exception('Gönderici adresi tanımlı değil. Lütfen Geliver Kargo Yapılandırması sayfasından Gönderici Adres ID girin.');
        }

        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string) $senderAddressId)) {
            throw new \Exception('Gönderici Adres ID geçersiz UUID formatında. Lütfen Geliver panelindeki gerçek adres UUID değerini girin.');
        }

        $recipientName = trim(($orderAddress->shipping_first_name ?? '') . ' ' . ($orderAddress->shipping_last_name ?? ''));
        if ($recipientName === '') {
            $recipientName = $orderAddress->shipping_email ?: 'Müşteri';
        }

        if (! $orderAddress->shipping_phone) {
            throw new \Exception('Alıcı telefon numarası bulunamadı. Lütfen sipariş adresini kontrol edin.');
        }

        $stateName = $orderAddress->shipping_state ?? CountryState::query()->find($orderAddress->shipping_state_id)?->name;
        $cityNameRaw = $orderAddress->shipping_city ?? City::query()->find($orderAddress->shipping_city_id)?->name;
        $cityName = $this->normalizeAscii($stateName ?: $this->getCityName($orderAddress->shipping_address ?? ''));
        $districtName = $cityNameRaw ? $this->normalizeAscii($cityNameRaw) : null;
        $cityCode = $this->getCityCode($cityName);

        $recipientAddress = [
            'name' => $recipientName,
            'phone' => $this->normalizePhone($orderAddress->shipping_phone),
            'address1' => $orderAddress->shipping_address,
            'cityName' => $cityName,
            'countryCode' => 'TR',
        ];

        if ($cityCode) {
            $recipientAddress['cityCode'] = $cityCode;
        }
        if ($districtName) {
            $recipientAddress['districtName'] = $districtName;
        }
        if ($orderAddress->shipping_zip_code) {
            $recipientAddress['zip'] = $orderAddress->shipping_zip_code;
        }

        return [
            'senderAddressID' => (string) $senderAddressId,
            'recipientAddress' => $recipientAddress,
            'length' => '20.0',
            'width' => '15.0',
            'height' => '10.0',
            'weight' => '1.0',
            'massUnit' => 'kg',
            'distanceUnit' => 'cm',
        ];
    }

    private function createGeliverShipment(array $shipmentData): array
    {
        $response = $this->testMode
            ? $this->getClient()->shipments()->createTest($shipmentData)
            : $this->getClient()->shipments()->create($shipmentData);

        return $response['data'] ?? $response;
    }

    private function normalizeOffers(mixed $offers): array
    {
        if (! is_array($offers)) {
            return [];
        }

        $result = [];
        $pushOffer = function (mixed $offer) use (&$result): void {
            if (! is_array($offer) || empty($offer['id'])) {
                return;
            }

            $priceRaw = $offer['amountLocal'] ?? $offer['amount'] ?? $offer['totalAmountLocal'] ?? $offer['totalAmount'] ?? $offer['price'] ?? $offer['totalPrice'] ?? null;
            $currency = $offer['currencyLocal'] ?? $offer['currency'] ?? 'TRY';
            $carrierName = $offer['providerAccountName'] ?? $offer['providerCode'] ?? ($offer['carrier']['name'] ?? null);

            $result[] = [
                'id' => (string) $offer['id'],
                'carrier_name' => $carrierName,
                'price' => is_numeric($priceRaw) ? (float) $priceRaw : null,
                'currency' => (string) $currency,
                'price_text' => is_numeric($priceRaw) ? number_format((float) $priceRaw, 2, ',', '.') . ' ₺' : null,
            ];
        };

        if (isset($offers['cheapest']) && is_array($offers['cheapest'])) {
            $pushOffer($offers['cheapest']);
        }

        foreach (['all', 'list', 'offers', 'items'] as $key) {
            if (! empty($offers[$key]) && is_array($offers[$key])) {
                foreach ($offers[$key] as $offer) {
                    $pushOffer($offer);
                }
            }
        }

        if (array_is_list($offers)) {
            foreach ($offers as $offer) {
                $pushOffer($offer);
            }
        }

        $unique = [];
        foreach ($result as $offer) {
            $unique[$offer['id']] = $offer;
        }

        return array_values($unique);
    }

    private function resolveDefaultOfferId(array $shipment, array $offers): ?string
    {
        $cheapestId = $shipment['offers']['cheapest']['id'] ?? null;
        if (! empty($cheapestId)) {
            return (string) $cheapestId;
        }

        $sorted = collect($offers)
            ->filter(fn ($item) => isset($item['price']) && is_numeric($item['price']))
            ->sortBy('price')
            ->values();

        return ! empty($sorted[0]['id']) ? (string) $sorted[0]['id'] : ($offers[0]['id'] ?? null);
    }

    private function offerCacheKey(int $orderId, string $offerId): string
    {
        return "geliver_offer_meta:order:{$orderId}:offer:{$offerId}";
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        if (preg_match('/^0(\d{10})$/', $phone, $m)) {
            return '+90' . $m[1];
        }

        if (preg_match('/^(\d{10})$/', $phone, $m)) {
            return '+90' . $m[1];
        }

        return $phone;
    }

    private function getCityName(string $address): string
    {
        $cityMap = [
            'istanbul' => 'Istanbul', 'ankara' => 'Ankara', 'izmir' => 'Izmir', 'bursa' => 'Bursa',
            'antalya' => 'Antalya', 'adana' => 'Adana', 'konya' => 'Konya', 'gaziantep' => 'Gaziantep',
            'mersin' => 'Mersin', 'kayseri' => 'Kayseri', 'eskisehir' => 'Eskisehir', 'eskişehir' => 'Eskisehir',
            'diyarbakir' => 'Diyarbakir', 'diyarbakır' => 'Diyarbakir', 'samsun' => 'Samsun',
            'denizli' => 'Denizli', 'sanliurfa' => 'Sanliurfa', 'şanlıurfa' => 'Sanliurfa',
            'trabzon' => 'Trabzon', 'malatya' => 'Malatya',
        ];

        $lower = mb_strtolower($address, 'UTF-8');
        foreach ($cityMap as $keyword => $name) {
            if (str_contains($lower, $keyword)) {
                return $name;
            }
        }

        return 'Istanbul';
    }

    private function normalizeAscii(string $text): string
    {
        $from = ['İ', 'ı', 'Ş', 'ş', 'Ğ', 'ğ', 'Ü', 'ü', 'Ö', 'ö', 'Ç', 'ç'];
        $to = ['I', 'i', 'S', 's', 'G', 'g', 'U', 'u', 'O', 'o', 'C', 'c'];

        return str_replace($from, $to, $text);
    }

    private function getCityCode(string $cityName): ?string
    {
        $map = [
            'Adana' => '01', 'Adiyaman' => '02', 'Afyonkarahisar' => '03', 'Agri' => '04', 'Amasya' => '05',
            'Ankara' => '06', 'Antalya' => '07', 'Artvin' => '08', 'Aydin' => '09', 'Balikesir' => '10',
            'Bilecik' => '11', 'Bingol' => '12', 'Bitlis' => '13', 'Bolu' => '14', 'Burdur' => '15',
            'Bursa' => '16', 'Canakkale' => '17', 'Cankiri' => '18', 'Corum' => '19', 'Denizli' => '20',
            'Diyarbakir' => '21', 'Edirne' => '22', 'Elazig' => '23', 'Erzincan' => '24', 'Erzurum' => '25',
            'Eskisehir' => '26', 'Gaziantep' => '27', 'Giresun' => '28', 'Gumushane' => '29', 'Hakkari' => '30',
            'Hatay' => '31', 'Isparta' => '32', 'Mersin' => '33', 'Istanbul' => '34', 'Izmir' => '35',
            'Kars' => '36', 'Kastamonu' => '37', 'Kayseri' => '38', 'Kirklareli' => '39', 'Kirsehir' => '40',
            'Kocaeli' => '41', 'Konya' => '42', 'Kutahya' => '43', 'Malatya' => '44', 'Manisa' => '45',
            'Kahramanmaras' => '46', 'Mardin' => '47', 'Mugla' => '48', 'Mus' => '49', 'Nevsehir' => '50',
            'Nigde' => '51', 'Ordu' => '52', 'Rize' => '53', 'Sakarya' => '54', 'Samsun' => '55',
            'Siirt' => '56', 'Sinop' => '57', 'Sivas' => '58', 'Tekirdag' => '59', 'Tokat' => '60',
            'Trabzon' => '61', 'Tunceli' => '62', 'Sanliurfa' => '63', 'Usak' => '64', 'Van' => '65',
            'Yozgat' => '66', 'Zonguldak' => '67', 'Aksaray' => '68', 'Bayburt' => '69', 'Karaman' => '70',
            'Kirikkale' => '71', 'Batman' => '72', 'Sirnak' => '73', 'Bartin' => '74', 'Ardahan' => '75',
            'Igdir' => '76', 'Yalova' => '77', 'Karabuk' => '78', 'Kilis' => '79', 'Osmaniye' => '80', 'Duzce' => '81',
        ];

        return $map[$cityName] ?? null;
    }
}
