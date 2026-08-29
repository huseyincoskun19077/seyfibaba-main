<?php

namespace App\Http\Controllers\User;

use Str;
use Auth;
use Cart;
use Mail;
use Session;
use Redirect;
use Exception;
use App\Models\City;
use App\Models\User;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Vendor;
use App\Models\Address;
use App\Models\Country;
use App\Models\Product;
use App\Models\Setting;
use Twilio\Rest\Client;
use App\Models\Shipping;
use App\Models\FlashSale;
use App\Models\TwilioSms;
use App\Models\BiztechSms;
use App\Helpers\MailHelper;
use App\Models\SmsTemplate;
use App\Models\CountryState;
use App\Models\OrderAddress;
use App\Models\OrderProduct;
use App\Models\ShoppingCart;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Models\MultiCurrency;
use App\Models\ProductVariant;
use App\Mail\OrderSuccessfully;
use App\Mail\OrderNotificationToSeller;
use App\Models\BreadcrumbImage;
use App\Models\FlashSaleProduct;
use App\Models\ProductVariantItem;
use App\Models\OrderProductVariant;
use App\Models\ShoppingCartVariant;
use App\Http\Controllers\Controller;
use App\Services\CartCleanupService;
use App\Services\CommissionService;
use App\Support\OrderQuantityHelper;

