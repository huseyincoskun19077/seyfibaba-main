<?php

namespace App\Http\Controllers\User;

use Log;
use Auth;
use Cart;
use Session;
use App\Models\City;
use App\Models\User;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Vendor;
use App\Models\Address;
use App\Models\Country;
use App\Support\OrderQuantityHelper;
use App\Services\BuyerInvoiceService;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shipping;
use App\Models\Wishlist;
use App\Models\FlashSale;
use App\Models\TwilioSms;
use App\Models\BiztechSms;
use App\Helpers\MailHelper;
use App\Models\BankPayment;
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
use App\Models\BreadcrumbImage;
use App\Models\FlashSaleProduct;
use App\Models\ProductVariantItem;
use App\Models\OrderProductVariant;
use App\Models\ShoppingCartVariant;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class CheckoutWithoutTokenController extends Controller
{
    public function checkout(Request $request)
    {

        $shippings = Shipping::all();

        $couponOffer = '';
        if ($request->coupon) {
            $coupon = Coupon::where(['code' => $request->coupon, 'status' => 1])->first();
            if ($coupon) {
                if ($coupon->expired_date >= date('Y-m-d')) {
                    if ($coupon->apply_qty <  $coupon->max_quantity) {
                        $couponOffer = $coupon;
                    }
                }
            }
        }



        $bankPaymentInfo = BankPayment::first();
        $iyzico = \App\Models\IyzicoPayment::select('status','marketplace_mode','is_test_mode')->first();

        return response()->json([
            'shippings' => $shippings,
            'couponOffer' => $couponOffer,
            'bankPaymentInfo' => $bankPaymentInfo,
            'iyzico' => $iyzico,
        ], 200);
    }


    public function cashOnDelivery(Request $request)
    {

        $currency = MultiCurrency::where('is_default', 'Yes')->first();

        //  $setting = Setting::first();
        //  if ($setting->map_status == 0) {
        //      $rules = [
        //          'shipping_method_id'=>'required',
        //      ];
        //      $customMessages = [
        //          'shipping_method_id.required' => trans('user_validation.Shipping method is required'),
        //      ];
        //  }

        //  $this->validate($request, $rules,$customMessages);

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
        $total = $this->calculateCartTotal($user, $cartProducts, $request->coupon, $request->shipping_method_id, $request->address);

        if ($total instanceof \Illuminate\Http\JsonResponse) {
            return $total; // Return the JSON response directly
        }

        $total_price = $total['total_price'];
        $coupon_price = $total['coupon_price'];
        $shipping_fee = $total['shipping_fee'];
        $productWeight = $total['productWeight'];
        $shipping = $total['shipping'];

        // $totalProduct = ShoppingCart::with('variants')->where('user_id', $user->id)->sum('qty');

        $transaction_id = 'cash_on_delivery';
        $is_draft = 'no';
        $addressInfo = $this->mergeGuestAddressWithInvoice($request);
        $invoiceData = app(BuyerInvoiceService::class)->validateFromAddressInfo($addressInfo, true);
        $order_result = $this->orderStore($total_price, $cartProducts, $totalProduct, 'Cash on Delivery', $transaction_id, 0, $shipping, $shipping_fee, $coupon_price, 1, $addressInfo, $is_draft, $invoiceData);

        //  return $order_result;

        $this->sendOrderSuccessMail($user, $total_price, 'Cash on Delivery', 0, $order_result['order'], $order_result['order_details']);
        app(PaymentController::class)->sendOrderNotificationToSellers($order_result['order']);

        $this->sendOrderSuccessSms($user, $order_result['order']);

        $notification = trans('user_validation.Order submited successfully. please wait for admin approval');

        $order = $order_result['order'];
        $order_id = $order->order_id;

        return response()->json(['message' => $notification, 'order_id' => $order_id], 200);
    }


    public function store_draft_order(Request $request)
    {

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


        $total = $this->calculateCartTotal($user, $cartProducts, $request->coupon, $request->shipping_method_id, $request->address);

        if ($total instanceof \Illuminate\Http\JsonResponse) {
            return $total; // Return the JSON response directly
        }

        $total_price = $total['total_price'];
        $coupon_price = $total['coupon_price'];
        $shipping_fee = $total['shipping_fee'];
        $productWeight = $total['productWeight'];
        $shipping = $total['shipping'];

        // $totalProduct = ShoppingCart::with('variants')->where('user_id', $user->id)->sum('qty');

        $transaction_id = 'cash_on_delivery';
        $is_draft = 'yes';
        $addressInfo = $this->mergeGuestAddressWithInvoice($request);
        $invoiceData = app(BuyerInvoiceService::class)->validateFromAddressInfo($addressInfo, true);
        $order_result = $this->orderStore($total_price, $cartProducts, $totalProduct, 'draft', 'draft', 0, $shipping, $shipping_fee, $coupon_price, 1, $addressInfo, $is_draft, $invoiceData);

        //  return $order_result;

        // $this->sendOrderSuccessMail($user, $total_price, 'Cash on Delivery', 0, $order_result['order'], $order_result['order_details']);

        // $this->sendOrderSuccessSms($user, $order_result['order']);

        $notification = trans('Order submited successfully. please make payment now');

        $order = $order_result['order'];
        $order_id = $order->order_id;

        return response()->json(['message' => $notification, 'order_id' => $order_id], 200);
    }

    public function payWithBank(Request $request)
    {
        $rules = [
            'tnx_info' => 'required',
        ];
        $customMessages = [
            'shipping_address_id.required' => trans('user_validation.Shipping address is required'),
            'billing_address_id.required' => trans('user_validation.Billing address is required'),
        ];

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
        $total = $this->calculateCartTotal($user, $cartProducts, $request->coupon, $request->shipping_method_id, $request->address);

        $total_price = $total['total_price'];
        $coupon_price = $total['coupon_price'];
        $shipping_fee = $total['shipping_fee'];
        $productWeight = $total['productWeight'];
        $shipping = $total['shipping'];

        //  $totalProduct = ShoppingCart::with('variants')->where('user_id', $user->id)->sum('qty');

        $setting = Setting::first();

        $amount_real_currency = $total_price;
        $amount_usd = round($total_price / $setting->currency->currency_rate, 2);
        $currency_rate = $setting->currency->currency_rate;
        $currency_icon = $setting->currency->currency_icon;
        $currency_name = $setting->currency->currency_name;


        $is_draft = 'no';
        $transaction_id = $request->tnx_info;
        $addressInfo = $this->mergeGuestAddressWithInvoice($request);
        $invoiceData = app(BuyerInvoiceService::class)->validateFromAddressInfo($addressInfo, true);
        $order_result = $this->orderStore($total_price, $cartProducts,  $totalProduct, 'bankpayment', $transaction_id, 0, $shipping, $shipping_fee, $coupon_price, 1, $addressInfo, $is_draft, $invoiceData);

        $this->sendOrderSuccessMail($user, $total_price, 'Bank Payment', 0, $order_result['order'], $order_result['order_details']);

        $this->sendOrderSuccessSms($user, $order_result['order']);

        $notification = trans('user_validation.Order submited successfully. please wait for admin approval');

        $order = $order_result['order'];
        $order_id = $order->order_id;

        return response()->json(['message' => $notification, 'order_id' => $order_id], 200);
    }

    public function calculateCartTotal($user, $cartProducts, $request_coupon, $request_shipping_method_id, $address)
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
            //  $price = $product['offer_price ']? $product['offer_price'] : $product['price'];
            $price = $product->offer_price ? $product->offer_price : $product->price;
            $price = $price + $variantPrice;
            $weight = $product->weight;
            $weight = $weight * $cartProduct['qty'];
            $productWeight += $weight;
            //  $isFlashSale = FlashSaleProduct::where(['product_id' => $product['id'],'status' => 1])->first();
            $isFlashSale = FlashSaleProduct::where(['product_id' => $product->id, 'status' => 1])->first();
            $today = date('Y-m-d H:i:s');
            if ($isFlashSale) {
                $flashSale = FlashSale::first();
                if ($flashSale->status == 1) {
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

        if ($setting->map_status == 1) {

            $vendorIds = $cartProducts->pluck('product.vendor_id')->unique();
            $vendor_id = $vendorIds[0];

            if ($vendor_id == 0) {
                $vendor_lat_long = Admin::where('id', 1)->select('latitude', 'longitude')->first();
            } else {
                $find_user = Vendor::where('id', $vendor_id)->select('user_id')->first();
                $vendor_lat_long = User::where('id', $find_user->user_id)->select('id', 'latitude', 'longitude')->first();
            }

            //  $address = Address::where(['id' => $shipping_address_id])->first();
            // $distance = $this->calculateDistance(
            //     $vendor_lat_long['latitude'],
            //     $vendor_lat_long['longitude'],
            //     $shipping_address['latitude'],
            //     $shipping_address['longitude'],
            // );
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

        $distance = $earthRadius * $c;

        return $distance;
    }


    private function generateUniqueUserId(): int
    {
        do {
            $userId = random_int(100000, 999999);

            $userExists = User::where('id', $userId)->exists();
        } while ($userExists);

        return $userId;
    }


    public function orderStore($total_price, $cartProducts, $totalProduct, $payment_method, $transaction_id, $paymetn_status, $shipping, $shipping_fee, $coupon_price, $cash_on_delivery, $address_info, $is_draft, ?array $invoiceData = null)
    {

        if ($cartProducts->count() == 0) {
            $notification = trans('user_validation.Your shopping cart is empty');
            return response()->json(['message' => $notification], 403);
        }
        //  $address_info = Address::where('country_id',$address['country_id'])->where('state_id',$address['state_id'])->where('city_id',$address['city_id'])->where('address',$address['address'])->where('phone',$address['phone'])->where('name',$address['name'])->first();
        $setting = Setting::first();


        $order = new Order();
        $orderId = substr(rand(0, time()), 0, 10);
        $order->order_id = $orderId;
        $order->user_id = $this->generateUniqueUserId();
        $order->total_amount = $total_price;
        $order->product_qty = $totalProduct;
        $order->payment_method = $payment_method;
        $order->transection_id = $transaction_id;
        $order->payment_status = $paymetn_status;
        if ($setting->map_status == 1) {
            $order->shipping_method = 0;
            $order->latitude = $shipping->latitude;
            $order->longitude = $shipping->longitude;
        } else {
            $order->shipping_method = $shipping->shipping_rule;
        }
        $order->shipping_cost = $shipping_fee;
        $order->coupon_coast = $coupon_price;
        $order->order_status = 0;
        $order->cash_on_delivery = $cash_on_delivery;
        $order->is_draft = $is_draft;
        
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
        foreach ($cartProducts as $key => $cartProduct) {


            // if (!empty($cartProduct['variants']) && is_array($cartProduct['variants'])) {
            //     foreach ($cartProduct['variants'] as $item_index => $var_item) {
            //         $item = ProductVariantItem::find($var_item['variant_item_id']);
            //         if ($item) {
            //             $variantPrice += $item->price;
            //         }
            //     }
            // }

            // Log::info('variants ');
            // Log::info($cartProduct['variants']);

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
            $price = $product->offer_price ? $product->offer_price : $product->price;
            $price = $price + $variantPrice;
            $isFlashSale = FlashSaleProduct::where(['product_id' => $product->id, 'status' => 1])->first();
            $today = date('Y-m-d H:i:s');
            if ($isFlashSale) {
                $flashSale = FlashSale::first();
                if ($flashSale->status == 1) {
                    if ($today <= $flashSale->end_time) {
                        $offerPrice = ($flashSale->offer / 100) * $price;
                        $price = $price - $offerPrice;
                    }
                }
            }

            $orderProduct = new OrderProduct();
            $orderProduct->order_id = $order->id;
            $orderProduct->product_id = $cartProduct['product_id'];
            $orderProduct->seller_id = $product->vendor_id;
            $orderProduct->product_name = $product->name;
            $orderProduct->unit_price = $price;
            $orderProduct->qty = $cartProduct['qty'];
            $orderProduct->save();

            // Only update stock when payment is confirmed (payment_status = 1)
            // Bank transfer (payment_status = 0) waits for admin approval
            if ($paymetn_status == 1) {
                $qty = $product->qty - $totalProduct;
                $product->qty = $qty;
                $product->save();
            }

            // return $cartProduct->variants;
            if (!empty($cartProduct['variants']) && is_array($cartProduct['variants'])) {
                foreach ($cartProduct['variants'] as $index => $variant) {
                    $item = ProductVariantItem::find($variant['variant_item_id']);
                    $productVariant = new OrderProductVariant();
                    $productVariant->order_product_id = $orderProduct->id;
                    $productVariant->product_id = $cartProduct['product_id'];
                    $productVariant->variant_name = $item->product_variant_name;
                    $productVariant->variant_value = $item->name;
                    $productVariant->save();
                }
            }
            $order_details .= 'Product: ' . $product->name . '<br>';
            $order_details .= 'Quantity: ' . $totalProduct . '<br>';
            $order_details .= 'Price: ' . $currency->currency_icon . $totalProduct * $price . '<br>';
        }


        // Ülke/il/ilçe: numeric ID veya string isim olarak gelebilir — her ikisini de destekle
        $countryVal = $address_info['country'] ?? null;
        $stateVal   = $address_info['state']   ?? null;
        $cityVal    = $address_info['city']    ?? null;

        $address_country = is_numeric($countryVal)
            ? Country::where('id', (int) $countryVal)->first()
            : Country::where('name', $countryVal)->first();

        $address_state = is_numeric($stateVal)
            ? CountryState::where('id', (int) $stateVal)->first()
            : CountryState::where('name', $stateVal)->first();

        $address_city = is_numeric($cityVal)
            ? City::where('id', (int) $cityVal)->first()
            : City::where('name', $cityVal)->first();

        // İsimden bulunamazsa değerin kendisini doğrudan kullan (fallback)
        $countryName = $address_country?->name ?? (is_string($countryVal) ? $countryVal : '');
        $stateName   = $address_state?->name   ?? (is_string($stateVal)   ? $stateVal   : '');
        $cityName    = $address_city?->name    ?? (is_string($cityVal)    ? $cityVal    : '');

        $orderAddress = new OrderAddress();
        $orderAddress->order_id = $order->id;
        $orderAddress->billing_name = $address_info['name'] ?? '';
        $orderAddress->billing_email = $address_info['email'] ?? '';
        $orderAddress->billing_phone = $address_info['phone'] ?? '';
        $orderAddress->billing_address = $address_info['address'] ?? '';
        $orderAddress->billing_country = $countryName;
        $orderAddress->billing_state = $stateName;
        $orderAddress->billing_city = $cityName;
        $orderAddress->billing_address_type = $address_info['type'] ?? '';

        $orderAddress->shipping_name = $address_info['name'] ?? '';
        $orderAddress->shipping_email = $address_info['email'] ?? '';
        $orderAddress->shipping_phone = $address_info['phone'] ?? '';
        $orderAddress->shipping_address = $address_info['address'] ?? '';
        $orderAddress->shipping_country = $countryName;
        $orderAddress->shipping_state = $stateName;
        $orderAddress->shipping_city = $cityName;
        $orderAddress->shipping_address_type = $address_info['type'] ?? '';
        if ($invoiceData) {
            app(BuyerInvoiceService::class)->applyToOrderAddress($orderAddress, $invoiceData);
        }
        $orderAddress->save();
        foreach ($cartProducts as $cartProduct) {
            ShoppingCartVariant::where('shopping_cart_id', $cartProduct['product_id'])->delete();
        }
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
        //  $message = str_replace('{{user_name}}',$user->name,$message);
        $message = str_replace('{{total_amount}}', $currency->currency_icon . $total_price, $message);
        $message = str_replace('{{payment_method}}', $payment_method, $message);
        $message = str_replace('{{payment_status}}', $payment_status, $message);
        $message = str_replace('{{order_status}}', 'Pending', $message);
        $message = str_replace('{{order_date}}', $order->created_at->format('d F, Y'), $message);
        $message = str_replace('{{order_detail}}', $order_details, $message);
        //  Mail::to($user->email)->send(new OrderSuccessfully($message,$subject));
    }

    public function sendOrderSuccessSms($user, $order)
    {
        $template = SmsTemplate::where('id', 3)->first();
        $message = $template->description;
        //  $message = str_replace('{{user_name}}',$user->name,$message);
        $message = str_replace('{{order_id}}', $order->order_id, $message);

        $twilio = TwilioSms::first();
        //  if($twilio->enable_order_confirmation_sms == 1){
        //      if($user->phone){
        //          try{
        //              $account_sid = $twilio->account_sid;
        //              $auth_token = $twilio->auth_token;
        //              $twilio_number = $twilio->twilio_phone_number;
        //              $recipients = $user->phone;
        //              $client = new Client($account_sid, $auth_token);
        //              $client->messages->create($recipients,
        //                      ['from' => $twilio_number, 'body' => $message] );
        //          }catch(Exception $ex){

        //          }
        //      }
        //  }

        $biztech = BiztechSms::first();
        //  if($biztech->enable_order_confirmation_sms == 1){
        //      if($user->phone){
        //          try{
        //              $apikey = $biztech->api_key;
        //              $clientid = $biztech->client_id;
        //              $senderid = $biztech->sender_id;
        //              $senderid = urlencode($senderid);
        //              $message = $message;
        //              $msg_type = true;  // true or false for unicode message
        //              $message  = urlencode($message);
        //              $mobilenumbers = $user->phone; //8801700000000 or 8801700000000,9100000000
        //              $url = "https://api.smsq.global/api/v2/SendSMS?ApiKey=$apikey&ClientId=$clientid&SenderId=$senderid&Message=$message&MobileNumbers=$mobilenumbers&Is_Unicode=$msg_type";
        //              $ch = curl_init();
        //              curl_setopt ($ch, CURLOPT_URL, $url);
        //              curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        //              curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        //              curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
        //              curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
        //              curl_setopt($ch, CURLOPT_NOBODY, false);
        //              curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //              $response = curl_exec($ch);
        //              $response = json_decode($response);
        //          }catch(Exception $ex){}
        //      }
        //  }
    }

    private function mergeGuestAddressWithInvoice(Request $request): array
    {
        $address = is_array($request->input('address')) ? $request->input('address') : [];

        return array_merge($address, $request->only([
            'invoice_type',
            'tc_identity',
            'tax_number',
            'tax_office',
        ]));
    }


}
