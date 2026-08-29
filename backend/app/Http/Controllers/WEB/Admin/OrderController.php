<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Models\Order;
use App\Models\Setting;
use App\Models\DeliveryMan;
use App\Models\OrderAddress;
use App\Models\OrderProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\OrderProductVariant;
use App\Http\Controllers\Controller;
use App\Services\CommissionService;
use App\Services\SellerPayoutService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Country;

class OrderController extends Controller
{
    protected $commissionService;
    protected $payoutService;

    public function __construct(CommissionService $commissionService, SellerPayoutService $payoutService)
    {
        $this->commissionService = $commissionService;
        $this->payoutService = $payoutService;
        $this->middleware('auth:admin');
    }

    public function index(){
        $orders = Order::with('user', 'orderProducts')->orderBy('id','desc')->get();
        $title = trans('admin_validation.All Orders');
        $setting = Setting::first();


        return view('admin.order', compact('orders','title','setting'));

    }

    public function pendingOrder(){
        $orders = Order::with('user', 'orderProducts')->orderBy('id','desc')->where('order_status',0)->get();
        $title = trans('admin_validation.Pending Orders');
        $setting = Setting::first();

        return view('admin.order', compact('orders','title','setting'));
    }

    public function pregressOrder(){
        $orders = Order::with('user', 'orderProducts')->orderBy('id','desc')->where('order_status',1)->get();
        $title = trans('admin_validation.Pregress Orders');
        $setting = Setting::first();

        return view('admin.order', compact('orders','title','setting'));
    }

    public function deliveredOrder(){
        $orders = Order::with('user', 'orderProducts')->orderBy('id','desc')->where('order_status',2)->get();
        $title = trans('admin_validation.Delivered Orders');
        $setting = Setting::first();

        return view('admin.order', compact('orders','title','setting'));
    }

    public function completedOrder(){
        $orders = Order::with('user', 'orderProducts')->orderBy('id','desc')->where('order_status',3)->get();
        $title = trans('admin_validation.Completed Orders');
        $setting = Setting::first();
        return view('admin.order', compact('orders','title','setting'));
    }

    public function declinedOrder(){
        $orders = Order::with('user', 'orderProducts')->orderBy('id','desc')->where('order_status',4)->get();
        $title = trans('admin_validation.Declined Orders');
        $setting = Setting::first();
        return view('admin.order', compact('orders','title','setting'));
    }

    public function cashOnDelivery(){
        $orders = Order::with('user', 'orderProducts')->orderBy('id','desc')->where('cash_on_delivery',1)->get();
        $title = trans('admin_validation.Cash On Delivery');
        $setting = Setting::first();
        return view('admin.order', compact('orders','title','setting'));
    }

    // Havale bekleyen siparişler
    public function bankTransferPending(){
        $orders = Order::with('user', 'orderProducts')
            ->orderBy('id','desc')
            ->where('payment_method', 'bankpayment')
            ->where('payment_status', 0)
            ->get();
        $title = 'Havale Bekleyen Siparişler';
        $setting = Setting::first();
        return view('admin.order', compact('orders','title','setting'));
    }

    // Havale ödeme onayla
    public function approvePayment($id){
        $order = Order::with('user')->find($id);
        
        if (!$order) {
            return back()->with('error', 'Sipariş bulunamadı');
        }
        
        if ($order->payment_method != 'bankpayment') {
            return back()->with('error', 'Bu sipariş havale ile ödenmemiş');
        }
        
        // Update payment status to approved
        $order->payment_status = 1;
        $order->payment_approval_date = date('Y-m-d H:i:s');
        $order->save();
        
        // Reduce stock after payment approved
        $orderProducts = $order->orderProducts;
        foreach ($orderProducts as $orderProduct) {
            $product = Product::find($orderProduct->product_id);
            if ($product) {
                $product->qty -= $orderProduct->qty;
                $product->save();
            }
        }
        
        // Send email to customer
        try {
            $user = $order->user;
            if ($user && $user->email) {
                \Mail::to($user->email)->send(new \App\Mail\PaymentApprovedMail($order));
            }
        } catch (\Exception $e) {
            // Email gönderimi başarısız olursa sipariş yine de onaylansın
        }
        
        return back()->with('success', 'Ödeme onaylandı ve müşteri bilgilendirildi');
    }

    public function show($id){
        $order = Order::with('user', 'orderProducts.orderProductVariants', 'orderProducts.product', 'orderAddress')->find($id);
        $products = Product::where('status',1)->get();
        $deliverymans=DeliveryMan::latest()->get();
        $setting = Setting::first();

        $brands = Brand::all();
        $categories = Category::with('subCategories','products')->get();
        $countries = Country::all();
        return view('admin.show_order',compact('order', 'deliverymans', 'setting','products','brands','categories','countries'));
    }

