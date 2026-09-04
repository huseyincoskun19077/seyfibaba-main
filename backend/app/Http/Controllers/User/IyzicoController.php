<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\IyzicoPayment;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariantItem;
use App\Models\Setting;
use App\Models\Vendor;
use App\Services\CartCleanupService;
use App\Services\CategoryInstallmentService;
use App\Services\BuyerInvoiceService;
use App\Services\IyzicoService;
use App\Support\OrderQuantityHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class IyzicoController extends Controller
{
    private IyzicoService $iyzicoService;

    private CategoryInstallmentService $installmentService;

    public function __construct(IyzicoService $iyzicoService, CategoryInstallmentService $installmentService)
    {
        $this->iyzicoService = $iyzicoService;
        $this->installmentService = $installmentService;
        $this->middleware('auth:api')->except(['callback', 'createGuestCheckoutSession']);
    }

    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'shipping_address_id' => 'required|integer',
            'billing_address_id' => 'required|integer',
            'shipping_method_id' => 'required|integer',
            'cart_products' => 'required|array|min:1',
        ]);

        $user = Auth::guard('api')->user();

        // Frontend Redux sepetini doğrudan kullan (PaymentController ile aynı yaklaşım)
        // shopping_carts DB tablosunu sorgulamak yerine request'ten gelen cart_products dizisini kullan
        $cartProducts = collect($request->input('cart_products', []));

        if ($cartProducts->count() == 0) {
            return response()->json(['message' => trans('user_validation.Your shopping cart is empty')], 403);
        }

        $iyzicoConfig = IyzicoPayment::first();
        if (!$iyzicoConfig || !$iyzicoConfig->status) {
            return response()->json(['message' => 'Iyzico ödeme yöntemi aktif değil.'], 422);
        }

        try {
            $paymentController = app(PaymentController::class);
            $totalInfo = $paymentController->calculateCartTotal(
                $user,
                $cartProducts,
                $request->coupon,
                $request->shipping_method_id,
                $request->shipping_address_id
            );

            if ($totalInfo instanceof \Illuminate\Http\JsonResponse) {
                return $totalInfo;
            }

            $totalPrice = $totalInfo['total_price'];
            $shippingFee = $totalInfo['shipping_fee'];
            $couponPrice = $totalInfo['coupon_price'];
            $totalProduct = OrderQuantityHelper::fromCartProducts($cartProducts);

            $invoiceData = app(BuyerInvoiceService::class)->validateFromRequest($request, $user, true);

            $orderResult = $paymentController->orderStore(
                $user,
                $totalPrice,
                $cartProducts,
                $totalProduct,
                'Iyzico',
                null,
                0,
                $totalInfo['shipping'],
                $shippingFee,
                $couponPrice,
                0,
                $request->billing_address_id,
                $request->shipping_address_id,
                'yes',
                $invoiceData
            );

            if ($orderResult instanceof \Illuminate\Http\JsonResponse) {
                return $orderResult;
            }

            $order = $orderResult['order'];
            $shippingAddress = Address::with('country', 'countryState', 'city')->find($request->shipping_address_id);
            $billingAddress = Address::with('country', 'countryState', 'city')->find($request->billing_address_id);

            if (!$shippingAddress || !$billingAddress) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fatura veya teslimat adresi bulunamadi.',
                ], 422);
            }

            $conversationId = 'order_' . $order->id;
            $amount = number_format((float)$totalPrice, 2, '.', '');

            $callbackUrl = $this->resolveIyzicoCallbackUrl((int) $order->id);
            $basketItems = $this->buildGuestBasketItems($order, $cartProducts, $iyzicoConfig);

            if (empty($basketItems)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sepet Iyzico icin islenemedi.',
                ], 422);
            }

            try {
                $enabledInstallments = $this->installmentService->enabledInstallmentsForCart($cartProducts);
            } catch (\Throwable $installmentError) {
                Log::warning('Iyzico installment computation failed, using single payment', [
                    'order_id' => $order->id,
                    'error' => $installmentError->getMessage(),
                ]);
                $enabledInstallments = [1];
            }

            // Debug: kategori bazlı taksit kısıtı kanıtı (kart verisi içermez)
            try {
                $debugItems = [];
                foreach ($cartProducts as $cp) {
                    $pid = $cp['product_id'] ?? null;
                    if (! $pid) continue;
                    $p = Product::query()->select('id', 'name', 'category_id', 'sub_category_id')->find($pid);
                    if (! $p) continue;
                    $debugItems[] = [
                        'product_id' => (int) $p->id,
                        'category_id' => (int) ($p->category_id ?? 0),
                        'sub_category_id' => (int) ($p->sub_category_id ?? 0),
                        'qty' => (int) ($cp['qty'] ?? 1),
                    ];
                }
                Log::info('Iyzico enabled installments computed', [
                    'order_id' => (int) $order->id,
                    'order_number' => (string) $order->order_id,
                    'enabled_installments' => $enabledInstallments,
                    'items' => $debugItems,
                    'iyzico_categories' => collect($basketItems)
                        ->filter(fn ($i) => str_starts_with((string) ($i['id'] ?? ''), 'PROD-'))
                        ->map(fn ($i) => [
                            'id' => $i['id'] ?? null,
                            'category_1' => $i['category_1'] ?? null,
                            'category_2' => $i['category_2'] ?? null,
                        ])
                        ->values()
                        ->all(),
                ]);
            } catch (\Throwable $e) {
                // ignore
            }

            $basketItems = $this->normalizeBasketItemsForIyzico($basketItems, $iyzicoConfig, (int) $order->id);

            // Kargo ücretini basket items'a ekle — Iyzico, basket toplamı = price olmasını zorunlu kılar
            if ((float)$shippingFee > 0) {
                $basketItems[] = [
                    'id' => 'SHIPPING-' . $order->id,
                    'name' => 'Kargo Ücreti',
                    'category_1' => 'Kargo',
                    'category_2' => 'Kargo',
                    'item_type' => 'VIRTUAL',
                    'price' => number_format((float)$shippingFee, 2, '.', ''),
                ];
            }

            $basketItems = $this->alignBasketToPaidAmount($basketItems, $amount);

            $basketTotal = collect($basketItems)->sum(fn ($item) => (float) ($item['price'] ?? 0));
            if (abs($basketTotal - (float) $amount) > 0.02) {
                Log::warning('Iyzico basket total mismatch', [
                    'order_id' => $order->id,
                    'basket_total' => $basketTotal,
                    'order_total' => $amount,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Odeme tutari ile sepet tutari eslesmiyor. Lutfen sayfayi yenileyip tekrar deneyin.',
                    'error' => 'basket_total=' . $basketTotal . ', order_total=' . $amount,
                ], 422);
            }

            $addressText = (string)($shippingAddress->address ?? 'Adres belirtilmedi');
            $city = (string)(optional($shippingAddress->city)->name
                ?? optional($shippingAddress->countryState)->name
                ?? 'Istanbul');
            $country = (string)(optional($shippingAddress->country)->name ?? 'Turkey');
            $billingCity = (string)(optional($billingAddress->city)->name
                ?? optional($billingAddress->countryState)->name
                ?? 'Istanbul');
            $billingCountry = (string)(optional($billingAddress->country)->name ?? 'Turkey');
            $zipCode = (string) (
                ($invoiceData['postal_code'] ?? null)
                ?: data_get($user, 'zip_code')
                ?: '34000'
            );

            $session = $this->iyzicoService->createCheckoutForm([
                'locale' => 'tr',
                'conversation_id' => $conversationId,
                'price' => $amount,
                'paid_price' => $amount,
                'currency' => 'TRY',
                'basket_id' => (string)$order->id,
                'callback_url' => $callbackUrl,
                'enabled_installments' => $enabledInstallments,
                'buyer' => [
                    'id' => (string)$user->id,
                    'name' => (string)($this->extractFirstName($user->name) ?: 'Musteri'),
                    'surname' => (string)($this->extractLastName($user->name) ?: 'User'),
                    'gsm_number' => (string)($user->phone ?? $shippingAddress->phone ?? '+900000000000'),
                    'email' => (string)($user->email ?? 'musteri@seyfibaba.com'),
                    'identity_number' => (string) (
                        ($invoiceData['tc_identity'] ?? null)
                        ?: ($invoiceData['tax_number'] ?? null)
                        ?: data_get($user, 'identity_number')
                        ?: data_get($user, 'tc_identity')
                        ?: '00000000000'
                    ),
                    'last_login_date' => now()->format('Y-m-d H:i:s'),
                    'registration_date' => ($user->created_at ?? now())->format('Y-m-d H:i:s'),
                    'registration_address' => $addressText,
                    'ip' => (string)$request->ip(),
                    'city' => $city,
                    'country' => $country,
                    'zip_code' => $zipCode,
                ],
                'shipping_address' => [
                    'contact_name' => (string)($shippingAddress->name ?? $user->name ?? 'Musteri'),
                    'city' => $city,
                    'country' => $country,
                    'address' => $addressText,
                    'zip_code' => $zipCode,
                ],
                'billing_address' => [
                    'contact_name' => (string)($billingAddress->name ?? $user->name ?? 'Musteri'),
                    'city' => $billingCity,
                    'country' => $billingCountry,
                    'address' => (string)($billingAddress->address ?? 'Adres belirtilmedi'),
                    'zip_code' => $zipCode,
                ],
                'basket_items' => $basketItems,
            ]);

            if ($session->getStatus() !== 'success' || !$session->getPaymentPageUrl()) {
                Log::error('Iyzico checkout init failed', [
                    'order_id' => $order->id,
                    'status' => $session->getStatus(),
                    'error_code' => $session->getErrorCode(),
                    'error_message' => $session->getErrorMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Iyzico ödeme oturumu oluşturulamadı.',
                    'error' => $session->getErrorMessage(),
                ], 422);
            }

            // Transaction ref'i kaydet
            $order->transection_id = $session->getToken();
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Odeme oturumu olusturuldu.',
                'data' => [
                    'checkout_url' => $session->getPaymentPageUrl(),
                    'token' => $session->getToken(),
                    'order_id' => $order->order_id,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Iyzico checkout session exception', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Odeme oturumu olusturulurken hata olustu.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createGuestCheckoutSession(Request $request)
    {
        $request->validate([
            'address' => 'required|array',
            'address.name' => 'required|string',
            'address.email' => 'required|email',
            'address.phone' => 'required|string',
            'address.address' => 'required|string',
            'address.city' => 'required',
            'address.country' => 'required',
            'cart_products' => 'required|array|min:1',
            'shipping_method_id' => 'required|integer',
        ]);

        $iyzicoConfig = IyzicoPayment::first();
        if (!$iyzicoConfig || !$iyzicoConfig->status) {
            return response()->json(['message' => 'Iyzico ödeme yöntemi aktif değil.'], 422);
        }

        try {
            $guestController = app(CheckoutWithoutTokenController::class);
            $cartProducts = collect($request->input('cart_products', []));

            if ($cartProducts->count() == 0) {
                return response()->json(['message' => trans('user_validation.Your shopping cart is empty')], 403);
            }

            $totalInfo = $guestController->calculateCartTotal(
                null,
                $cartProducts,
                $request->coupon,
                $request->shipping_method_id,
                (object) $request->address
            );

            if ($totalInfo instanceof \Illuminate\Http\JsonResponse) {
                return $totalInfo;
            }

            $totalPrice = $totalInfo['total_price'];
            $shippingFee = $totalInfo['shipping_fee'];
            $couponPrice = $totalInfo['coupon_price'];
            $totalProduct = OrderQuantityHelper::fromCartProducts($cartProducts);

            $addressInfo = array_merge(
                is_array($request->address) ? $request->address : [],
                $request->only([
                    'invoice_type',
                    'tc_identity',
                    'tax_number',
                    'tax_office',
                    'company_name',
                    'is_e_invoice',
                    'postal_code',
                    'zip_code',
                ])
            );
            $invoiceData = app(BuyerInvoiceService::class)->validateFromAddressInfo($addressInfo, true);

            $orderResult = $guestController->orderStore(
                $totalPrice,
                $cartProducts,
                $totalProduct,
                'Iyzico',
                null,
                0,
                $totalInfo['shipping'],
                $shippingFee,
                $couponPrice,
                0,
                $addressInfo,
                'yes',
                $invoiceData
            );

            if ($orderResult instanceof \Illuminate\Http\JsonResponse) {
                return $orderResult;
            }

            $order = $orderResult['order'];
            $addr = $request->address;
            $conversationId = 'order_' . $order->id;
            $amount = number_format((float)$totalPrice, 2, '.', '');

            $callbackUrl = $this->resolveIyzicoCallbackUrl((int) $order->id);
            $basketItems = $this->buildGuestBasketItems($order, $cartProducts, $iyzicoConfig);
            $enabledInstallments = $this->installmentService->enabledInstallmentsForCart($cartProducts);

            // Debug: kategori bazlı taksit kısıtı kanıtı (kart verisi içermez)
            try {
                $debugItems = [];
                foreach ($cartProducts as $cp) {
                    $pid = $cp['product_id'] ?? null;
                    if (! $pid) continue;
                    $p = Product::query()->select('id', 'name', 'category_id', 'sub_category_id')->find($pid);
                    if (! $p) continue;
                    $debugItems[] = [
                        'product_id' => (int) $p->id,
                        'category_id' => (int) ($p->category_id ?? 0),
                        'sub_category_id' => (int) ($p->sub_category_id ?? 0),
                        'qty' => (int) ($cp['qty'] ?? 1),
                    ];
                }
                Log::info('Iyzico enabled installments computed (guest)', [
                    'order_id' => (int) $order->id,
                    'order_number' => (string) $order->order_id,
                    'enabled_installments' => $enabledInstallments,
                    'items' => $debugItems,
                    'iyzico_categories' => collect($basketItems)
                        ->filter(fn ($i) => str_starts_with((string) ($i['id'] ?? ''), 'PROD-'))
                        ->map(fn ($i) => [
                            'id' => $i['id'] ?? null,
                            'category_1' => $i['category_1'] ?? null,
                            'category_2' => $i['category_2'] ?? null,
                        ])
                        ->values()
                        ->all(),
                ]);
            } catch (\Throwable $e) {
                // ignore
            }

            $basketItems = $this->normalizeBasketItemsForIyzico($basketItems, $iyzicoConfig, (int) $order->id);

            // Kargo ücretini basket items'a ekle
            if ((float)$shippingFee > 0) {
                $basketItems[] = [
                    'id' => 'SHIPPING-' . $order->id,
                    'name' => 'Kargo Ücreti',
                    'category_1' => 'Kargo',
                    'category_2' => 'Kargo',
                    'item_type' => 'VIRTUAL',
                    'price' => number_format((float)$shippingFee, 2, '.', ''),
                ];
            }

            $basketItems = $this->alignBasketToPaidAmount($basketItems, $amount);
            $basketTotal = collect($basketItems)->sum(fn ($item) => (float) ($item['price'] ?? 0));
            if (abs($basketTotal - (float) $amount) > 0.02) {
                Log::warning('Iyzico guest basket total mismatch', [
                    'order_id' => $order->id,
                    'basket_total' => $basketTotal,
                    'order_total' => $amount,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Odeme tutari ile sepet tutari eslesmiyor. Lutfen sayfayi yenileyip tekrar deneyin.',
                    'error' => 'basket_total=' . $basketTotal . ', order_total=' . $amount,
                ], 422);
            }

            $addressText = (string)($addr['address'] ?? 'Adres belirtilmedi');
            $city = (string)($addr['city'] ?? 'Istanbul');
            $country = (string)($addr['country'] ?? 'Turkey');
            $zipCode = (string) (
                ($invoiceData['postal_code'] ?? null)
                ?: ($addr['postal_code'] ?? null)
                ?: ($addr['zip_code'] ?? null)
                ?: '34000'
            );

            $session = $this->iyzicoService->createCheckoutForm([
                'locale' => 'tr',
                'conversation_id' => $conversationId,
                'price' => $amount,
                'paid_price' => $amount,
                'currency' => 'TRY',
                'basket_id' => (string)$order->id,
                'callback_url' => $callbackUrl,
                'enabled_installments' => $enabledInstallments,
                'buyer' => [
                    'id' => 'GUEST-' . $order->id,
                    'name' => (string)($this->extractFirstName($addr['name'] ?? '') ?: 'Misafir'),
                    'surname' => (string)($this->extractLastName($addr['name'] ?? '') ?: 'Kullanici'),
                    'gsm_number' => (string)($addr['phone'] ?? '+900000000000'),
                    'email' => (string)($addr['email'] ?? 'misafir@seyfibaba.com'),
                    'identity_number' => (string)($addr['identity_number'] ?? '00000000000'),
                    'last_login_date' => now()->format('Y-m-d H:i:s'),
                    'registration_date' => now()->format('Y-m-d H:i:s'),
                    'registration_address' => $addressText,
                    'ip' => (string)$request->ip(),
                    'city' => $city,
                    'country' => $country,
                    'zip_code' => $zipCode,
                ],
                'shipping_address' => [
                    'contact_name' => (string)($addr['name'] ?? 'Misafir'),
                    'city' => $city,
                    'country' => $country,
                    'address' => $addressText,
                    'zip_code' => $zipCode,
                ],
                'billing_address' => [
                    'contact_name' => (string)($addr['name'] ?? 'Misafir'),
                    'city' => $city,
                    'country' => $country,
                    'address' => $addressText,
                    'zip_code' => $zipCode,
                ],
                'basket_items' => $basketItems,
            ]);

            if ($session->getStatus() !== 'success' || !$session->getPaymentPageUrl()) {
                Log::error('Iyzico guest checkout init failed', [
                    'order_id' => $order->id,
                    'status' => $session->getStatus(),
                    'error_code' => $session->getErrorCode(),
                    'error_message' => $session->getErrorMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Iyzico ödeme oturumu oluşturulamadı.',
                    'error' => $session->getErrorMessage(),
                ], 422);
            }

            $order->transection_id = $session->getToken();
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Odeme oturumu olusturuldu.',
                'data' => [
                    'checkout_url' => $session->getPaymentPageUrl(),
                    'token' => $session->getToken(),
                    'order_id' => $order->order_id,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Iyzico guest checkout session exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Odeme oturumu olusturulurken hata olustu.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        $orderId = (int)$request->get('order_id');
        $token = (string)$request->get('token', '');
        $conversationId = 'order_' . $orderId;

        $setting = Setting::first();
        $frontendUrl = rtrim($setting->frontend_url ?? config('app.url'), '/');
        $cancelUrl = $this->buildFailedPaymentUrl($frontendUrl, $orderId);

        if ($token === '' || $orderId <= 0) {
            Log::warning('Iyzico callback missing token/order_id', [
                'order_id' => $orderId,
                'has_token' => $token !== '',
            ]);
            return redirect()->to(
                $this->buildFailedPaymentUrl($frontendUrl, $orderId, [
                    'reason' => 'missing_callback_params',
                ])
            );
        }

        $order = Order::find($orderId);
        if (!$order) {
            return redirect()->to(
                $this->buildFailedPaymentUrl($frontendUrl, $orderId, [
                    'reason' => 'order_not_found',
                ])
            );
        }

        $successUrl = $frontendUrl . '/order/' . $order->order_id . '?payment_status=success';

        // Replay protection: if already paid, redirect to success without reprocessing
        if ($order->payment_status == 1) {
            Log::info('Iyzico callback replay blocked — order already paid', ['order_id' => $orderId]);
            return redirect()->to($successUrl);
        }

        try {
            $result = $this->iyzicoService->retrieveCheckoutForm($token, $conversationId);

            Log::info('Iyzico callback result', [
                'order_id' => $order->id,
                'status' => $result->getStatus(),
                'payment_status' => $result->getPaymentStatus(),
                'payment_id' => $result->getPaymentId(),
            ]);

            if ($result->getStatus() === 'success' && strtoupper((string)$result->getPaymentStatus()) === 'SUCCESS') {
                $order->payment_status = 1;
                $order->payment_method = 'Iyzico';
                $order->transection_id = (string)($result->getPaymentId() ?: $token);
                $order->is_draft = 'no';
                $order->payment_approval_date = now()->toDateTimeString();

                // Store payment transaction IDs per item (needed for refunds)
                $paymentItems = $result->getPaymentItems();
                if ($paymentItems) {
                    $itemData = [];
                    foreach ($paymentItems as $item) {
                        $itemData[] = [
                            'item_id' => $item->getItemId(),
                            'payment_transaction_id' => $item->getPaymentTransactionId(),
                            'price' => $item->getPrice(),
                            'paid_price' => $item->getPaidPrice(),
                        ];
                    }
                    $order->iyzico_payment_data = json_encode([
                        'payment_id' => $result->getPaymentId(),
                        'items' => $itemData,
                    ]);
                }

                $order->save();

                try {
                    app(\App\Services\SellerPayoutService::class)->syncPaymentTransactionIds($order);
                } catch (\Throwable $e) {
                    Log::warning('Iyzico payment transaction sync failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Reduce stock after successful payment confirmed
                $orderProducts = $order->orderProducts;
                foreach ($orderProducts as $orderProduct) {
                    $product = Product::find($orderProduct->product_id);
                    if ($product) {
                        $product->qty -= $orderProduct->qty;
                        $product->save();
                    }
                }

                try {
                    $user = $order->user;
                    if ($user) {
                        $paymentController = app(PaymentController::class);
                        $paymentController->sendOrderSuccessMail(
                            $user,
                            $order->total_amount,
                            'Iyzico',
                            1,
                            $order,
                            ''
                        );
                        $paymentController->sendOrderSuccessSms($user, $order);
                        $paymentController->sendOrderNotificationToSellers($order);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Iyzico success mail failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
                }

                Log::info('Iyzico payment verified', [
                    'order_id' => $order->id,
                    'payment_id' => $result->getPaymentId(),
                ]);

                $this->clearUserCartForOrder($order);

                return redirect()->to($successUrl);
            }

            // Ödeme başarısız — siparişi iptal et ve stoku geri al
            $order->payment_status = 0;
            $order->order_status = 4; // 4 = ödeme başarısız / iptal
            $order->is_draft = 'no';
            $order->save();

            $this->restoreStockForOrder($order);

            $errorCode = $result->getErrorCode();
            $errorCategory = match(true) {
                str_contains($errorCode ?? '', '3D') => 'THREE_D_SECURE_FAILED',
                str_contains($errorCode ?? '', 'INSUFFICIENT') || str_contains($errorCode ?? '', 'LIMIT') => 'INSUFFICIENT_FUNDS',
                str_contains($errorCode ?? '', 'EXPIRED') => 'CARD_EXPIRED',
                str_contains($errorCode ?? '', 'INVALID') => 'INVALID_CARD',
                str_contains($errorCode ?? '', 'FRAUD') => 'FRAUD_DETECTED',
                default => 'PAYMENT_DECLINED',
            };

            Log::warning("Iyzico payment failed [{$errorCategory}]", [
                'order_id' => $order->id,
                'category' => $errorCategory,
                'status' => $result->getStatus(),
                'payment_status' => $result->getPaymentStatus(),
                'error_code' => $errorCode,
                'error_message' => $result->getErrorMessage(),
                'error_group' => $result->getErrorGroup(),
            ]);

            try {
                $user = $order->user;
                if ($user && $user->email) {
                    \App\Helpers\MailHelper::setMailConfig();
                    $content = "Ödemeniz başarısız oldu.\n\nSipariş No: {$order->order_id}\n\nFarklı bir kart deneyebilir veya tekrar ödeme yapabilirsiniz. Sorun devam ederse destek ekibimizle iletişime geçebilirsiniz.";
                    \Mail::to($user->email)->send(new \App\Mail\PaymentFailedMail($content));
                }
            } catch (\Throwable $e) {
                Log::warning('Payment failed mail error', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }

            return redirect()->to(
                $this->buildFailedPaymentUrl($frontendUrl, $orderId, [
                    'reason' => 'payment_declined',
                    'code' => $result->getErrorCode(),
                ])
            );
        } catch (\Throwable $e) {
            // Exception durumunda da stok geri al
            if (isset($order) && $order) {
                $order->payment_status = 0;
                $order->order_status = 4;
                $order->is_draft = 'no';
                $order->save();
                $this->restoreStockForOrder($order);
            }

            Log::error('Iyzico callback exception — stock restored', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            return redirect()->to(
                $this->buildFailedPaymentUrl($frontendUrl, $orderId, [
                    'reason' => 'payment_processing_error',
                ])
            );
        }
    }

    private function buildFailedPaymentUrl(string $frontendUrl, int $orderId, array $params = []): string
    {
        $query = array_filter([
            'order_id' => $orderId > 0 ? $orderId : null,
            'reason' => $params['reason'] ?? null,
            'code' => $params['code'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');

        $queryString = http_build_query($query);

        return $frontendUrl . '/payment-failed' . ($queryString ? '?' . $queryString : '');
    }

    private function buildBasketItems(Order $order, $cartProducts, IyzicoPayment $config): array
    {
        $basketItems = [];
        $marketplaceMode = (bool)$config->marketplace_mode;
        $storeSubMerchantKeys = json_decode($config->store_sub_merchant_keys ?? '{}', true) ?: [];

        foreach ($cartProducts as $cartProduct) {
            $product = $cartProduct->product;
            if (!$product) continue;

            $variantPrice = 0;
            foreach (($cartProduct->variants ?? []) as $variant) {
                $variantItemId = $variant['variant_item_id'] ?? $variant->variant_item_id ?? null;
                if ($variantItemId) {
                    $variantPrice += (float) ProductVariantItem::where('id', $variantItemId)->value('price');
                }
            }

            $price = (float)($product->offer_price ?: $product->price) + $variantPrice;
            $linePrice = number_format($price * $cartProduct->qty, 2, '.', '');
            $iyzicoCategory = $this->installmentService->resolveIyzicoCategory($product);

            $item = [
                'id' => 'PROD-' . $product->id,
                'name' => (string)($product->name ?? 'Urun #' . $product->id),
                'category_1' => $iyzicoCategory['category_1'],
                'category_2' => $iyzicoCategory['category_2'],
                'item_type' => 'PHYSICAL',
                'price' => $linePrice,
            ];

            // Iyzico marketplace hesabı her ürün için subMerchantKey zorunlu tutar
            $subMerchantKey = null;
            if ($marketplaceMode && $product->vendor_id) {
                $subMerchantKey = $this->resolveSubMerchantKey($product->vendor_id, $storeSubMerchantKeys, $config);
            }
            if (!$subMerchantKey && $config->sub_merchant_key) {
                $subMerchantKey = $config->sub_merchant_key;
            }
            if ($subMerchantKey) {
                $item['sub_merchant_key'] = $subMerchantKey;
                $item['sub_merchant_price'] = $linePrice;
            }

            $basketItems[] = $item;
        }

        return $basketItems;
    }

    private function buildGuestBasketItems(Order $order, $cartProducts, IyzicoPayment $config): array
    {
        $basketItems = [];
        $marketplaceMode = (bool)$config->marketplace_mode;
        $storeSubMerchantKeys = json_decode($config->store_sub_merchant_keys ?? '{}', true) ?: [];

        foreach ($cartProducts as $cartProduct) {
            $product = \App\Models\Product::find($cartProduct['product_id']);
            if (!$product) continue;

            $qty = (int)($cartProduct['qty'] ?? 1);
            $unitPrice = $this->computeCartLineUnitPrice(
                (int) $cartProduct['product_id'],
                is_array($cartProduct['variants'] ?? null) ? $cartProduct['variants'] : []
            );
            if ($unitPrice === null) {
                continue;
            }

            $linePrice = number_format($unitPrice * $qty, 2, '.', '');
            $iyzicoCategory = $this->installmentService->resolveIyzicoCategory($product);

            $item = [
                'id' => 'PROD-' . $product->id,
                'name' => (string)($product->name ?? 'Urun #' . $product->id),
                'category_1' => $iyzicoCategory['category_1'],
                'category_2' => $iyzicoCategory['category_2'],
                'item_type' => 'PHYSICAL',
                'price' => $linePrice,
            ];

            // Iyzico marketplace hesabı her ürün için subMerchantKey zorunlu tutar
            $subMerchantKey = null;
            if ($marketplaceMode && $product->vendor_id) {
                $subMerchantKey = $this->resolveSubMerchantKey($product->vendor_id, $storeSubMerchantKeys, $config);
            }
            if (!$subMerchantKey && $config->sub_merchant_key) {
                $subMerchantKey = $config->sub_merchant_key;
            }
            if ($subMerchantKey) {
                $item['sub_merchant_key'] = $subMerchantKey;
                $item['sub_merchant_price'] = $linePrice;
            }

            $basketItems[] = $item;
        }

        return $basketItems;
    }

    /**
     * Iyzico sepet satır toplamı ödenen tutara eşit olmalı.
     * Kupon indirimi ürün satırlarına orantılı dağıtılır; kargo satırı korunur.
     */
    private function alignBasketToPaidAmount(array $basketItems, string $paidAmount): array
    {
        if ($basketItems === []) {
            return $basketItems;
        }

        $target = round((float) $paidAmount, 2);
        $current = round(array_sum(array_map(
            static fn ($item) => (float) ($item['price'] ?? 0),
            $basketItems
        )), 2);

        if (abs($current - $target) <= 0.02) {
            return $basketItems;
        }

        $diff = round($current - $target, 2);
        $productIndexes = [];
        $productTotal = 0.0;
        foreach ($basketItems as $index => $item) {
            $id = (string) ($item['id'] ?? '');
            if (str_starts_with($id, 'SHIPPING-')) {
                continue;
            }
            $productIndexes[] = $index;
            $productTotal += (float) ($item['price'] ?? 0);
        }

        if ($productIndexes === [] || $productTotal <= 0) {
            return $basketItems;
        }

        $remaining = $diff;
        $lastKey = count($productIndexes) - 1;
        foreach ($productIndexes as $key => $index) {
            $price = (float) $basketItems[$index]['price'];
            if ($key === $lastKey) {
                $share = $remaining;
            } else {
                $share = round(($price / $productTotal) * $diff, 2);
                $remaining = round($remaining - $share, 2);
            }

            $newPrice = round($price - $share, 2);
            if ($newPrice < 0.01) {
                $newPrice = 0.01;
            }

            $formatted = number_format($newPrice, 2, '.', '');
            $basketItems[$index]['price'] = $formatted;

            if (isset($basketItems[$index]['sub_merchant_price'])) {
                $oldSub = (float) $basketItems[$index]['sub_merchant_price'];
                $ratio = $price > 0 ? min(1, $oldSub / $price) : 1;
                $newSub = round($newPrice * $ratio, 2);
                if ($newSub > $newPrice) {
                    $newSub = $newPrice;
                }
                $basketItems[$index]['sub_merchant_price'] = number_format(max(0.01, $newSub), 2, '.', '');
            }
        }

        $current = round(array_sum(array_map(
            static fn ($item) => (float) ($item['price'] ?? 0),
            $basketItems
        )), 2);
        $penny = round($target - $current, 2);
        if (abs($penny) >= 0.01) {
            $last = $productIndexes[count($productIndexes) - 1];
            $adjusted = round((float) $basketItems[$last]['price'] + $penny, 2);
            if ($adjusted < 0.01) {
                $adjusted = 0.01;
            }
            $basketItems[$last]['price'] = number_format($adjusted, 2, '.', '');
            if (isset($basketItems[$last]['sub_merchant_price'])) {
                $sub = min((float) $basketItems[$last]['sub_merchant_price'], $adjusted);
                $basketItems[$last]['sub_merchant_price'] = number_format(max(0.01, $sub), 2, '.', '');
            }
        }

        return $basketItems;
    }

    /**
     * Sandbox (standard üye işyeri) ve eksik sub-merchant senaryolarında
     * subMerchantKey/subMerchantPrice gönderilmez.
     */
    private function normalizeBasketItemsForIyzico(array $basketItems, IyzicoPayment $config, int $orderId): array
    {
        if ($config->is_test_mode) {
            return $this->stripSubMerchantFromBasketItems($basketItems);
        }

        if ($config->marketplace_mode) {
            $allHaveSubMerchant = collect($basketItems)->every(
                fn ($item) => ! empty($item['sub_merchant_key'])
            );
            if (! $allHaveSubMerchant) {
                Log::warning('Iyzico: marketplace mode aktif ama sub-merchant key eksik, standard moda geçiliyor', [
                    'order_id' => $orderId,
                ]);

                return $this->stripSubMerchantFromBasketItems($basketItems);
            }
        }

        return $basketItems;
    }

    private function stripSubMerchantFromBasketItems(array $basketItems): array
    {
        return array_map(function ($item) {
            unset($item['sub_merchant_key'], $item['sub_merchant_price']);

            return $item;
        }, $basketItems);
    }

    private function resolveIyzicoCallbackUrl(int $orderId): string
    {
        $params = ['order_id' => $orderId];

        if (Route::has('api.iyzico.callback')) {
            return route('api.iyzico.callback', $params);
        }

        if (Route::has('iyzico.callback')) {
            return route('iyzico.callback', $params);
        }

        return url('/api/user/iyzico/callback?' . http_build_query($params));
    }

    private function computeCartLineUnitPrice(int $productId, array $variants = []): ?float
    {
        $product = Product::select('id', 'price', 'offer_price')->find($productId);
        if (!$product) {
            return null;
        }

        $variantPrice = 0.0;
        foreach ($variants as $variant) {
            $variantItemId = $variant['variant_item_id'] ?? null;
            if ($variantItemId) {
                $variantPrice += (float) ProductVariantItem::where('id', $variantItemId)->value('price');
            }
        }

        $price = (float) ($product->offer_price ?: $product->price) + $variantPrice;

        $isFlashSale = FlashSaleProduct::where(['product_id' => $product->id, 'status' => 1])->first();
        if ($isFlashSale) {
            $flashSale = FlashSale::first();
            if ($flashSale && (int) $flashSale->status === 1 && date('Y-m-d H:i:s') <= $flashSale->end_time) {
                $price -= ((float) $flashSale->offer / 100) * $price;
            }
        }

        return $price;
    }

    /**
     * Resolve the sub-merchant key for a vendor.
     * Priority: vendor DB field → admin JSON map → global fallback
     */
    private function resolveSubMerchantKey(int $vendorId, array $storeSubMerchantKeys, IyzicoPayment $config): ?string
    {
        $vendor = Vendor::find($vendorId);
        if ($vendor && $vendor->iyzico_sub_merchant_key) {
            return $vendor->iyzico_sub_merchant_key;
        }

        $vendorIdStr = (string)$vendorId;
        return $storeSubMerchantKeys[$vendorIdStr]
            ?? $storeSubMerchantKeys[$vendorId]
            ?? $config->sub_merchant_key
            ?? null;
    }

    /**
     * Calculate sub-merchant price after commission deduction.
     * subMerchantPrice = linePrice - (linePrice * commissionRate / 100)
     */
    private function calculateSubMerchantPrice(float $linePrice, int $vendorId): string
    {
        $vendor = Vendor::find($vendorId);
        $commissionRate = $vendor ? $vendor->getEffectiveCommissionRate() : 0;

        $commission = $linePrice * ($commissionRate / 100);
        $subMerchantPrice = $linePrice - $commission;

        return number_format(max(0, $subMerchantPrice), 2, '.', '');
    }

    private function extractFirstName(?string $fullName): string
    {
        $parts = preg_split('/\s+/', trim((string) $fullName));
        return $parts[0] ?? 'Musteri';
    }

    private function extractLastName(?string $fullName): string
    {
        $parts = preg_split('/\s+/', trim((string) $fullName));
        if (count($parts) <= 1) {
            return 'User';
        }

        array_shift($parts);
        return implode(' ', $parts);
    }

    /**
     * Başarılı ödemeden sonra kullanıcının sepetinden siparişteki ürünleri kaldır.
     */
    private function clearUserCartForOrder(Order $order): void
    {
        app(CartCleanupService::class)->clearCartForOrder($order);
    }

    /**
     * Ödeme başarısız olduğunda sipariş ürünlerinin stoğunu geri yükle
     */
    private function restoreStockForOrder(Order $order): void
    {
        try {
            $orderProducts = $order->orderProducts;
            foreach ($orderProducts as $orderProduct) {
                $product = Product::find($orderProduct->product_id);
                if ($product) {
                    $product->qty += $orderProduct->qty;
                    $product->save();
                }
            }
            Log::info('Stock restored for failed order', [
                'order_id' => $order->id,
                'products_count' => $orderProducts->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to restore stock', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