class PaymentController extends Controller
{
    protected $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
        $this->middleware('auth:api');
    }


    public function cashOnDelivery(Request $request)
    {
        $currency = MultiCurrency::where('is_default', 'Yes')->first();

        $rules = [
            'shipping_address_id' => 'required',
            'billing_address_id' => 'required',
        ];

        $customMessages = [
            'shipping_address_id.required' => trans('user_validation.Shipping address is required'),
            'billing_address_id.required' => trans('user_validation.Billing address is required'),
        ];

        $setting = Setting::first();
        if ($setting->map_status == 0) {
            $rules['shipping_method_id'] = 'required';

            $customMessages = [
                'shipping_method_id.required' => trans('user_validation.Shipping method is required'),
            ];
        }

        $this->validate($request, $rules, $customMessages);

        $user = Auth::guard('api')->user();
        $cartProducts = collect($request->input('cart_products', []));

        $error_array = [];

        foreach ($cartProducts as $index => $cartProduct) {

            if (!empty($cartProduct['variants']) && is_array($cartProduct['variants'])) {
                foreach ($cartProduct['variants'] as $item_index => $var_item) {

                    $variant = ProductVariant::find($var_item['variant_id']);

                    if (!$variant) {
                        $error_array[$index][] = trans('Your requested variant not exist');
                    }

                    $variant_item = ProductVariantItem::find($var_item['variant_item_id']);
                    if (!$variant_item) {
                        $error_array[$index][] = trans('Your requested variant item not exist');
                    }
                }

                $product = Product::find($cartProduct['product_id']);

                if (!$product || $product->status != 1 || $product->approve_by_admin != 1) {
                    $error_array[$index][] = $product ? 'Bu ürün artık satışta değil.' : trans('Your requested product not exist');
                }

                // return $variant_item;
                if ($variant && $variant_item && $product) {
                    if ($variant_item->product_variant_id != $variant->id && $variant_item->product_id != $product->id) {
                        $error_array[$index][] = trans('Deos not have any relation between requested product, variant & item');
                    }
                }
            }
        }

        if (count($error_array) > 0) {
            return response()->json(['message' => trans('Unprocessable  Data'), 'errors' => $error_array], 403);
        }



        $totalProduct = OrderQuantityHelper::fromCartProducts($cartProducts);
        $total = $this->calculateCartTotal($user, $cartProducts, $request->coupon, $request->shipping_method_id, $request->shipping_address_id);

        if ($total instanceof \Illuminate\Http\JsonResponse) {
            return $total; // Return the JSON response directly
        }

        $total_price = $total['total_price'];
        $coupon_price = $total['coupon_price'];
        $shipping_fee = $total['shipping_fee'];
        $productWeight = $total['productWeight'];
        $shipping = $total['shipping'];

        $transaction_id = $request->razorpay_payment_id;
        $is_draft = 'no';
        $order_result = $this->orderStore($user, $total_price, $cartProducts, $totalProduct, 'Cash on Delivery', 'cash_on_delivery', 0, $shipping, $shipping_fee, $coupon_price, 1, $request->billing_address_id, $request->shipping_address_id, $is_draft);

        $this->sendOrderSuccessMail($user, $total_price, 'Cash on Delivery', 0, $order_result['order'], $order_result['order_details']);
        $this->sendOrderNotificationToSellers($order_result['order']);

        $this->sendOrderSuccessSms($user, $order_result['order']);

        app(CartCleanupService::class)->clearPurchasedItemsForUser(
            $user->id,
            $cartProducts->pluck('product_id')
        );

        $notification = trans('user_validation.Order submited successfully. please wait for admin approval');

        $order = $order_result['order'];
        $order_id = $order->order_id;

        return response()->json(['message' => $notification, 'order_id' => $order_id], 200);
    }


    public function store_draft_order(Request $request)
    {
        $rules = [
            'shipping_address_id' => 'required',
            'billing_address_id' => 'required',
        ];
        $customMessages = [
            'shipping_address_id.required' => trans('user_validation.Shipping address is required'),
            'billing_address_id.required' => trans('user_validation.Billing address is required'),
            'card_number.required' => trans('user_validation.Card number is required'),
            'year.required' => trans('user_validation.Year is required'),
            'month.required' => trans('user_validation.Month is required'),
            'cvv.required' => trans('user_validation.Cvv is required'),
        ];

        $setting = Setting::first();
        if ($setting->map_status == 0) {
            $rules['shipping_method_id'] = 'required';
            $customMessages = [
                'shipping_method_id.required' => trans('user_validation.Shipping method is required'),
            ];
        }

        $this->validate($request, $rules, $customMessages);

        $user = Auth::guard('api')->user();
        $cartProducts = collect($request->input('cart_products', []));

        $error_array = [];

        foreach ($cartProducts as $index => $cartProduct) {

            if (!empty($cartProduct['variants']) && is_array($cartProduct['variants'])) {
                foreach ($cartProduct['variants'] as $item_index => $var_item) {

                    $variant = ProductVariant::find($var_item['variant_id']);

                    if (!$variant) {
                        $error_array[$index][] = trans('Your requested variant not exist');
                    }

                    $variant_item = ProductVariantItem::find($var_item['variant_item_id']);
                    if (!$variant_item) {
                        $error_array[$index][] = trans('Your requested variant item not exist');
                    }
                }

                $product = Product::find($cartProduct['product_id']);

                if (!$product || $product->status != 1 || $product->approve_by_admin != 1) {
                    $error_array[$index][] = $product ? 'Bu ürün artık satışta değil.' : trans('Your requested product not exist');
                }

                // return $variant_item;
                if ($variant && $variant_item && $product) {
                    if ($variant_item->product_variant_id != $variant->id && $variant_item->product_id != $product->id) {
                        $error_array[$index][] = trans('Deos not have any relation between requested product, variant & item');
                    }
                }
            }
        }

        if (count($error_array) > 0) {
            return response()->json(['message' => trans('Unprocessable  Data'), 'errors' => $error_array], 403);
        }


        $totalProduct = OrderQuantityHelper::fromCartProducts($cartProducts);
        $total = $this->calculateCartTotal($user, $cartProducts, $request->coupon, $request->shipping_method_id, $request->shipping_address_id);
        $total_price = $total['total_price'];
        $coupon_price = $total['coupon_price'];
        $shipping_fee = $total['shipping_fee'];
        $productWeight = $total['productWeight'];
        $shipping = $total['shipping'];

        $transaction_id = 'draft';
        $is_draft = 'yes';
        $order_result = $this->orderStore($user, $total_price, $cartProducts, $totalProduct, 'draft', $transaction_id, 1, $shipping, $shipping_fee, $coupon_price, 0, $request->billing_address_id, $request->shipping_address_id, $is_draft);
        // $this->sendOrderSuccessMail($user, $total_price, 'Stripe', 1, $order_result['order'], $order_result['order_details']);

        // $this->sendOrderSuccessSms($user, $order_result['order']);


        $notification = trans('Order submited successfully. please make payment now');
        $order = $order_result['order'];
        $order_id = $order->order_id;

        return response()->json(['message' => $notification, 'order_id' => $order_id], 200);
    }

    public function payWithBank(Request $request)
    {
        $rules = [
            'shipping_address_id' => 'required',
            'billing_address_id' => 'required',
            'tnx_info' => 'required',
        ];
        $customMessages = [
            'shipping_address_id.required' => trans('user_validation.Shipping address is required'),
            'billing_address_id.required' => trans('user_validation.Billing address is required'),
        ];

        $setting = Setting::first();
        if ($setting->map_status == 0) {
            $rules['shipping_method_id'] = 'required';
            $customMessages = [
                'shipping_method_id.required' => trans('user_validation.Shipping method is required'),
            ];
        }

        $this->validate($request, $rules, $customMessages);

        $user = Auth::guard('api')->user();
        $cartProducts = collect($request->input('cart_products', []));

        $error_array = [];

        foreach ($cartProducts as $index => $cartProduct) {

            if (!empty($cartProduct['variants']) && is_array($cartProduct['variants'])) {
                foreach ($cartProduct['variants'] as $item_index => $var_item) {

                    $variant = ProductVariant::find($var_item['variant_id']);

                    if (!$variant) {
                        $error_array[$index][] = trans('Your requested variant not exist');
                    }

                    $variant_item = ProductVariantItem::find($var_item['variant_item_id']);
                    if (!$variant_item) {
                        $error_array[$index][] = trans('Your requested variant item not exist');
                    }
                }

                $product = Product::find($cartProduct['product_id']);

                if (!$product || $product->status != 1 || $product->approve_by_admin != 1) {
                    $error_array[$index][] = $product ? 'Bu ürün artık satışta değil.' : trans('Your requested product not exist');
                }

                // return $variant_item;
                if ($variant && $variant_item && $product) {
                    if ($variant_item->product_variant_id != $variant->id && $variant_item->product_id != $product->id) {
                        $error_array[$index][] = trans('Deos not have any relation between requested product, variant & item');
                    }
                }
            }
        }

        if (count($error_array) > 0) {
            return response()->json(['message' => trans('Unprocessable  Data'), 'errors' => $error_array], 403);
        }


        $totalProduct = OrderQuantityHelper::fromCartProducts($cartProducts);
        $total = $this->calculateCartTotal($user, $cartProducts, $request->coupon, $request->shipping_method_id, $request->shipping_address_id);

        $total_price = $total['total_price'];
        $coupon_price = $total['coupon_price'];
        $shipping_fee = $total['shipping_fee'];
        $productWeight = $total['productWeight'];
        $shipping = $total['shipping'];

        $setting = Setting::first();

        $amount_real_currency = $total_price;
        $amount_usd = round($total_price / $setting->currency->currency_rate, 2);
        $currency_rate = $setting->currency->currency_rate;
        $currency_icon = $setting->currency->currency_icon;
        $currency_name = $setting->currency->currency_name;

        $transaction_id = $request->tnx_info;
        $is_draft = 'no';
        $order_result = $this->orderStore($user, $total_price, $cartProducts,  $totalProduct, 'bankpayment', $transaction_id, 0, $shipping, $shipping_fee, $coupon_price, 1, $request->billing_address_id, $request->shipping_address_id, $is_draft);

        try {
            $this->sendOrderSuccessMail($user, $total_price, 'Bank Payment', 0, $order_result['order'], $order_result['order_details']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Bank payment mail gönderilemedi', ['order_id' => $order_result['order']->id, 'error' => $e->getMessage()]);
        }

        try {
            $this->sendOrderSuccessSms($user, $order_result['order']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Bank payment SMS gönderilemedi', ['order_id' => $order_result['order']->id, 'error' => $e->getMessage()]);
        }

        app(CartCleanupService::class)->clearPurchasedItemsForUser(
            $user->id,
            $cartProducts->pluck('product_id')
        );

        $notification = trans('user_validation.Order submited successfully. please wait for admin approval');

        $order = $order_result['order'];
        $order_id = $order->order_id;

        return response()->json(['message' => $notification, 'order_id' => $order_id], 200);
    }


    public function calculateCartTotal($user, $cartProducts, $request_coupon, $request_shipping_method_id, $shipping_address_id)
    {

        $total_price = 0;
        $coupon_price = 0;
        $shipping_fee = 0;
        $productWeight = 0;

        // $cartProducts = ShoppingCart::with('product','variants.variantItem')->where('user_id', $user->id)->select('id','product_id','qty')->get();
        if ($cartProducts->count() == 0) {
            $notification = trans('user_validation.Your shopping cart is empty');
            return response()->json(['message' => $notification], 403);
        }
        foreach ($cartProducts as $index => $cartProduct) {
            $variantPrice = 0;

            if (isset($cartProduct['product'])) {
            }

            if (!empty($cartProduct['variants']) && is_array($cartProduct['variants'])) {
                foreach ($cartProduct['variants'] as $item_index => $var_item) {
                    $item = ProductVariantItem::find($var_item['variant_item_id']);
                    if ($item) {
                        $variantPrice += $item->price;
                    }
                }
            }

            $product = Product::select('id', 'price', 'offer_price', 'weight')->find($cartProduct['product_id']);
            if (!$product) {
                return response()->json([
                    'message' => 'Sepetteki ürün bulunamadı (ID: ' . ($cartProduct['product_id'] ?? '?') . ')',
                ], 422);
            }
            $price = $product->offer_price ? $product->offer_price : $product->price;
            $price = $price + $variantPrice;
            $weight = $product->weight;
            $weight = $weight * $cartProduct['qty'];
            $productWeight += $weight;
            $isFlashSale = FlashSaleProduct::where(['product_id' => $product->id, 'status' => 1])->first();
            $today = date('Y-m-d H:i:s');
            if ($isFlashSale) {
                $flashSale = FlashSale::first();
                if ($flashSale && (int) $flashSale->status === 1) {
                    if ($today <= $flashSale->end_time) {
                        $offerPrice = ($flashSale->offer / 100) * $price;
                        $price = $price - $offerPrice;
                    }
                }
            }

            $price = $price * $cartProduct['qty'];
            $total_price += $price;
        }

        // calculate coupon coast
        if ($request_coupon) {
            $coupon = Coupon::where(['code' => $request_coupon, 'status' => 1])->first();
            if ($coupon) {
                if ($coupon->expired_date >= date('Y-m-d')) {
                    if ($coupon->apply_qty <  $coupon->max_quantity) {
                        if ($coupon->offer_type == 1) {
                            $couponAmount = $coupon->discount;
                            $couponAmount = ($couponAmount / 100) * $total_price;
                        } elseif ($coupon->offer_type == 2) {
                            $couponAmount = $coupon->discount;
                        }
                        $coupon_price = $couponAmount;

                        $qty = $coupon->apply_qty;
                        $qty = $qty + 1;
                        $coupon->apply_qty = $qty;
                        $coupon->save();
                    }
                }
            }
        }

        $setting = Setting::first();
        if (!$setting) {
            return response()->json(['message' => 'Site ayarlari yuklenemedi.'], 422);
        }

        if ((int) $setting->map_status === 1) {

            $vendorIds = $cartProducts->pluck('product.vendor_id')->filter()->unique();
            $vendor_id = $vendorIds->first() ?? 0;

            if ($vendor_id == 0) {
                $vendor_lat_long = Admin::where('id', 1)->select('latitude', 'longitude')->first();
            } else {
                $find_user = Vendor::where('id', $vendor_id)->select('user_id')->first();
                $vendor_lat_long = $find_user
                    ? User::where('id', $find_user->user_id)->select('id', 'latitude', 'longitude')->first()
                    : null;
            }

            $address = Address::where(['id' => $shipping_address_id])->first();
            if (!$address || !$vendor_lat_long) {
                return response()->json(['message' => 'Kargo hesaplamasi icin adres veya konum bilgisi eksik.'], 422);
            }

            $distance = $this->calculateDistance(
                $vendor_lat_long['latitude'],
                $vendor_lat_long['longitude'],
                $address->latitude,
                $address->longitude
            );

            $shipping_fee = $distance * $setting->per_km_price_range;
            $shipping = '';
        } else {
            $shipping = Shipping::find($request_shipping_method_id);
            if (!$shipping) {
                return response()->json(['message' => trans('user_validation.Shipping method not found')], 403);
            }

            if ($shipping->shipping_fee == 0) {
                $shipping_fee = 0;
            } else {
                $shipping_fee = $shipping->shipping_fee;
            }
        }


        $total_price = ($total_price - $coupon_price) + $shipping_fee;
        $total_price = str_replace(array('\'', '"', ',', ';', '<', '>'), '', $total_price);
        $total_price = number_format($total_price, 2, '.', '');

        $arr = [];
        $arr['total_price'] = $total_price;
        $arr['coupon_price'] = $coupon_price;
        $arr['shipping_fee'] = $shipping_fee;
        $arr['productWeight'] = $productWeight;
        $arr['shipping'] = $shipping;

        return $arr;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radius of the Earth in kilometers

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $latDifference = $lat2 - $lat1;
        $lonDifference = $lon2 - $lon1;

        $a = sin($latDifference / 2) * sin($latDifference / 2) +
            cos($lat1) * cos($lat2) *
            sin($lonDifference / 2) * sin($lonDifference / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c; // Distance in kilometers

        return $distance;
    }


    public function orderStore($user, $total_price, $cartProducts, $totalProduct, $payment_method, $transaction_id, $paymetn_status, $shipping, $shipping_fee, $coupon_price, $cash_on_delivery, $billing_address_id, $shipping_address_id, $is_draft)
    {
        // $cartProducts = ShoppingCart::with('product','variants.variantItem')->where('user_id', $user->id)->select('id','product_id','qty')->get();
        if ($cartProducts->count() == 0) {
            $notification = trans('user_validation.Your shopping cart is empty');
            return response()->json(['message' => $notification], 403);
        }
        $billingAddress = Address::find($billing_address_id);
        $shippingAddress = Address::find($shipping_address_id);
        if (!$billingAddress || !$shippingAddress) {
            return response()->json(['message' => 'Adres bulunamadi.'], 422);
        }
        $setting = Setting::first();
        if (!$setting) {
            return response()->json(['message' => 'Site ayarlari yuklenemedi.'], 422);
        }
        $order = new Order();
        $orderId = substr(rand(0, time()), 0, 10);
        $order->order_id = $orderId;
        $order->user_id = $user->id;
        $order->total_amount = $total_price;
        $order->product_qty = $totalProduct;
        $order->payment_method = $payment_method;
        $order->transection_id = $transaction_id;
        $order->payment_status = $paymetn_status;
        $order->is_draft = $is_draft;
        if ($setting->map_status == 1) {
            $order->shipping_method = 0;
            $order->latitude = $shippingAddress->latitude;
            $order->longitude = $shippingAddress->longitude;
        } else {
            $order->shipping_method = ($shipping && is_object($shipping)) ? $shipping->shipping_rule : 0;
        }
        $order->shipping_cost = $shipping_fee;
        $order->coupon_coast = $coupon_price;
        $order->order_status = 0;
        $order->cash_on_delivery = $cash_on_delivery;
        
        // Bank transfer discount
        if ($payment_method == 'bankpayment') {
            $setting = Setting::first();
            $discountPercent = $setting->bank_transfer_discount_percent ?? 3;
            $discountAmount = ($total_price * $discountPercent) / 100;
            $order->discount_amount = $discountAmount;
            $order->discount_type = 'bank_transfer';
            // Store the discounted total as the actual amount paid
            $order->total_amount = $total_price - $discountAmount;
        }
        
        $order->save();
        $order_details = '';
        $currency = MultiCurrency::where('is_default', 'Yes')->first();
        $currencyIcon = $currency->currency_icon ?? '₺';
        foreach ($cartProducts as $key => $cartProduct) {

            $variantPrice = 0;
            if (!empty($cartProduct['variants']) && is_array($cartProduct['variants'])) {
                foreach ($cartProduct['variants'] as $item_index => $var_item) {
                    $item = ProductVariantItem::find($var_item['variant_item_id']);
                    if ($item) {
                        $variantPrice += $item->price;
                    }
                }
            }

            // calculate product price
            $product = Product::select('id', 'price', 'offer_price', 'weight', 'vendor_id', 'qty', 'name')->find($cartProduct['product_id']);
            if (!$product) {
                return response()->json([
                    'message' => 'Sepetteki ürün bulunamadı (ID: ' . ($cartProduct['product_id'] ?? '?') . ')',
                ], 422);
            }
            $price = $product->offer_price ? $product->offer_price : $product->price;
            $price = $price + $variantPrice;
            $isFlashSale = FlashSaleProduct::where(['product_id' => $product->id, 'status' => 1])->first();
            $today = date('Y-m-d H:i:s');
            if ($isFlashSale) {
                $flashSale = FlashSale::first();
                if ($flashSale && (int) $flashSale->status === 1) {
                    if ($today <= $flashSale->end_time) {
                        $offerPrice = ($flashSale->offer / 100) * $price;
                        $price = $price - $offerPrice;
                    }
                }
            }

            // store ordre product
            $orderProduct = new OrderProduct();
            $orderProduct->order_id = $order->id;
            $orderProduct->product_id = $cartProduct['product_id'];
            $orderProduct->seller_id = $product->vendor_id;
            $orderProduct->product_name = $product->name;
            $orderProduct->unit_price = $price;
            $orderProduct->qty = $cartProduct['qty'] ?? 1;
            $orderProduct->save();

            // record commission
            $this->commissionService->recordCommission($orderProduct, $order);

            // Only update stock when payment is confirmed (payment_status = 1)
            // Bank transfer (payment_status = 0) waits for admin approval
            if ($paymetn_status == 1) {
                $decrementBy = (int) ($orderProduct->qty ?? 1);
                $product->qty = max(0, (int) $product->qty - $decrementBy);
                $product->save();
            }

            // store prouct variant

            // return $cartProduct->variants;
            if (!empty($cartProduct['variants']) && is_array($cartProduct['variants'])) {
                foreach ($cartProduct['variants'] as $index => $variant) {
                    $item = ProductVariantItem::find($variant['variant_item_id']);
                    if (!$item) {
                        continue;
                    }
                    $productVariant = new OrderProductVariant();
                    $productVariant->order_product_id = $orderProduct->id;
                    $productVariant->product_id = $cartProduct['product_id'];
                    $productVariant->variant_name = $item->product_variant_name;
                    $productVariant->variant_value = $item->name;
                    $productVariant->save();
                }
            }
            $order_details .= 'Product: ' . $product->name . '<br>';
            $lineQty = (int) ($orderProduct->qty ?? 1);
            $order_details .= 'Quantity: ' . $lineQty . '<br>';
            $order_details .= 'Price: ' . $currencyIcon . ($lineQty * $price) . '<br>';
        }

        // store shipping and billing address

        $orderAddress = new OrderAddress();
        $orderAddress->order_id = $order->id;
        $orderAddress->billing_name = $billingAddress->name;
        $orderAddress->billing_email = $billingAddress->email;
        $orderAddress->billing_phone = $billingAddress->phone;
        $orderAddress->billing_address = $billingAddress->address;
        $orderAddress->billing_country = optional($billingAddress->country)->name ?? '';
        $orderAddress->billing_state = optional($billingAddress->countryState)->name ?? '';
        $orderAddress->billing_city = optional($billingAddress->city)->name ?? '';
        $orderAddress->billing_address_type = $billingAddress->type;
        $orderAddress->shipping_name = $shippingAddress->name;
        $orderAddress->shipping_email = $shippingAddress->email;
        $orderAddress->shipping_phone = $shippingAddress->phone;
        $orderAddress->shipping_address = $shippingAddress->address;
        $orderAddress->shipping_country = optional($shippingAddress->country)->name ?? '';
        $orderAddress->shipping_state = optional($shippingAddress->countryState)->name ?? '';
        $orderAddress->shipping_city = optional($shippingAddress->city)->name ?? '';
        $orderAddress->shipping_address_type = $shippingAddress->type;
        $orderAddress->save();
        // Sepet temizleme: frontend başarılı siparişten sonra Redux sepetini temizliyor.
        // Buradaki eski silme kodu (shopping_cart_id yerine product_id) hatalı eşleşmeye yol açıyordu.
        $arr = [];
        $arr['order'] = $order;
        $arr['order_details'] = $order_details;

        return $arr;
    }


    public function sendOrderSuccessMail($user, $total_price, $payment_method, $payment_status, $order, $order_details)
    {
        $currency = MultiCurrency::where('is_default', 'Yes')->first();
        MailHelper::setMailConfig();

        $template = EmailTemplate::where('id', 6)->first();
        $subject = $template->subject;
        $message = $template->description;
        $message = str_replace('{{user_name}}', $user->name, $message);
        $message = str_replace('{{total_amount}}', $currency->currency_icon . $total_price, $message);
        $message = str_replace('{{payment_method}}', $payment_method, $message);
        $message = str_replace('{{payment_status}}', $payment_status, $message);
        $message = str_replace('{{order_status}}', 'Pending', $message);
        $message = str_replace('{{order_date}}', $order->created_at->format('d F, Y'), $message);
        $message = str_replace('{{order_detail}}', $order_details, $message);
        Mail::to($user->email)->send(new OrderSuccessfully($message, $subject));
    }

    public function sendOrderNotificationToSellers($order)
    {
        try {
            MailHelper::setMailConfig();
            $order->loadMissing(['orderProducts.product', 'user']);
            $sellerIds = $order->orderProducts->pluck('seller_id')->unique()->filter();

            foreach ($sellerIds as $sellerId) {
                $vendor = Vendor::find($sellerId);
                if (!$vendor) continue;
                $sellerUser = User::find($vendor->user_id);
                if (!$sellerUser || !$sellerUser->email) continue;

                $lines = $order->orderProducts->where('seller_id', $sellerId);
                $productList = $lines->map(fn ($l) => ($l->product->name ?? 'Ürün') . ' x' . ($l->qty ?? 1))->implode("\n");
                $currency = MultiCurrency::where('is_default', 'Yes')->first();
                $icon = $currency->currency_icon ?? '₺';

                $content = "Mağazanıza yeni sipariş geldi.\n\nSipariş No: {$order->order_id}\nMüşteri: {$order->user->name}\n\nÜrünler:\n{$productList}\n\nSatıcı panelinden siparişi onaylayabilirsiniz.";

                Mail::to($sellerUser->email)->send(new OrderNotificationToSeller($content, "Yeni Sipariş #{$order->order_id} — Seyfibaba"));
            }
        } catch (\Throwable $e) {
            \Log::warning('Seller order notification mail failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    public function sendOrderSuccessSms($user, $order)
    {
        $template = SmsTemplate::where('id', 3)->first();
        $message = $template->description;
        $message = str_replace('{{user_name}}', $user->name, $message);
        $message = str_replace('{{order_id}}', $order->order_id, $message);

        $twilio = TwilioSms::first();
        if ($twilio->enable_order_confirmation_sms == 1) {
            if ($user->phone) {
                try {
                    $account_sid = $twilio->account_sid;
                    $auth_token = $twilio->auth_token;
                    $twilio_number = $twilio->twilio_phone_number;
                    $recipients = $user->phone;
                    $client = new Client($account_sid, $auth_token);
                    $client->messages->create(
                        $recipients,
                        ['from' => $twilio_number, 'body' => $message]
                    );
                } catch (Exception $ex) {
                }
            }
        }

        $biztech = BiztechSms::first();
        if ($biztech->enable_order_confirmation_sms == 1) {
            if ($user->phone) {
                try {
                    $apikey = $biztech->api_key;
                    $clientid = $biztech->client_id;
                    $senderid = $biztech->sender_id;
                    $senderid = urlencode($senderid);
                    $message = $message;
                    $msg_type = true;  // true or false for unicode message
                    $message  = urlencode($message);
                    $mobilenumbers = $user->phone; //8801700000000 or 8801700000000,9100000000
                    $url = "https://api.smsq.global/api/v2/SendSMS?ApiKey=$apikey&ClientId=$clientid&SenderId=$senderid&Message=$message&MobileNumbers=$mobilenumbers&Is_Unicode=$msg_type";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_NOBODY, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $response = curl_exec($ch);
                    $response = json_decode($response);
                } catch (Exception $ex) {
                }
            }
        }
    }
}