    public function blockPayout(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:2000',
        ]);

        $order = Order::findOrFail($id);
        $order->payout_blocked_at = now();
        $order->payout_block_reason = $request->reason ?: null;
        $order->save();

        return redirect()->back()->with([
            'messege' => 'Payout bloklandı',
            'alert-type' => 'success',
        ]);
    }

    public function unblockPayout($id)
    {
        $order = Order::findOrFail($id);
        $order->payout_blocked_at = null;
        $order->payout_block_reason = null;
        $order->save();

        return redirect()->back()->with([
            'messege' => 'Payout blok kaldırıldı',
            'alert-type' => 'success',
        ]);
    }

    public function holdPayout(Request $request, $id)
    {
        $request->validate([
            'hold_until' => 'required|date',
            'reason' => 'nullable|string|max:2000',
        ]);

        $order = Order::findOrFail($id);
        $order->payout_hold_until = $request->hold_until;
        if ($request->filled('reason')) {
            $order->payout_block_reason = $request->reason;
        }
        $order->save();

        return redirect()->back()->with([
            'messege' => 'Payout bekletmeye alındı',
            'alert-type' => 'success',
        ]);
    }

    public function clearHoldPayout($id)
    {
        $order = Order::findOrFail($id);
        $order->payout_hold_until = null;
        $order->save();

        return redirect()->back()->with([
            'messege' => 'Payout bekletme kaldırıldı',
            'alert-type' => 'success',
        ]);
    }

    public function processPayout($id)
    {
        $order = Order::with('orderProducts.seller', 'orderProducts.product')->findOrFail($id);

        $this->payoutService->syncPaymentTransactionIds($order);
        $order->refresh()->load('orderProducts.seller', 'orderProducts.product');

        $result = $this->payoutService->processOrderPayout($order, true);

        return redirect()->back()->with([
            'messege' => $result['message'],
            'alert-type' => $result['success'] ? 'success' : 'error',
        ]);
    }

    public function updateOrderStatus(Request $request , $id){
        $rules = [
            'order_status' => 'required',
            'payment_status' => 'required',
        ];
        $this->validate($request, $rules);

        $order = Order::find($id);
        if($request->order_status == 0){
            $order->order_status = 0;
            $order->save();
        }else if($request->order_status == 1){
            $order->order_status = 1;
            $order->order_approval_date = date('Y-m-d');
            $order->save();
           //return $this->sendFirebasePush($tokens,$data);
        }else if($request->order_status == 2){
            $order->order_status = 2;
            $order->order_delivered_date = date('Y-m-d');
            $order->save();
        }else if($request->order_status == 3){
            $order->order_status = 3;
            $order->order_completed_date = date('Y-m-d');
            $order->save();

            // settle commission
            $this->commissionService->settleCommissions($order);
            app(\App\Services\SellerPayoutService::class)->schedulePayoutEligibility($order);
        }else if($request->order_status == 4){
            $order->order_status = 4;
            $order->order_declined_date = date('Y-m-d');
            $order->save();

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

        if($request->delivery_man_id == 0){
            $order->delivery_man_id = 0;
            $order->order_request = 0;
            $order->save();
        }else if($request->delivery_man_id > 0){
            $order->delivery_man_id = $request->delivery_man_id;
            $order->order_request = 0;
            $order->order_req_date = date('Y-m-d');
            $order->save();
        }



        $notification = trans('admin_validation.Order Status Updated successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
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

        $notification = trans('admin_validation.Delete successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.all-order')->with($notification);
    }

    public function addNewProduct(Request $request,$id)
    {
        $product = Product::find($request->product_id);
        if($product->offer_price == NULL)
        {
            $amount = $product->price;
        }else{
            $amount = $product->offer_price;
        }
        $order_product = new OrderProduct();
        $order_product->order_id = $id;
        $order_product->product_id = $request->product_id;
        $order_product->seller_id = $product->vendor_id;
        $order_product->product_name = $product->name;
        $order_product->unit_price = $amount;
        $order_product->qty = $request->quantity;
        $order_product->save();

        if($product->offer_price == NULL)
        {
            $add_amount = $product->price*$request->quantity;
        }else{
            $add_amount = $product->offer_price*$request->quantity;
        }
        $order = Order::find($id);
        Order::where('id',$id)->update([
            'total_amount' => $order->total_amount + $add_amount
        ]);

        $notification = trans('admin_validation.Order Status Updated successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);

    }

    public function incrementOrderQuantity($id,$order_id)
    {
        $orderProduct = OrderProduct::find($id);
        OrderProduct::where('id',$id)->update([
            'qty' => $orderProduct->qty + 1
        ]);

        $order = Order::find($order_id);
        Order::where('id',$order_id)->update([
            'total_amount' => $order->total_amount + $orderProduct->unit_price
        ]);

        $notification = trans('admin_validation.Updated successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }
    public function decrementOrderQuantity($id,$order_id)
    {
        $orderProduct = OrderProduct::find($id);

        OrderProduct::where('id',$id)->update([
            'qty' => $orderProduct->qty - 1
        ]);

        $order = Order::find($order_id);
        Order::where('id',$order_id)->update([
            'total_amount' => $order->total_amount - $orderProduct->unit_price
        ]);

        $notification = trans('admin_validation.Updated successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function deleteOrderProduct($id,$order_id)
    {

        $orderProduct =  OrderProduct::find($id);

        $amount = $orderProduct->unit_price * $orderProduct->qty;
        $orderProduct->delete();
        $order = Order::find($order_id);
        Order::where('id',$order_id)->update([
            'total_amount' => $order->total_amount - $amount
        ]);

        $notification = trans('admin_validation.Delete successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }
}
