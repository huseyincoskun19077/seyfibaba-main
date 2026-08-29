<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Setting;
use App\Models\OrderProduct;
use App\Models\OrderProductVariant;
use App\Models\OrderAddress;
use App\Models\CargoShipment;
use Auth;
class SellerOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(){
        $seller = Auth::guard('api')->user()->seller;
        $orders = Order::with('user','deliveryman')->whereHas('orderProducts',function($query) use ($seller){
            $query->where(['seller_id' => $seller->id]);
        })->where('payment_status', 1)->orderBy('id','desc')->paginate(15);
        $title = trans('All Orders');

        return response()->json(['orders' => $orders, 'title' => $title], 200);
    }

    public function pendingOrder(){
        return $this->pregressOrder();
    }

    public function pregressOrder(){
        $seller = Auth::guard('api')->user()->seller;
        $orders = Order::with('user','deliveryman')->whereHas('orderProducts',function($query) use ($seller){
            $query->where(['seller_id' => $seller->id]);
        })->where('payment_status', 1)->whereHas('orderProducts', function($q) use ($seller) {
            $q->where('seller_id', $seller->id)->whereIn('seller_status', [0, 1]);
        })->paginate(15);
        $title = 'Hazırlanan Siparişler';

        return response()->json(['orders' => $orders, 'title' => $title], 200);
    }

    public function deliveredOrder(){
        $seller = Auth::guard('api')->user()->seller;
        $orders = Order::with('user','deliveryman')->whereHas('orderProducts',function($query) use ($seller){
            $query->where(['seller_id' => $seller->id]);
        })->where('payment_status', 1)->whereHas('orderProducts', function($q) use ($seller) {
            $q->where('seller_id', $seller->id)->where('seller_status', 2);
        })->paginate(15);
        $title = 'Kargoya Verilen Siparişler';

        return response()->json(['orders' => $orders, 'title' => $title], 200);
    }

    public function completedOrder(){
        $seller = Auth::guard('api')->user()->seller;
        $orders = Order::with('user','deliveryman')->whereHas('orderProducts',function($query) use ($seller){
            $query->where(['seller_id' => $seller->id]);
        })->where('payment_status', 1)->whereHas('orderProducts', function($q) use ($seller) {
            $q->where('seller_id', $seller->id)->whereIn('seller_status', [3]);
        })->paginate(15);
        $title = 'Tamamlanan Siparişler';

        return response()->json(['orders' => $orders, 'title' => $title], 200);
    }

    public function declinedOrder(){
        $seller = Auth::guard('api')->user()->seller;
        $orders = Order::with('user','deliveryman')->whereHas('orderProducts',function($query) use ($seller){
            $query->where(['seller_id' => $seller->id]);
        })->where('payment_status', 1)->whereHas('orderProducts', function($q) use ($seller) {
            $q->where('seller_id', $seller->id)->where('seller_status', 4);
        })->paginate(15);
        $title = 'İptal/Red Siparişler';

        return response()->json(['orders' => $orders, 'title' => $title], 200);
    }

    public function cashOnDelivery(){
        $seller = Auth::guard('api')->user()->seller;
        $orders = Order::with('user','deliveryman')->whereHas('orderProducts',function($query) use ($seller){
            $query->where(['seller_id' => $seller->id]);
        })->where('payment_status', 1)->where('cash_on_delivery',1)->orderBy('id','desc')->paginate(15);

        $title = trans('Cash On Delivery');

        return response()->json(['orders' => $orders, 'title' => $title], 200);
    }

    public function show($id){
        $seller = Auth::guard('api')->user()->seller;

        $order = Order::with(['user', 'orderAddress', 'deliveryman'])
            ->where('payment_status', 1)
            ->whereHas('orderProducts', function($query) use ($seller) {
                $query->where('seller_id', $seller->id);
            })
            ->find($id);

        if (!$order) {
            return response()->json(['message' => 'Bu siparişe erişim yetkiniz yok.'], 403);
        }

        // Sadece bu satıcıya ait ürünleri döndür
        $order->setRelation(
            'orderProducts',
            $order->orderProducts()->where('seller_id', $seller->id)->with('orderProductVariants')->get()
        );

        return response()->json(['order' => $order], 200);
    }

    /**
     * Satıcı sipariş durumunu günceller
     * Not: Pazaryeri akışında satıcı süreçleri order_products.seller_status ile yürür.
     * İzin verilen geçişler (seller_status):
     *   0 (beklemede) → 1 (satıcı onayladı / hazırlanıyor)
     *   1 (hazırlanıyor) → 2 (kargoya verildi)
     */
    /**
     * Manuel kargo: mobil ve API istemcileri için (Geliver gerekmez).
     */
    public function manualShip(Request $request, $id)
    {
        $this->mergeJsonBody($request);

        $validated = $request->validate([
            'carrier_name' => ['required', 'string', 'max:255'],
            'tracking_number' => ['required', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:2000'],
        ]);

        $seller = Auth::guard('api')->user()->seller;

        $orderProduct = OrderProduct::query()
            ->where('order_id', $id)
            ->where('seller_id', $seller->id)
            ->first();

        if (! $orderProduct) {
            return response()->json(['message' => 'Sipariş bulunamadı veya bu siparişe erişim yetkiniz yok.'], 404);
        }

        if ((int) $orderProduct->seller_status !== 1) {
            return response()->json([
                'message' => 'Kargoya vermek için sipariş önce onaylanmış olmalıdır.',
            ], 422);
        }

        $this->saveManualCargoAndMarkShipped(
            (int) $id,
            (int) $seller->id,
            $orderProduct,
            trim($validated['carrier_name']),
            trim($validated['tracking_number']),
            isset($validated['tracking_url']) ? trim((string) $validated['tracking_url']) : null
        );

        return response()->json([
            'notification' => 'Manuel kargo kaydedildi ve sipariş kargoya verildi.',
        ], 200);
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $this->mergeJsonBody($request);

        $request->validate([
            'order_status' => 'required|integer|in:1,2',
            'carrier_name' => 'nullable|string|max:255',
            'tracking_number' => 'nullable|string|max:255',
            'tracking_url' => 'nullable|url|max:2000',
        ]);

        $seller = Auth::guard('api')->user()->seller;

        // Satıcı sadece kendi order_product satırını güncelleyebilir
        $orderProduct = OrderProduct::query()
            ->where('order_id', $id)
            ->where('seller_id', $seller->id)
            ->first();

        if (!$orderProduct) {
            return response()->json(['message' => 'Sipariş bulunamadı veya bu siparişe erişim yetkiniz yok.'], 404);
        }

        $newStatus = (int) $request->order_status;
        $currentStatus = (int) $orderProduct->seller_status;

        // Geçiş kuralları: 0→1 veya 1→2 (seller_status)
        $allowedTransitions = [
            0 => [1], // beklemede → onaylandı
            1 => [2], // onaylandı → kargoya verildi
        ];

        if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            return response()->json([
                'message' => 'Bu durum geçişi yapılamaz. Mevcut durum: ' . $currentStatus,
            ], 422);
        }

        if ($newStatus === 1) {
            $orderProduct->seller_status = 1;
        } elseif ($newStatus === 2) {
            $trackingNumber = trim((string) $request->input('tracking_number', ''));
            $carrierName = trim((string) $request->input('carrier_name', ''));
            $trackingUrl = $request->filled('tracking_url')
                ? trim((string) $request->input('tracking_url'))
                : null;

            $latestCargo = CargoShipment::query()
                ->where('order_id', (int) $id)
                ->where('seller_id', (int) $seller->id)
                ->whereNotIn('status', ['cancelled'])
                ->latest()
                ->first();

            if ($trackingNumber === '' && $latestCargo) {
                $trackingNumber = trim((string) ($latestCargo->tracking_number ?? ''));
            }

            if ($trackingNumber === '') {
                return response()->json([
                    'message' => 'Kargoya verildi olarak işaretlemek için kargo firması ve takip numarası girin.',
                ], 422);
            }

            if ((! $latestCargo || empty($latestCargo->tracking_number)) && $carrierName === '') {
                return response()->json([
                    'message' => 'Kargo firması adı gereklidir.',
                ], 422);
            }

            $this->saveManualCargoAndMarkShipped(
                (int) $id,
                (int) $seller->id,
                $orderProduct,
                $carrierName !== '' ? $carrierName : (string) ($latestCargo->carrier_name ?? 'Kargo'),
                $trackingNumber,
                $trackingUrl
            );
        }

        $orderProduct->save();

        $statusLabels = [1 => 'Satıcı Onayladı', 2 => 'Kargoya Verildi'];
        return response()->json([
            'notification' => 'Sipariş durumu güncellendi: ' . $statusLabels[$newStatus],
        ], 200);
    }

    private function mergeJsonBody(Request $request): void
    {
        if ($request->request->count() > 0 || $request->query->count() > 0) {
            return;
        }

        $raw = $request->getContent();
        if (! is_string($raw) || trim($raw) === '') {
            return;
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $request->merge($decoded);
        }
    }

    private function saveManualCargoAndMarkShipped(
        int $orderId,
        int $sellerId,
        OrderProduct $orderProduct,
        string $carrierName,
        string $trackingNumber,
        ?string $trackingUrl = null
    ): void {
        $latestCargo = CargoShipment::query()
            ->where('order_id', $orderId)
            ->where('seller_id', $sellerId)
            ->whereNotIn('status', ['cancelled'])
            ->latest()
            ->first();

        if (! $latestCargo || empty($latestCargo->tracking_number)) {
            CargoShipment::create([
                'order_id' => $orderId,
                'seller_id' => $sellerId,
                'carrier_name' => $carrierName,
                'tracking_number' => $trackingNumber,
                'tracking_url' => $trackingUrl,
                'status' => 'shipped',
                'created_by_type' => 'seller',
                'created_by_id' => $sellerId,
                'raw_response' => [
                    'manual' => true,
                    'created_at' => now()->toIso8601String(),
                ],
            ]);
        } else {
            $latestCargo->update([
                'carrier_name' => $carrierName !== '' ? $carrierName : $latestCargo->carrier_name,
                'tracking_number' => $trackingNumber,
                'tracking_url' => $trackingUrl ?? $latestCargo->tracking_url,
                'status' => $latestCargo->status === 'delivered' ? 'delivered' : 'shipped',
            ]);
        }

        $orderProduct->seller_status = 2;
        $orderProduct->shipped_at = $orderProduct->shipped_at ?: now();
        $orderProduct->save();
    }
}
