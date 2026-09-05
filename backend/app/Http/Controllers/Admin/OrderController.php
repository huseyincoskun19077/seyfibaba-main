<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Setting;
use App\Models\OrderProduct;
use App\Models\OrderProductVariant;
use App\Models\OrderAddress;
use App\Models\Product;
use App\Services\CommissionService;
use App\Services\SellerPayoutService;
use Illuminate\Support\Facades\Log;
class OrderController extends Controller
{
    public function __construct(
        private CommissionService $commissionService,
        private SellerPayoutService $payoutService,
    )
    {
        $this->middleware('auth:admin-api');
    }

    public function index(){
        $orders = Order::with('user')->orderBy('id','desc')->paginate(15);
        $title = trans('All Orders');
        $setting = Setting::first();

        return response()->json(['orders' => $orders, 'title' => $title], 200);
    }

    public function pendingOrder(){
        $orders = Order::with('user')->orderBy('id','desc')->where('order_status',0)->paginate(15);
        $title = trans('Pending Orders');
        $setting = Setting::first();

        return response()->json(['orders' => $orders, 'title' => $title], 200);
    }

    public function pregressOrder(){
        $orders = Order::with('user')->orderBy('id','desc')->where('order_status',1)->paginate(15);
        $title = trans('Pregress Orders');
        $setting = Setting::first();

        return response()->json(['orders' => $orders, 'title' => $title], 200);
    }

    public function deliveredOrder(){
        $orders = Order::with('user')->orderBy('id','desc')->where('order_status',2)->paginate(15);
        $title = trans('Delivered Orders');
        $setting = Setting::first();

        return response()->json(['orders' => $orders, 'title' => $title], 200);
    }

    public function completedOrder(){
        $orders = Order::with('user')->orderBy('id','desc')->where('order_status',3)->paginate(15);
        $title = trans('Completed Orders');
        $setting = Setting::first();
        return response()->json(['orders' => $orders, 'title' => $title], 200);
    }

    public function declinedOrder(){
        $orders = Order::with('user')->orderBy('id','desc')->where('order_status',4)->paginate(15);
        $title = trans('Declined Orders');
        $setting = Setting::first();
        return response()->json(['orders' => $orders, 'title' => $title], 200);
    }

    public function cashOnDelivery(){
        $orders = Order::with('user')->orderBy('id','desc')->where('cash_on_delivery',1)->paginate(15);
        $title = trans('Cash On Delivery');
        $setting = Setting::first();
        return response()->json(['orders' => $orders, 'title' => $title], 200);
    }

    // Bank transfer pending orders
    public function bankTransferPending(){
        $orders = Order::with('user')
            ->orderBy('id','desc')
            ->where('payment_method', 'bankpayment')
            ->where('payment_status', 0)
            ->paginate(15);
        $title = 'Havale Bekleyen Siparişler';
        $setting = Setting::first();
        return response()->json(['orders' => $orders, 'title' => $title], 200);
    }

    // Approve bank transfer payment
    public function approvePayment($id){
        $order = Order::with('user')->find($id);
        
        if (!$order) {
            return response()->json(['message' => 'Sipariş bulunamadı'], 404);
        }
        
        if ($order->payment_method != 'bankpayment') {
            return response()->json(['message' => 'Bu sipariş havale ile ödenmemiş'], 400);
        }
        
        // Update payment status to approved
        $order->payment_status = 1;
        $order->payment_approval_date = date('Y-m-d H:i:s');
        $order->save();

        // Stok sipariş oluşturulurken rezerve edildi; onayda tekrar düşülmez.
        
        // Send email to customer
        try {
            $user = $order->user;
            if ($user && $user->email) {
                \Mail::to($user->email)->send(new \App\Mail\PaymentApprovedMail($order));
            }
        } catch (\Exception $e) {
            // Email gönderimi başarısız olursa sipariş yine de onaylansın
        }
        
        return response()->json(['message' => 'Ödeme onaylandı ve müşteri bilgilendirildi'], 200);
    }

    public function show($id){
        $order = Order::with('user','orderProducts.orderProductVariants','orderAddress')->find($id);
        return response()->json(['order' => $order], 200);
    }

