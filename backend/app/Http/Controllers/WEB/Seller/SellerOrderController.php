<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Setting;
use App\Models\OrderProduct;
use App\Models\OrderProductVariant;
use App\Models\OrderAddress;
use App\Models\CountryState;
use App\Models\City;
use App\Models\User;
use App\Models\CargoShipment;
use Auth;
use Mail;
use Log;
use App\Mail\OrderSuccessfully;
use App\Helpers\MailHelper;
class SellerOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index(){
        $seller = Auth::guard('web')->user()->seller;
        
        // Sadece bu satıcının sipariş ürünlerini yükle
        $orders = Order::with(['user', 'orderProducts' => function($query) use ($seller) {
            $query->where('seller_id', $seller->id);
        }])->whereHas('orderProducts', function($query) use ($seller){
            $query->where('seller_id', $seller->id);
        })->where('payment_status', 1)->orderBy('id','desc')->paginate(15);
        $title = trans('admin_validation.All Orders');
        $setting = Setting::first();
        return view('seller.order', compact('orders','title','setting'));
    }

    public function pendingOrder(){
        return redirect()->route('seller.pregress-order');
    }

    public function pregressOrder(){
        $seller = Auth::guard('web')->user()->seller;
        $orders = Order::with(['user', 'orderProducts' => function($query) use ($seller) {
            $query->where('seller_id', $seller->id)->whereIn('seller_status', [0, 1]);
        }])->whereHas('orderProducts',function($query) use ($seller){
            $query->where('seller_id', $seller->id)->whereIn('seller_status', [0, 1]);
        })->where('payment_status', 1)->paginate(15);
        $title = 'Hazırlanan Siparişler';
        $setting = Setting::first();
        return view('seller.order', compact('orders','title','setting'));
    }

    public function deliveredOrder(){
        $seller = Auth::guard('web')->user()->seller;
        $orders = Order::with(['user', 'orderProducts' => function($query) use ($seller) {
            $query->where(['seller_id' => $seller->id, 'seller_status' => 2]);
        }])->whereHas('orderProducts',function($query) use ($seller){
            $query->where(['seller_id' => $seller->id, 'seller_status' => 2]);
        })->where('payment_status', 1)->paginate(15);
        $title = 'Kargoya Verilen Siparişler';
        $setting = Setting::first();
        return view('seller.order', compact('orders','title','setting'));
    }

    public function completedOrder(){
        $seller = Auth::guard('web')->user()->seller;
        $orders = Order::with(['user', 'orderProducts' => function($query) use ($seller) {
            $query->where('seller_id', $seller->id)->whereIn('seller_status', [2, 3]);
        }])->whereHas('orderProducts',function($query) use ($seller){
            $query->where('seller_id', $seller->id)->whereIn('seller_status', [2, 3]);
        })->where('payment_status', 1)->paginate(15);
        $title = trans('admin_validation.Completed Orders');
        $setting = Setting::first();
        return view('seller.order', compact('orders','title','setting'));
    }

    public function declinedOrder(){
        $seller = Auth::guard('web')->user()->seller;
        $orders = Order::with(['user', 'orderProducts' => function($query) use ($seller) {
            $query->where('seller_id', $seller->id)->where('seller_status', 4);
        }])->whereHas('orderProducts',function($query) use ($seller){
            $query->where('seller_id', $seller->id)->where('seller_status', 4);
        })->where('payment_status', 1)->paginate(15);
        $title = trans('admin_validation.Declined Orders');
        $setting = Setting::first();
        return view('seller.order', compact('orders','title','setting'));
    }

    public function cashOnDelivery(){
        $seller = Auth::guard('web')->user()->seller;
        $orders = Order::with(['user', 'orderProducts' => function($query) use ($seller) {
            $query->where('seller_id', $seller->id);
        }])->whereHas('orderProducts',function($query) use ($seller){
            $query->where('seller_id', $seller->id);
        })->where('payment_status', 1)->where('cash_on_delivery',1)->orderBy('id','desc')->paginate(15);

        $title = trans('admin_validation.Cash On Delivery');
        $setting = Setting::first();
        return view('seller.order', compact('orders','title','setting'));
    }

    public function show($id){
        $seller = Auth::guard('web')->user()->seller;

        // Satıcının bu siparişte ürünü olduğunu doğrula VE ödeme onaylanmış olmalı
        $order = Order::with(['user', 'orderAddress', 'cargoShipment', 'orderProducts' => function($query) use ($seller) {
            $query->where('seller_id', $seller->id);
        }])
            ->whereHas('orderProducts', function($query) use ($seller) {
                $query->where('seller_id', $seller->id);
            })
            ->where('payment_status', 1)
            ->find($id);

        if (!$order) {
            $notification = array('messege' => 'Bu siparişe erişim yetkiniz yok veya ödeme onaylanmamış.', 'alert-type' => 'error');
            return redirect()->route('seller.all-order')->with($notification);
        }

        // Sadece bu satıcıya ait ürünleri yükle
        $order->setRelation(
            'orderProducts',
            $order->orderProducts()->where('seller_id', $seller->id)->with(['orderProductVariants', 'product'])->get()
        );

        $orderDistinctSellerCount = (int) OrderProduct::query()
            ->where('order_id', $order->id)
            ->selectRaw('COUNT(DISTINCT seller_id) as c')
            ->value('c');

        $sellerLinesSubtotal = 0.0;
        foreach ($order->orderProducts as $op) {
            $line = (float) $op->unit_price * (int) $op->qty;
            foreach ($op->orderProductVariants as $v) {
                $line += (float) $v->variant_price * (int) $op->qty;
            }
            $sellerLinesSubtotal += $line;
        }

        if ($order->orderAddress) {
            $addr = $order->orderAddress;
            if (trim((string) ($addr->billing_name ?? '')) === '') {
                $addr->billing_name = trim(($addr->billing_first_name ?? '').' '.($addr->billing_last_name ?? ''));
            }
            if (trim((string) ($addr->shipping_name ?? '')) === '') {
                $addr->shipping_name = trim(($addr->shipping_first_name ?? '').' '.($addr->shipping_last_name ?? ''));
            }
            if (trim((string) ($addr->shipping_state ?? '')) === '' && (int) $addr->shipping_state_id > 0) {
                $addr->shipping_state = CountryState::query()->find($addr->shipping_state_id)?->name;
            }
            if (trim((string) ($addr->shipping_city ?? '')) === '' && (int) $addr->shipping_city_id > 0) {
                $addr->shipping_city = City::query()->find($addr->shipping_city_id)?->name;
            }
            if (trim((string) ($addr->billing_state ?? '')) === '' && (int) $addr->billing_state_id > 0) {
                $addr->billing_state = CountryState::query()->find($addr->billing_state_id)?->name;
            }
            if (trim((string) ($addr->billing_city ?? '')) === '' && (int) $addr->billing_city_id > 0) {
                $addr->billing_city = City::query()->find($addr->billing_city_id)?->name;
            }
        }

        $setting = Setting::first();
        return view('seller.show_order', compact(
            'order',
            'setting',
            'orderDistinctSellerCount',
            'sellerLinesSubtotal'
        ));
    }

    /**
     * Seller sipariş durumu: Her satıcı kendi ürününü günceller (order_products.seller_status)
     * 0 (beklemede) → 1 (işlemde), 1 → 2 (teslim edildi)
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $rules = [
            'order_status' => 'required|integer|in:1,2',
        ];
        $this->validate($request, $rules);

        $seller = Auth::guard('web')->user()->seller;

        // Satıcının bu siparişteki ürününü bul
        $orderProduct = OrderProduct::where('order_id', $id)
            ->where('seller_id', $seller->id)
            ->first();

        if (! $orderProduct) {
            $notification = [
                'messege' => 'Bu siparişe erişim yetkiniz yok.',
                'alert-type' => 'error',
            ];
            return redirect()->route('seller.all-order')->with($notification);
        }

        $currentStatus = (int) $orderProduct->seller_status;
        $newStatus = (int) $request->order_status;

        if ($newStatus === 1) {
            if ($currentStatus !== 0) {
                $notification = [
                    'messege' => 'Bu sipariş zaten onaylanmış.',
                    'alert-type' => 'warning',
                ];
                return redirect()->back()->with($notification);
            }
            $orderProduct->seller_status = 1;
            $orderProduct->save();

            // Sipariş durumunu güncelle - satıcı onayladıysa
            $order = Order::find($id);
            if ($order->order_status == 0) {
                $order->order_status = 1;
                $order->order_approval_date = date('Y-m-d');
                $order->save();
            }

            // Email gönder
            try {
                MailHelper::setMailConfig();
                $order = Order::find($id);
                $user = User::find($order->user_id);
                if ($user && $user->email) {
                    $template = 'Siparişiniz satıcı tarafından onaylandı ve işleme alındı. Sipariş No: ' . $order->order_id;
                    Mail::to($user->email)->send(new OrderSuccessfully($template, 'Sipariş Onaylandı - Seyfibaba'));
                }
            } catch (\Exception $e) {
                \Log::warning('Satıcı sipariş onay mail gönderilemedi', ['order_id' => $id, 'error' => $e->getMessage()]);
            }

            $notification = [
                'messege' => 'Sipariş onaylandı. Kargoya verebilirsiniz.',
                'alert-type' => 'success',
            ];
        } elseif ($newStatus === 2) {
            if ($currentStatus !== 1) {
                $notification = [
                    'messege' => 'Sadece onaylanmış siparişleri kargoya verildi olarak işaretleyebilirsiniz.',
                    'alert-type' => 'warning',
                ];
                return redirect()->back()->with($notification);
            }
            // Pazaryeri kuralı: "Kargoya verildi" için takip numarası olmalı.
            // Satıcı Geliver kullanabilir veya Manuel Kargo kartından takip no girebilir.
            $latestCargo = CargoShipment::query()
                ->where('order_id', (int) $id)
                ->where('seller_id', (int) $seller->id)
                ->whereNotIn('status', ['cancelled'])
                ->latest()
                ->first();

            if (! $latestCargo || empty($latestCargo->tracking_number)) {
                $notification = [
                    'messege' => 'Kargoya verildi olarak işaretlemek için takip numarası girmeniz gerekiyor. Lütfen "Manuel Kargo" bölümünden kargo firması ve takip numarası kaydedin.',
                    'alert-type' => 'warning',
                ];
                return redirect()->back()->with($notification);
            }

            // Satır bazlı shipped işaretle (bu satıcıya ait tüm satırlar)
            $sellerLines = OrderProduct::query()
                ->where('order_id', (int) $id)
                ->where('seller_id', (int) $seller->id)
                ->get();

            foreach ($sellerLines as $op) {
                $op->seller_status = 2;
                $op->shipped_at = $op->shipped_at ?: now();
                $op->save();
            }

            $order = Order::find($id);
            if ($order) {
                \App\Support\OrderFulfillmentSync::sync($order);
            }

            // Email gönder — kargo takip no dahil
            try {
                MailHelper::setMailConfig();
                $user = $order ? User::find($order->user_id) : null;
                if ($order && $user && $user->email) {
                    $productNames = $sellerLines->map(fn ($op) => ($op->product->name ?? 'Ürün') . ' x' . ($op->qty ?? 1))->implode("\n");
                    $trackingInfo = $latestCargo->tracking_number
                        ? "\nKargo Firması: " . ($latestCargo->carrier_name ?? $latestCargo->cargo_company ?? '-') . "\nTakip No: " . $latestCargo->tracking_number
                        : '';
                    $template = "Siparişiniz kargoya verildi.\n\nSipariş No: {$order->order_id}\n\nGönderilen Ürünler:\n{$productNames}{$trackingInfo}\n\nKargo durumunu takip numarasıyla sorgulayabilirsiniz.";
                    Mail::to($user->email)->send(new OrderSuccessfully($template, 'Sipariş Kargoya Verildi — Seyfibaba'));
                }
            } catch (\Exception $e) {
                \Log::warning('Satıcı teslim mail gönderilemedi', ['order_id' => $id, 'error' => $e->getMessage()]);
            }

            $notification = [
                'messege' => 'Sipariş kargoya verildi olarak işaretlendi.',
                'alert-type' => 'success',
            ];
        }

        return redirect()->back()->with($notification);
    }
}