    public function updateOrderStatus(Request $request , $id){
        $rules = [
            'order_status' => 'required',
            'payment_status' => 'required',
        ];
        $this->validate($request, $rules);

        $order = Order::find($id);
        $previousOrderStatus = (int) $order->order_status;
        if($request->order_status == 0){
            $order->order_status = 0;
            $order->save();
        }else if($request->order_status == 1){
            $order->order_status = 1;
            $order->order_approval_date = date('Y-m-d');
            $order->save();
        }else if($request->order_status == 2){
            $order->order_status = 2;
            $order->order_delivered_date = date('Y-m-d');
            $order->save();
        }else if($request->order_status == 3){
            $order->order_status = 3;
            $order->order_completed_date = date('Y-m-d');
            $order->save();
            $this->commissionService->settleCommissions($order);
            app(\App\Services\SellerPayoutService::class)->schedulePayoutEligibility($order);
        }else if($request->order_status == 4){
            $order->order_status = 4;
            $order->order_declined_date = date('Y-m-d');
            $order->save();

            if ($previousOrderStatus !== 4) {
                $order->loadMissing('orderProducts');
                foreach ($order->orderProducts as $orderProduct) {
                    $product = Product::find($orderProduct->product_id);
                    if ($product) {
                        $product->qty = (int) $product->qty + (int) $orderProduct->qty;
                        $product->save();
                    }
                }
            }

            try {
                \App\Helpers\MailHelper::setMailConfig();
                $user = \App\Models\User::find($order->user_id);
                if ($user && $user->email) {
                    $content = "Siparişiniz iptal edildi.\n\nSipariş No: {$order->order_id}\nİptal Tarihi: " . date('d.m.Y') . "\n\nÖdemeniz varsa iade süreci başlatılacaktır. Sorularınız için destek ekibimizle iletişime geçebilirsiniz.";
                    \Mail::to($user->email)->send(new \App\Mail\OrderCancelledMail($content));
                }
            } catch (\Throwable $e) {
                \Log::warning('Order cancelled mail failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        if($request->payment_status == 0){
            $order->payment_status = 0;
            $order->save();
        }elseif($request->payment_status == 1){
            $order->payment_status = 1;
            $order->payment_approval_date = date('Y-m-d');
            $order->save();
        }

        $notification = trans('Order Status Updated successfully');
        return response()->json(['notification' => $notification], 200);
    }


    public function destroy($id){
        $order = Order::find($id);
        $order->delete();
        $orderProducts = OrderProduct::where('order_id',$id)->get();
        $orderAddress = OrderAddress::where('order_id',$id)->first();
        foreach($orderProducts as $orderProduct){
            OrderProductVariant::where('order_product_id',$orderProduct->id)->delete();
            $orderProduct->delete();
        }
        OrderAddress::where('order_id',$id)->delete();

        $notification = trans('Delete successfully');
        return response()->json(['notification' => $notification], 200);
    }

    /**
     * Process seller payout for a specific order
     * Transfers the seller amount to sub-merchant account via Iyzico
     */
    public function processSellerPayout(Request $request, $orderId)
    {
        $order = Order::with('orderProducts.seller', 'orderProducts.product')
            ->find($orderId);

        if (!$order) {
            return response()->json(['notification' => 'Sipariş bulunamadı'], 404);
        }

        $result = $this->payoutService->processOrderPayout($order, true);

        return response()->json([
            'notification' => $result['message'],
            'results' => $result['results'] ?? [],
        ], $result['success'] ? 200 : 422);
    }

    public function blockPayout(Request $request, $orderId)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = Order::query()->find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Sipariş bulunamadı'], 404);
        }

        $order->payout_blocked_at = now();
        $order->payout_block_reason = $validated['reason'] ?? null;
        $order->save();

        return response()->json([
            'message' => 'Payout bloklandı',
            'order' => [
                'id' => $order->id,
                'payout_blocked_at' => $order->payout_blocked_at,
                'payout_block_reason' => $order->payout_block_reason,
                'payout_hold_until' => $order->payout_hold_until,
            ],
        ]);
    }

    public function unblockPayout($orderId)
    {
        $order = Order::query()->find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Sipariş bulunamadı'], 404);
        }

        $order->payout_blocked_at = null;
        $order->payout_block_reason = null;
        $order->save();

        return response()->json([
            'message' => 'Payout blok kaldırıldı',
            'order' => [
                'id' => $order->id,
                'payout_blocked_at' => $order->payout_blocked_at,
                'payout_block_reason' => $order->payout_block_reason,
                'payout_hold_until' => $order->payout_hold_until,
            ],
        ]);
    }

    public function holdPayout(Request $request, $orderId)
    {
        $validated = $request->validate([
            'hold_until' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = Order::query()->find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Sipariş bulunamadı'], 404);
        }

        $order->payout_hold_until = $validated['hold_until'];
        if (!empty($validated['reason'])) {
            $order->payout_block_reason = $validated['reason'];
        }
        $order->save();

        return response()->json([
            'message' => 'Payout bekletmeye alındı',
            'order' => [
                'id' => $order->id,
                'payout_blocked_at' => $order->payout_blocked_at,
                'payout_block_reason' => $order->payout_block_reason,
                'payout_hold_until' => $order->payout_hold_until,
            ],
        ]);
    }

    public function clearHoldPayout($orderId)
    {
        $order = Order::query()->find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Sipariş bulunamadı'], 404);
        }

        $order->payout_hold_until = null;
        $order->save();

        return response()->json([
            'message' => 'Payout bekletme kaldırıldı',
            'order' => [
                'id' => $order->id,
                'payout_blocked_at' => $order->payout_blocked_at,
                'payout_block_reason' => $order->payout_block_reason,
                'payout_hold_until' => $order->payout_hold_until,
            ],
        ]);
    }
}
