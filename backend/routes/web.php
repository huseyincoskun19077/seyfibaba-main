<?php


use App\Models\Setting;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\User\AddressCotroller;
use App\Http\Controllers\User\MessageController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\User\CheckoutController;
use App\Http\Controllers\WEB\Admin\FaqController;
use App\Http\Controllers\WEB\Admin\PosController;
use App\Http\Controllers\WEB\Admin\CityController;
use App\Http\Controllers\WEB\Admin\AdminController;
use App\Http\Controllers\WEB\Admin\OrderController;
use App\Http\Controllers\User\UserProfileController;
use App\Http\Controllers\WEB\Admin\CouponController;
use App\Http\Controllers\WEB\Admin\FooterController;
use App\Http\Controllers\WEB\Admin\SellerController;
use App\Http\Controllers\WEB\Admin\CallCenterRegistrationController;
use App\Http\Controllers\WEB\Admin\SliderController;
use App\Http\Controllers\WEB\Admin\MobileSliderController;
use App\Http\Controllers\WEB\Admin\AboutUsController;
use App\Http\Controllers\WEB\Admin\ContentController;
use App\Http\Controllers\WEB\Admin\CountryController;
use App\Http\Controllers\WEB\Admin\ProductController;
use App\Http\Controllers\WEB\Admin\ServiceController;
use App\Http\Controllers\WEB\Admin\SettingController;
use App\Http\Controllers\WEB\Admin\CurrencyController;
use App\Http\Controllers\WEB\Admin\CustomerController;
use App\Http\Controllers\WEB\Admin\HomePageController;
use App\Http\Controllers\WEB\Admin\LanguageController;
use App\Http\Controllers\WEB\Admin\MegaMenuController;
use App\Http\Controllers\WEB\LanguageSwitchController;
use App\Http\Controllers\WEB\Admin\DashboardController;
use App\Http\Controllers\WEB\Admin\ErrorPageController;
use App\Http\Controllers\WEB\Admin\FlashSaleController;
use App\Http\Controllers\WEB\Admin\InventoryController;
use App\Http\Controllers\WEB\Seller\WithdrawController;
use App\Http\Controllers\WEB\Seller\ReturnRequestController as SellerReturnRequestController;
use App\Http\Controllers\WEB\Admin\BreadcrumbController;
use App\Http\Controllers\WEB\Admin\CustomPageController;
use App\Http\Controllers\WEB\Admin\FooterLinkController;
use App\Http\Controllers\WEB\Admin\SubscriberController;
use App\Http\Controllers\WEB\Admin\ContactPageController;
use App\Http\Controllers\WEB\Admin\DeliveryManController;
use App\Http\Controllers\WEB\Admin\TestimonialController;
use App\Http\Controllers\WEB\Admin\AdminProfileController;
use App\Http\Controllers\WEB\Admin\CountryStateController;
use App\Http\Controllers\WEB\Admin\NotificationController;
use App\Http\Controllers\WEB\Admin\ProductBrandController;
use App\Http\Controllers\WEB\Seller\SellerOrderController;
use App\Http\Controllers\WEB\Seller\SellerOrderCargoController;
use App\Http\Controllers\WEB\Seller\SellerBrandController;
use App\Http\Controllers\WEB\Admin\AdminLanguageController;
use App\Http\Controllers\WEB\Admin\AdvertisementController;
use App\Http\Controllers\WEB\Admin\EmailTemplateController;
use App\Http\Controllers\WEB\Admin\PaymentMethodController;
use App\Http\Controllers\WEB\Admin\PrivacyPolicyController;
use App\Http\Controllers\WEB\Admin\LegalDocumentController as AdminLegalDocumentController;
use App\Http\Controllers\WEB\Admin\AiSettingsController;
use App\Http\Controllers\WEB\Admin\GeliverSettingsController;
use App\Http\Controllers\WEB\Admin\OrderCargoController;
use App\Http\Controllers\WEB\Admin\CommissionController;
use App\Http\Controllers\WEB\Admin\SellerKycController as AdminSellerKycController;
use App\Http\Controllers\WEB\Admin\SellerProductOverviewController;
use App\Http\Controllers\WEB\Admin\StockAlertController as WebAdminStockAlertController;
use App\Http\Controllers\WEB\Admin\PushNotificationController as WebAdminPushNotificationController;
use App\Http\Controllers\WEB\Admin\InstallmentCategoryController;
use App\Http\Controllers\WEB\Admin\SecondHandController;
use App\Http\Controllers\WEB\Admin\SalonCrmAdminController;
use App\Http\Controllers\WEB\Admin\SmsCampaignController;

use App\Http\Controllers\WEB\Admin\ProductReportController;
use App\Http\Controllers\WEB\Admin\ProductReviewController;
use App\Http\Controllers\WEB\Seller\SellerMessageContoller;
use App\Http\Controllers\WEB\Admin\ContactMessageController;
use App\Http\Controllers\WEB\Admin\MenuVisibilityController;
use App\Http\Controllers\WEB\Admin\ProductGalleryController;
use App\Http\Controllers\WEB\Admin\ProductVariantController;
use App\Http\Controllers\WEB\Admin\SellerWithdrawController;
use App\Http\Controllers\WEB\Admin\ShippingMethodController;
use App\Http\Controllers\WEB\Admin\WithdrawMethodController;
use App\Http\Controllers\WEB\Deliveryman\MyReviewController;
use App\Http\Controllers\WEB\Seller\QuickProductController;
use App\Http\Controllers\WEB\Seller\SellerProductController;

use App\Http\Controllers\WEB\Seller\SellerProfileController;
use App\Http\Controllers\User\CheckoutWithoutTokenController;
use App\Http\Controllers\WEB\Admin\Auth\AdminLoginController;
use App\Http\Controllers\WEB\Admin\ProductCategoryController;
use App\Http\Controllers\WEB\Admin\FooterSocialLinkController;
use App\Http\Controllers\WEB\Admin\SpecificationKeyController;
use App\Http\Controllers\WEB\Deliveryman\MyWithdrawController;
use App\Http\Controllers\WEB\Seller\SellerAiAssistantController;
use App\Http\Controllers\WEB\Seller\SellerDashboardController;



use App\Http\Controllers\WEB\Admin\TermsAndConditionController;
use App\Http\Controllers\WEB\Seller\Auth\SellerLoginController;
use App\Http\Controllers\WEB\Seller\Auth\SellerSsoController;
use App\Support\SellerLoginUrl;
use App\Http\Controllers\WEB\Admin\EmailConfigurationController;

use App\Http\Controllers\WEB\Admin\HomepageVisibilityController;
use App\Http\Controllers\WEB\Admin\ProductSubCategoryController;
use App\Http\Controllers\WEB\Admin\ProductVariantItemController;
use App\Http\Controllers\WEB\Admin\DeliveryManWithdrawController;
use App\Http\Controllers\WEB\Admin\MegaMenuSubCategoryController;
use App\Http\Controllers\WEB\Seller\SellerProductReportControler;
use App\Http\Controllers\WEB\Admin\ProductChildCategoryController;
use App\Http\Controllers\WEB\Seller\SellerProductReviewController;
use App\Http\Controllers\WEB\Deliveryman\DeliveryMessageController;
use App\Http\Controllers\WEB\Seller\SellerProductGalleryController;
use App\Http\Controllers\WEB\Seller\SellerProductVariantController;
use App\Http\Controllers\WEB\Admin\DeliveryManOrderAmountController;
use App\Http\Controllers\WEB\Deliveryman\DeliveryManOrderController;
use App\Http\Controllers\WEB\Admin\Auth\AdminForgotPasswordController;
use App\Http\Controllers\WEB\Deliveryman\DeliveryManProfileController;
use App\Http\Controllers\WEB\Admin\DeliveryManWithdrawMethodController;
use App\Http\Controllers\WEB\Seller\SellerProductVariantItemController;
use App\Http\Controllers\WEB\Deliveryman\DeliveryManDashboardController;
use App\Http\Controllers\WEB\Seller\Auth\SellerForgotPasswordController;
use App\Http\Controllers\WEB\CallCenter\Auth\CallCenterLoginController;
use App\Http\Controllers\WEB\CallCenter\CallCenterDashboardController;
use App\Http\Controllers\WEB\CallCenter\CallCenterCommissionController;
use App\Http\Controllers\WEB\CallCenter\QuickRegistrationController;
use App\Http\Controllers\WEB\Deliveryman\Auth\DeliveryManLoginController;
use App\Http\Controllers\WEB\Deliveryman\Auth\DeliveryManResetPasswordController;
use App\Http\Controllers\WEB\Seller\InventoryController as SellerInventoryController;


// Broadcast::routes(['middleware' => ['auth:web']]);

Broadcast::routes(['prefix' => 'seller', 'middleware' => 'auth:web']);

Broadcast::routes(['prefix' => 'api', 'middleware' => 'auth:api']);

Route::get('/language-switcher', [LanguageSwitchController::class, 'language_switcher'])->name('language-switcher');

/**
 * Satıcı vitrin sayfaları kapalı — ürün odaklı sistem (admin paneli /seller/* korunur).
 */
Route::get('/sellers/{shop_name}', fn () => abort(404))->name('seller-detail');

/**
 * Ürün vitrin (Blade route('product-detail')) — /api/product/... JSON yerine Next.js ürün sayfası
 */
Route::get('/product/{slug}', function (string $slug) {
    $frontend = rtrim(optional(Setting::first())->frontend_url, '/') ?: config('app.url');

    return redirect()->away($frontend.'/urun/'.$slug);
})->name('product-detail');

Route::group([
    'prefix' => 'auth'

], function ($router) {

    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
});

Route::group(['as' => 'user.', 'prefix' => 'user'], function () {
    Route::group(['as' => 'checkout.', 'prefix' => 'checkout'], function () {
        Route::get('order-success-url-for-mobile-app', function () {
            return response()->json(['message' => 'order success']);
        })->name('order-success-url-for-mobile-app');

        Route::get('order-fail-url-for-mobile-app', function () {
            return response()->json(['message' => 'order faild']);
        })->name('order-fail-url-for-mobile-app');
    });
});



Route::group(['as' => 'user.', 'prefix' => 'user'], function () {
    Route::group(['as' => 'checkout.guest.', 'prefix' => 'checkout/guest'], function () {
        Route::get('order-success-url-for-mobile-app', function () {
            return response()->json(['message' => 'order success']);
        })->name('order-success-url-for-mobile-app');

        Route::get('order-fail-url-for-mobile-app', function () {
            return response()->json(['message' => 'order faild']);
        })->name('order-fail-url-for-mobile-app');
    });
});



Route::group(['middleware' => ['XSS']], function () {

    Route::group(['middleware' => ['maintainance']], function () {
        Route::get('/', fn () => redirect()->away(SellerLoginUrl::public()))->name('home');

        Route::redirect('seller', 'seller/dashboard');
        Route::get('seller/login', fn () => redirect()->away(SellerLoginUrl::public()));
        Route::redirect('satici-giris', SellerLoginUrl::public());
        Route::post('seller/login', [SellerLoginController::class, 'storeLogin'])->name('seller.login');
        Route::get('seller/logout', [SellerLoginController::class, 'adminLogout'])->name('seller.logout');
        Route::get('seller/sso', SellerSsoController::class)->name('seller.sso');

        Route::prefix('call-center')->name('call-center.')->group(function () {
            Route::get('login', [CallCenterLoginController::class, 'showLoginForm'])->name('login');
            Route::post('login', [CallCenterLoginController::class, 'login'])->middleware('throttle:auth-login')->name('login.post');

            Route::middleware(['auth:admin', 'call-center'])->group(function () {
                Route::post('logout', [CallCenterLoginController::class, 'logout'])->name('logout');
                Route::get('/', [CallCenterDashboardController::class, 'index'])->name('dashboard');
                Route::get('dashboard', [CallCenterDashboardController::class, 'index']);

                Route::get('registrations', [QuickRegistrationController::class, 'index'])->name('registrations.index');
                Route::get('registrations/create', [QuickRegistrationController::class, 'create'])->name('registrations.create');
                Route::post('registrations', [QuickRegistrationController::class, 'store'])->name('registrations.store');
                Route::get('registrations/{id}', [QuickRegistrationController::class, 'show'])->name('registrations.show');
                Route::post('registrations/{id}/resend-sms', [QuickRegistrationController::class, 'resendSms'])
                    ->middleware('throttle:10,1')
                    ->name('registrations.resend-sms');
                Route::post('registrations/{id}/resend-email', [QuickRegistrationController::class, 'resendEmail'])
                    ->middleware('throttle:10,1')
                    ->name('registrations.resend-email');
                Route::put('registrations/{id}', [QuickRegistrationController::class, 'update'])
                    ->middleware('throttle:10,1')
                    ->name('registrations.update');
                Route::put('registrations/{id}/phone', [QuickRegistrationController::class, 'updatePhone'])
                    ->middleware('throttle:10,1')
                    ->name('registrations.update-phone');
                Route::post('registrations/{id}/reminder-sms', [QuickRegistrationController::class, 'sendReminder'])
                    ->middleware('throttle:10,1')
                    ->name('registrations.send-reminder');
                Route::get('cities/{stateId}', [QuickRegistrationController::class, 'cities'])->name('cities');

                Route::prefix('sms-campaigns')->name('sms-campaigns.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\WEB\CallCenter\CallCenterSmsCampaignController::class, 'index'])->name('index');
                    Route::get('/create', [\App\Http\Controllers\WEB\CallCenter\CallCenterSmsCampaignController::class, 'create'])->name('create');
                    Route::post('/', [\App\Http\Controllers\WEB\CallCenter\CallCenterSmsCampaignController::class, 'store'])->name('store');
                    Route::get('/{id}', [\App\Http\Controllers\WEB\CallCenter\CallCenterSmsCampaignController::class, 'show'])->name('show');
                    Route::post('/preview', [\App\Http\Controllers\WEB\CallCenter\CallCenterSmsCampaignController::class, 'preview'])->name('preview');
                    Route::post('/users', [\App\Http\Controllers\WEB\CallCenter\CallCenterSmsCampaignController::class, 'usersForSegment'])->name('users');
                });

                Route::get('commissions', [CallCenterCommissionController::class, 'index'])->name('commissions.index');
                Route::post('commissions/{id}/approve', [CallCenterCommissionController::class, 'approve'])
                    ->middleware('throttle:20,1')
                    ->name('commissions.approve');
            });
        });

        // auth:web grubu: closure rotaları (kyc, contact-admin) controller middleware'inden yararlanmaz
        Route::group(['as' => 'seller.', 'prefix' => 'seller', 'middleware' => ['auth:web', 'seller.password.changed', 'seller.terms.accepted']], function () {
            Route::post('ai-assistant/chat', [SellerAiAssistantController::class, 'chat'])->name('ai-assistant.chat');

            Route::get('dashboard', [SellerDashboardController::class, 'index'])->name('dashboard');
            Route::get('my-profile', [SellerProfileController::class, 'index'])->name('my-profile');
            Route::get('state-by-country/{id}', [SellerProfileController::class, 'stateByCountry'])->name('state-by-country');
            Route::get('city-by-state/{id}', [SellerProfileController::class, 'cityByState'])->name('city-by-state');
            Route::put('update-seller-profile', [SellerProfileController::class, 'updateSellerProfile'])->name('update-seller-profile');
            Route::get('change-password', [SellerProfileController::class, 'changePassword'])->name('change-password');
            Route::put('password-update', [SellerProfileController::class, 'updatePassword'])->name('password-update');
            Route::get('accept-terms', [SellerProfileController::class, 'acceptTerms'])->name('accept-terms');
            Route::put('accept-terms', [SellerProfileController::class, 'storeTermsAcceptance'])->name('accept-terms.store');
            Route::get('shop-profile', [SellerProfileController::class, 'myShop'])->name('shop-profile');
            Route::put('update-seller-shop', [SellerProfileController::class, 'updateSellerSop'])->name('update-seller-shop');
            Route::put('remove-seller-social-link/{id}', [SellerProfileController::class, 'removeSellerSocialLink'])->name('remove-seller-social-link');
            Route::get('email-history', [SellerProfileController::class, 'emailHistory'])->name('email-history');
            Route::post('update-lat-long', [SellerProfileController::class, 'updateLocation'])->name('update.lat-long');

            Route::get('brand', [SellerBrandController::class, 'index'])->name('brand');
            Route::post('brand', [SellerBrandController::class, 'store'])->name('brand.store');
            Route::put('brand/{id}', [SellerBrandController::class, 'update'])->name('brand.update');
            Route::put('brand-status/{id}', [SellerBrandController::class, 'changeStatus'])->name('brand.status');
            Route::delete('brand/{id}', [SellerBrandController::class, 'destroy'])->name('brand.destroy');

            Route::get('product/quick-create', [QuickProductController::class, 'create'])->name('product.quick-create');
            Route::post('product/quick-store', [QuickProductController::class, 'store'])->name('product.quick-store');
            Route::post('product/quick-ai-fill', [QuickProductController::class, 'aiFill'])->name('product.quick-ai-fill');

            Route::resource('product', SellerProductController::class);
            Route::post('product/{id}/duplicate', [SellerProductController::class, 'duplicate'])->name('product.duplicate');
            Route::post('ai-generate-content', [SellerProductController::class, 'aiGenerateContent'])->name('ai-generate-content');
            Route::get('stockout-product', [SellerProductController::class, 'stockoutProduct'])->name('stockout-product');
            Route::put('product-status/{id}', [SellerProductController::class, 'changeStatus'])->name('product-status');
            Route::put('removed-product-exist-specification/{id}', [SellerProductController::class, 'removedProductExistSpecification'])->name('removed-product-exist-specification');
            Route::get('pending-product', [SellerProductController::class, 'pendingProduct'])->name('pending-product');
            Route::get('product-highlight/{id}', [SellerProductController::class, 'productHighlight'])->name('product-highlight');
            Route::put('update-product-highlight/{id}', [SellerProductController::class, 'productHighlightUpdate'])->name('update-product-highlight');

            Route::get('product-import-page', [SellerProductController::class, 'product_import_page'])->name('product-import-page');
            Route::get('product-bulk-import-template', [SellerProductController::class, 'product_bulk_import_template'])->name('product-bulk-import-template');
            Route::get('product-bulk-import-sample', [SellerProductController::class, 'product_bulk_import_sample'])->name('product-bulk-import-sample');
            Route::get('product-export', [SellerProductController::class, 'product_export'])->name('product-export');
            Route::get('product-demo-export', [SellerProductController::class, 'demo_product_export'])->name('product-demo-export');
            Route::post('product-import', [SellerProductController::class, 'product_import'])->name('product-import');


            Route::get('subcategory-by-category/{id}', [SellerProductController::class, 'getSubcategoryByCategory'])->name('subcategory-by-category');
            Route::get('childcategory-by-subcategory/{id}', [SellerProductController::class, 'getChildcategoryBySubCategory'])->name('childcategory-by-subcategory');


            Route::get('product-variant/{id}', [SellerProductVariantController::class, 'index'])->name('product-variant');
            Route::get('create-product-variant/{id}', [SellerProductVariantController::class, 'create'])->name('create-product-variant');
            Route::post('store-product-variant', [SellerProductVariantController::class, 'store'])->name('store-product-variant');
            Route::get('get-product-variant/{id}', [SellerProductVariantController::class, 'show'])->name('get-product-variant');
            Route::get('edit-product-variant/{id}', [SellerProductVariantController::class, 'edit'])->name('edit-product-variant');
            Route::put('update-product-variant/{id}', [SellerProductVariantController::class, 'update'])->name('update-product-variant');
            Route::delete('delete-product-variant/{id}', [SellerProductVariantController::class, 'destroy'])->name('delete-product-variant');
            Route::put('product-variant-status/{id}', [SellerProductVariantController::class, 'changeStatus'])->name('product-variant.status');

            Route::get('product-variant-item', [SellerProductVariantItemController::class, 'index'])->name('product-variant-item');
            Route::get('create-product-variant-item/{id}', [SellerProductVariantItemController::class, 'create'])->name('create-product-variant-item');
            Route::post('store-product-variant-item', [SellerProductVariantItemController::class, 'store'])->name('store-product-variant-item');
            Route::get('edit-product-variant-item/{id}', [SellerProductVariantItemController::class, 'edit'])->name('edit-product-variant-item');

            Route::get('get-product-variant-item/{id}', [SellerProductVariantItemController::class, 'show'])->name('egetdit-product-variant-item');

            Route::put('update-product-variant-item/{id}', [SellerProductVariantItemController::class, 'update'])->name('update-product-variant-item');
            Route::delete('delete-product-variant-item/{id}', [SellerProductVariantItemController::class, 'destroy'])->name('delete-product-variant-item');
            Route::put('product-variant-item-status/{id}', [SellerProductVariantItemController::class, 'changeStatus'])->name('product-variant-item.status');

            Route::get('product-gallery/{id}', [SellerProductGalleryController::class, 'index'])->name('product-gallery');
            Route::post('store-product-gallery', [SellerProductGalleryController::class, 'store'])->name('store-product-gallery');
            Route::delete('delete-product-image/{id}', [SellerProductGalleryController::class, 'destroy'])->name('delete-product-image');
            Route::put('product-gallery-status/{id}', [SellerProductGalleryController::class, 'changeStatus'])->name('product-gallery.status');
            Route::post('product-thumbnail/{id}', [SellerProductController::class, 'updateThumbnail'])->name('product.update-thumbnail');


            Route::get('product-review', [SellerProductReviewController::class, 'index'])->name('product-review');
            Route::get('show-product-review/{id}', [SellerProductReviewController::class, 'show'])->name('show-product-review');


            // Ürün şikayetleri (product-report) satıcıdan kaldırıldı — sadece admin görebilir

            Route::resource('my-withdraw', WithdrawController::class);
            Route::get('get-withdraw-account-info/{id}', [WithdrawController::class, 'getWithDrawAccountInfo'])->name('get-withdraw-account-info');
            Route::get('return-requests', [SellerReturnRequestController::class, 'index'])->name('return-requests.index');
            Route::get('show-return-request/{id}', [SellerReturnRequestController::class, 'show'])->name('return-requests.show');
            Route::put('update-return-request/{id}', [SellerReturnRequestController::class, 'updateStatus'])->name('return-requests.update-status');

            Route::get('all-order', [SellerOrderController::class, 'index'])->name('all-order');
            Route::get('pending-order', [SellerOrderController::class, 'pendingOrder'])->name('pending-order');
            Route::get('pregress-order', [SellerOrderController::class, 'pregressOrder'])->name('pregress-order');
            Route::get('delivered-order', [SellerOrderController::class, 'deliveredOrder'])->name('delivered-order');
            Route::get('completed-order', [SellerOrderController::class, 'completedOrder'])->name('completed-order');
            Route::get('declined-order', [SellerOrderController::class, 'declinedOrder'])->name('declined-order');
            Route::get('cash-on-delivery', [SellerOrderController::class, 'cashOnDelivery'])->name('cash-on-delivery');
            Route::get('order-show/{id}', [SellerOrderController::class, 'show'])->name('order-show');

            // Seller - order approval (0 -> 1) ve kargo (Geliver) işlemleri
            Route::put('update-order-status/{id}', [SellerOrderController::class, 'updateOrderStatus'])->name('update-order-status');
            Route::get('orders/{orderId}/cargo', [SellerOrderCargoController::class, 'show'])->name('orders.cargo.show');
            Route::get('orders/{orderId}/cargo/offers', [SellerOrderCargoController::class, 'offers'])->middleware('geliver.enabled')->name('orders.cargo.offers');
            Route::post('orders/{orderId}/cargo', [SellerOrderCargoController::class, 'createShipment'])->middleware('geliver.enabled')->name('orders.cargo.create');
            Route::delete('orders/{orderId}/cargo', [SellerOrderCargoController::class, 'cancel'])->middleware('geliver.enabled')->name('orders.cargo.cancel');
            Route::post('orders/{orderId}/cargo/manual/ship', [SellerOrderCargoController::class, 'manualShip'])->name('orders.cargo.manual.ship');
            Route::post('orders/{orderId}/cargo/manual/delivered', [SellerOrderCargoController::class, 'manualDelivered'])->name('orders.cargo.manual.delivered');

            Route::get('message', [SellerMessageContoller::class, 'index'])->name('message');
            Route::get('message-customer-list', [SellerMessageContoller::class, 'existing_customer_list'])->name('message-customer-list');

            Route::post('send-message-to-customer', [SellerMessageContoller::class, 'send_message_to_customer'])->name('send-message-to-customer');

            Route::get('load-active-user-message/{id}', [SellerMessageContoller::class, 'laod_active_user_message'])->name('load-active-user-message');



            Route::get('load-chat-box/{id}', [SellerMessageContoller::class, 'loadChatBox'])->name('load-chat-box');
            Route::get('load-new-message/{id}', [SellerMessageContoller::class, 'loadNewMessage'])->name('load-new-message');
            Route::get('send-message', [SellerMessageContoller::class, 'sendMessage'])->name('send-message');

            // Admin'e mesaj
            Route::get('contact-admin', function () {
                $setting = \App\Models\Setting::first();
                $messages = \App\Models\ContactMessage::where('email', \Auth::guard('web')->user()->email)->orderBy('id', 'desc')->get();
                return view('seller.contact_admin', compact('setting', 'messages'));
            })->name('contact-admin');

            Route::get('faq', function () {
                return view('seller.faq');
            })->name('faq');

            Route::get('guide', function () {
                return view('seller.guide');
            })->name('guide');

            Route::post('send-admin-message', function (\Illuminate\Http\Request $request) {
                $request->validate(['subject' => 'required', 'message' => 'required']);
                $user = \Auth::guard('web')->user();
                $msg = new \App\Models\ContactMessage();
                $msg->name = $user->name;
                $msg->email = $user->email;
                $msg->phone = $user->seller->phone ?? $user->phone;
                $msg->subject = $request->subject;
                $msg->message = $request->message;
                $msg->save();
                return redirect()->route('seller.contact-admin')->with(['messege' => 'Mesajınız admin\'e iletildi.', 'alert-type' => 'success']);
            })->name('send-admin-message');

            // KYC Hesap Doğrulama
            Route::get('kyc', function () {
                $setting = \App\Models\Setting::first();
                $seller = \Auth::guard('web')->user()->seller;
                $documents = $seller->kycDocuments()->orderBy('document_type')->get();
                return view('seller.kyc', compact('setting', 'seller', 'documents'));
            })->name('kyc');

            Route::post('kyc/upload', function (\Illuminate\Http\Request $request) {
                $seller = \Auth::guard('web')->user()->seller;
                $user = \Auth::guard('web')->user();
                $onboarding = app(\App\Services\SellerIyzicoOnboardingService::class);

                if (! $onboarding->hasValidContactEmail($user->email)) {
                    return redirect()->back()->withErrors([
                        'email' => 'Iyzico alt üye işyeri için geçerli bir e-posta adresi zorunludur. Önce "Satıcı Bilgileri" bölümünden e-postanızı kaydedin.',
                    ])->withInput();
                }

                $request->validate([
                    'document_type' => ['required', 'string', \Illuminate\Validation\Rule::in(\App\Services\SellerIyzicoOnboardingService::UPLOADABLE_DOCUMENT_TYPES)],
                    'document' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
                ], [
                    'document_type.in' => 'Şu an yalnızca vergi levhası yükleyebilirsiniz.',
                ]);

                $file = $request->file('document');
                $documentType = $request->document_type;

                $storedPath = $file->storeAs(
                    'private/kyc/' . $seller->id,
                    $documentType . '-' . \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension(),
                    'local'
                );

                // Mevcut belge varsa güncelle
                $existing = $seller->kycDocuments()->where('document_type', $documentType)->first();
                if ($existing && $existing->file_path) {
                    \Illuminate\Support\Facades\Storage::disk('local')->delete($existing->file_path);
                }

                \App\Models\SellerKycDocument::updateOrCreate(
                    ['seller_id' => $seller->id, 'document_type' => $documentType],
                    [
                        'file_path' => $storedPath,
                        'original_name' => $file->getClientOriginalName(),
                        'file_size' => (int) $file->getSize(),
                        'status' => 'pending',
                        'admin_note' => null,
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                    ]
                );

                // TC/IBAN/Vergi bilgilerini güncelle (IBAN normalize: boşluk sil, büyük harf)
                if ($request->filled('tc_identity')) {
                    $tc = preg_replace('/\D/', '', $request->tc_identity);
                    if (strlen($tc) !== 11) {
                        return redirect()->back()->withErrors(['tc_identity' => 'TC Kimlik No 11 haneli olmalıdır.'])->withInput();
                    }
                    $seller->tc_identity = $tc;
                }
                if ($request->filled('iban')) {
                    $iban = strtoupper(preg_replace('/\s+/', '', $request->iban));
                    if (!preg_match('/^TR\d{24}$/', $iban)) {
                        return redirect()->back()->withErrors(['iban' => 'IBAN formatı hatalı. TR ile başlayan 26 karakterli numara olmalıdır. Örn: TR960015700000000083650899'])->withInput();
                    }
                    $seller->iban = $iban;
                }
                if ($request->filled('tax_number')) $seller->tax_number = $request->tax_number;
                $seller->kyc_status = 'pending';
                $seller->kyc_submitted_at = now();
                $seller->save();

                return redirect()->route('seller.kyc')->with(['messege' => 'Belge başarıyla yüklendi.', 'alert-type' => 'success']);
            })->name('kyc.upload');

            Route::post('kyc/update-info', function (\Illuminate\Http\Request $request) {
                $user = \Auth::guard('web')->user();
                $seller = $user->seller;
                $onboarding = app(\App\Services\SellerIyzicoOnboardingService::class);
                $hasRealEmail = $onboarding->hasValidContactEmail($user->email);

                $rules = [
                    'seller_type' => 'required|in:sole_proprietorship,limited_company,corporate',
                    'iban' => 'required|string',
                    'address' => 'required|string|min:10',
                    'tc_identity' => 'required|string',
                    'tax_number' => 'nullable|string',
                    'tax_office' => 'nullable|string',
                    'legal_company_title' => 'nullable|string',
                ];
                $messages = [];
                if (! $hasRealEmail) {
                    $rules['email'] = 'required|email|max:255|unique:users,email,' . $user->id;
                    $messages = [
                        'email.required' => 'Iyzico alt üye işyeri için e-posta adresi zorunludur.',
                        'email.email' => 'Geçerli bir e-posta adresi giriniz.',
                        'email.unique' => 'Bu e-posta adresi başka bir hesapta kayıtlı.',
                    ];
                }
                $request->validate($rules, $messages);

                $sellerType = $onboarding->normalizeSellerType($request->seller_type);
                $tc = preg_replace('/\D/', '', (string) $request->tc_identity);
                if (strlen($tc) !== 11) {
                    return redirect()->back()->withErrors(['tc_identity' => 'TC Kimlik No 11 haneli olmalıdır.'])->withInput();
                }

                $iban = strtoupper(preg_replace('/\s+/', '', (string) $request->iban));
                if (! preg_match('/^TR\d{24}$/', $iban)) {
                    return redirect()->back()->withErrors(['iban' => 'IBAN formatı hatalı. TR ile başlayan 26 karakterli numara olmalıdır.'])->withInput();
                }

                $address = trim((string) $request->address);
                if ($address === '' || $address === 'Adres bilgisi sonra tamamlanacak') {
                    return redirect()->back()->withErrors(['address' => 'Iyzico için geçerli bir adres giriniz.'])->withInput();
                }

                $errors = [];
                if (in_array($sellerType, ['sole_proprietorship', 'limited_company'], true)) {
                    if (! $request->filled('tax_office')) {
                        $errors['tax_office'] = 'Vergi dairesi zorunludur.';
                    }
                    if (! $request->filled('legal_company_title')) {
                        $errors['legal_company_title'] = 'Ticari unvan zorunludur.';
                    }
                }
                if ($sellerType === 'limited_company' && ! $request->filled('tax_number')) {
                    $errors['tax_number'] = 'Ltd / A.Ş. için vergi numarası zorunludur.';
                }
                if ($errors !== []) {
                    return redirect()->back()->withErrors($errors)->withInput();
                }

                $seller->seller_type = $sellerType;
                $seller->tc_identity = $tc;
                $seller->iban = $iban;
                $seller->address = $address;
                $seller->tax_office = $request->filled('tax_office') ? trim((string) $request->tax_office) : null;
                $seller->legal_company_title = $request->filled('legal_company_title') ? trim((string) $request->legal_company_title) : null;

                if (! $hasRealEmail) {
                    $user->email = strtolower(trim((string) $request->email));
                    $user->save();
                    $seller->email = $user->email;
                }

                if ($sellerType === 'limited_company') {
                    $seller->tax_number = trim((string) $request->tax_number);
                } else {
                    // Şahıs: vergi no boşsa TC kullanılır
                    $seller->tax_number = $request->filled('tax_number')
                        ? trim((string) $request->tax_number)
                        : $tc;
                }

                $seller->save();

                return redirect()->route('seller.kyc')->with(['messege' => 'Bilgiler kaydedildi.', 'alert-type' => 'success']);
            })->name('kyc.update-info');

            Route::get('inventory', [SellerInventoryController::class, 'index'])->name('inventory');
            Route::get('stock-history/{id}', [SellerInventoryController::class, 'show_inventory'])->name('stock-history');
            Route::post('add-stock', [SellerInventoryController::class, 'add_stock'])->name('add-stock');
            Route::delete('delete-stock/{id}', [SellerInventoryController::class, 'delete_stock'])->name('delete-stock');
        });
    });



    //delivery man routes — modül kapalı (FEATURE_DELIVERYMAN=false)
    Route::middleware('deliveryman.enabled')->group(function () {
    Route::get('deliveryman/login', [DeliveryManLoginController::class, 'LoginPage'])->name('delivery.man.login');
    Route::post('deliveryman/login', [DeliveryManLoginController::class, 'dashboardLogin'])->name('delivery.man.login.submit');
    Route::get('deliveryman/logout', [DeliveryManLoginController::class, 'logout'])->name('deliveryman.logout');

    Route::get('deliveryman/password/reset', [DeliveryManResetPasswordController::class, 'passwordReset'])->name('deliveryman.password.reset');
    Route::post('deliveryman/password/reset/email', [DeliveryManResetPasswordController::class, 'passwrodResetEmail'])->name('deliveryman.password.reset.email');
    Route::get('deliveryman/password/reset/{token}', [DeliveryManResetPasswordController::class, 'passwordResetPage'])->name('deliveryman.password.reset.page');
    Route::post('deliveryman/password/update', [DeliveryManResetPasswordController::class, 'passwrodUpdate'])->name('deliveryman.pasword.update');



    Route::group(['as' => 'deliveryman.', 'prefix' => 'deliveryman', 'middleware' => 'deliveryman'], function () {
        Route::get('dashboard', [DeliveryManDashboardController::class, 'index'])->name('dashboard');
        Route::get('my-profile', [DeliveryManProfileController::class, 'index'])->name('my-profile');
        Route::get('edit-profile', [DeliveryManProfileController::class, 'edit'])->name('edit-profile');
        Route::put('update-profile', [DeliveryManProfileController::class, 'update'])->name('update-profile');
        Route::get('edit-password', [DeliveryManProfileController::class, 'password'])->name('edit-password');
        Route::put('update-password', [DeliveryManProfileController::class, 'updatePassword'])->name('update-password');
        Route::post('update-lat-long', [DeliveryManProfileController::class, 'updateLocation'])->name('update.lat-long');
        Route::get('orders', [DeliveryManOrderController::class, 'index'])->name('orders');

        Route::get('order-request', [DeliveryManOrderController::class, 'orderRequest'])->name('order-request');
        Route::put('order-request-status/{id}', [DeliveryManOrderController::class, 'orderRequestStatus'])->name('order-request-status');

        Route::get('completed-order', [DeliveryManOrderController::class, 'completedOrder'])->name('completed-order');
        Route::get('order-show/{id}', [DeliveryManOrderController::class, 'show'])->name('order-show');
        Route::put('update-order-status/{id}', [DeliveryManOrderController::class, 'updateOrderStatus'])->name('update-order-status');

        Route::resource('withdraw', MyWithdrawController::class);

        Route::get('get-withdraw-account-info/{id}', [MyWithdrawController::class, 'getWithDrawAccountInfo'])->name('get-withdraw-account-info');

        Route::get('my-review', [MyReviewController::class, 'index'])->name('my-review');

        Route::get('message-with-customer/{order_id}', [DeliveryMessageController::class, 'message_with_customer'])->name('message-with-customer');
        Route::get('get-message-with-customer/{order_id}', [DeliveryMessageController::class, 'get_message_with_customer'])->name('get-message-with-customer');
        Route::get('sent-message-to-customer', [DeliveryMessageController::class, 'sent_message_to_customer'])->name('sent-message-to-customer');

        Route::get('logout', [DeliveryManLoginController::class, 'logout'])->name('logout');
    });

    }); // deliveryman.enabled

    // start admin routes
    Route::group(['as' => 'admin.', 'prefix' => 'admin'], function () {

        // start auth route
        Route::get('login', [AdminLoginController::class, 'adminLoginPage'])->name('login');
        Route::post('login', [AdminLoginController::class, 'storeLogin'])->middleware('throttle:auth-login')->name('login.post');
        Route::get('forget-password', [AdminForgotPasswordController::class, 'forgetPassword'])->name('forget-password');
        Route::post('send-forget-password', [AdminForgotPasswordController::class, 'sendForgetEmail'])->middleware('throttle:password-reset')->name('send.forget.password');
        Route::get('reset-password/{token}', [AdminForgotPasswordController::class, 'resetPassword'])->name('reset.password');
        Route::post('password-store/{token}', [AdminForgotPasswordController::class, 'storeResetData'])->name('store.reset.password');
        // end auth route

        Route::group(['middleware' => ['auth:admin', 'not-call-center']], function () {
        Route::post('logout', [AdminLoginController::class, 'adminLogout'])->name('logout');
        Route::get('/', [DashboardController::class, 'dashobard'])->name('dashboard');
        Route::get('dashboard', [DashboardController::class, 'dashobard']);
        Route::get('profile', [AdminProfileController::class, 'index'])->name('profile');
        Route::put('profile-update', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::post('update-lat-long', [AdminProfileController::class, 'updateLocation'])->name('update.lat-long');

        Route::resource('product-category', ProductCategoryController::class);
        Route::put('product-category-status/{id}', [ProductCategoryController::class, 'changeStatus'])->name('product.category.status');

        Route::resource('product-sub-category', ProductSubCategoryController::class);
        Route::put('product-sub-category-status/{id}', [ProductSubCategoryController::class, 'changeStatus'])->name('product.sub.category.status');

        Route::resource('product-child-category', ProductChildCategoryController::class);
        Route::put('product-child-category-status/{id}', [ProductChildCategoryController::class, 'changeStatus'])->name('product.child.category.status');
        Route::get('subcategory-by-category/{id}', [ProductChildCategoryController::class, 'getSubcategoryByCategory'])->name('subcategory-by-category');
        Route::get('childcategory-by-subcategory/{id}', [ProductChildCategoryController::class, 'getChildcategoryBySubCategory'])->name('childcategory-by-subcategory');

        // Iyzico taksit kategori ayarları
        Route::get('installment-categories', [InstallmentCategoryController::class, 'index'])->name('installment-categories.index');
        Route::put('installment-categories', [InstallmentCategoryController::class, 'update'])->name('installment-categories.update');

        // İkinci El (C2C) - Admin bölümü
        Route::get('second-hand', [SecondHandController::class, 'index'])->name('second-hand.index');
        Route::get('second-hand/verifications', [SecondHandController::class, 'verifications'])->name('second-hand.verifications');
        Route::put('second-hand/verifications/{id}/approve', [SecondHandController::class, 'approveVerification'])->name('second-hand.verifications.approve');
        Route::put('second-hand/verifications/{id}/reject', [SecondHandController::class, 'rejectVerification'])->name('second-hand.verifications.reject');
        Route::get('second-hand/verifications/{id}/download-tax-document', [SecondHandController::class, 'downloadVerificationTaxDocument'])->name('second-hand.verifications.download-tax-document');
        Route::get('second-hand/verifications/{id}/download-barber-document', [SecondHandController::class, 'downloadVerificationBarberDocument'])->name('second-hand.verifications.download-barber-document');
        Route::get('second-hand/members', [SecondHandController::class, 'members'])->name('second-hand.members');
        Route::get('second-hand/listings', [SecondHandController::class, 'listings'])->name('second-hand.listings');
        Route::put('second-hand/listings/{id}/approve', [SecondHandController::class, 'approveListing'])->name('second-hand.listings.approve');
        Route::put('second-hand/listings/{id}/reject', [SecondHandController::class, 'rejectListing'])->name('second-hand.listings.reject');
        Route::put('second-hand/listings/{id}/featured', [SecondHandController::class, 'setListingFeatured'])->name('second-hand.listings.featured');
        Route::delete('second-hand/listings/{id}/featured', [SecondHandController::class, 'unsetListingFeatured'])->name('second-hand.listings.featured.unset');
        Route::put('second-hand/listings/{id}/urgent', [SecondHandController::class, 'setListingUrgent'])->name('second-hand.listings.urgent');
        Route::delete('second-hand/listings/{id}/urgent', [SecondHandController::class, 'unsetListingUrgent'])->name('second-hand.listings.urgent.unset');
        Route::put('second-hand/listings/{id}/deactivate', [SecondHandController::class, 'deactivateListing'])->name('second-hand.listings.deactivate');
        Route::put('second-hand/listings/{id}/activate', [SecondHandController::class, 'activateListing'])->name('second-hand.listings.activate');
        Route::get('second-hand/reports', [SecondHandController::class, 'reports'])->name('second-hand.reports');
        Route::get('second-hand/moderation-logs', [SecondHandController::class, 'moderationLogs'])->name('second-hand.moderation-logs');
        Route::get('second-hand/blocks', [SecondHandController::class, 'blocks'])->name('second-hand.blocks');
        Route::get('second-hand/messages', [SecondHandController::class, 'messagesInbox'])->name('second-hand.messages');
        Route::get('second-hand/messages/conversations/{conversationId}', [SecondHandController::class, 'messagesConversation'])->name('second-hand.messages.conversation');
        Route::get('second-hand/agreements', [SecondHandController::class, 'agreements'])->name('second-hand.agreements');
        Route::put('second-hand/agreements', [SecondHandController::class, 'updateAgreements'])->name('second-hand.agreements.update');
        Route::get('second-hand/homepage', [SecondHandController::class, 'homepage'])->name('second-hand.homepage');
        Route::put('second-hand/homepage', [SecondHandController::class, 'updateHomepage'])->name('second-hand.homepage.update');
        Route::post('second-hand/homepage/sliders', [SecondHandController::class, 'storeHomepageSlider'])->name('second-hand.homepage.sliders.store');
        Route::delete('second-hand/homepage/sliders/{id}', [SecondHandController::class, 'deleteHomepageSlider'])->name('second-hand.homepage.sliders.delete');
        Route::put('second-hand/reports/{id}', [SecondHandController::class, 'updateReportStatus'])->name('second-hand.reports.update');

        // Salon CRM — admin yönetimi
        Route::get('salon-crm', [SalonCrmAdminController::class, 'index'])->name('salon-crm.index');
        Route::get('salon-crm/{id}', [SalonCrmAdminController::class, 'show'])->name('salon-crm.show');
        Route::put('salon-crm/{id}/access', [SalonCrmAdminController::class, 'updateAccess'])->name('salon-crm.update-access');

        Route::resource('product-brand', ProductBrandController::class);
        Route::put('product-brand-status/{id}', [ProductBrandController::class, 'changeStatus'])->name('product.brand.status');

        Route::resource('specification-key', SpecificationKeyController::class);
        Route::put('specification-key-status/{id}', [SpecificationKeyController::class, 'changeStatus'])->name('specification-key.status');

        Route::resource('testimonial', TestimonialController::class);
        Route::put('testimonial-status/{id}', [TestimonialController::class, 'changeStatus'])->name('testimonial.status');

        Route::resource('product', ProductController::class);
        Route::get('create-product-info', [ProductController::class, 'create'])->name('create-product-info');
        Route::put('product-status/{id}', [ProductController::class, 'changeStatus'])->name('product.status');
        Route::put('product-homepage-flag/{id}', [ProductController::class, 'toggleHomepageFlag'])->name('product.homepage-flag');
        Route::put('product-homepage-qty', [ProductController::class, 'updateHomepageQty'])->name('product.homepage-qty');
        Route::put('product-approved/{id}', [ProductController::class, 'productApproved'])->name('product-approved');
        Route::put('removed-product-exist-specification/{id}', [ProductController::class, 'removedProductExistSpecification'])->name('removed-product-exist-specification');
        Route::get('seller-product', [ProductController::class, 'sellerProduct'])->name('seller-product');
        Route::get('seller-pending-product', [ProductController::class, 'sellerPendingProduct'])->name('seller-pending-product');
        Route::get('stockout-product', [ProductController::class, 'stockoutProduct'])->name('stockout-product');

        Route::get('product-import-page', [ProductController::class, 'product_import_page'])->name('product-import-page');
        Route::get('product-export', [ProductController::class, 'product_export'])->name('product-export');
        Route::get('product-demo-export', [ProductController::class, 'demo_product_export'])->name('product-demo-export');
        Route::get('product-bulk-import-template', [ProductController::class, 'product_bulk_import_template'])->name('product-bulk-import-template');
        Route::post('product-import', [ProductController::class, 'product_import'])->name('product-import');



        Route::get('product-variant/{id}', [ProductVariantController::class, 'index'])->name('product-variant');
        Route::get('create-product-variant/{id}', [ProductVariantController::class, 'create'])->name('create-product-variant');
        Route::post('store-product-variant', [ProductVariantController::class, 'store'])->name('store-product-variant');
        Route::get('get-product-variant/{id}', [ProductVariantController::class, 'show'])->name('get-product-variant');
        Route::put('update-product-variant/{id}', [ProductVariantController::class, 'update'])->name('update-product-variant');
        Route::delete('delete-product-variant/{id}', [ProductVariantController::class, 'destroy'])->name('delete-product-variant');
        Route::put('product-variant-status/{id}', [ProductVariantController::class, 'changeStatus'])->name('product-variant.status');

        Route::get('product-variant-item', [ProductVariantItemController::class, 'index'])->name('product-variant-item');
        Route::get('create-product-variant-item/{id}', [ProductVariantItemController::class, 'create'])->name('create-product-variant-item');
        Route::post('store-product-variant-item', [ProductVariantItemController::class, 'store'])->name('store-product-variant-item');
        Route::get('edit-product-variant-item/{id}', [ProductVariantItemController::class, 'edit'])->name('edit-product-variant-item');
        Route::get('get-product-variant-item/{id}', [ProductVariantItemController::class, 'show'])->name('egetdit-product-variant-item');

        Route::put('update-product-variant-item/{id}', [ProductVariantItemController::class, 'update'])->name('update-product-variant-item');
        Route::delete('delete-product-variant-item/{id}', [ProductVariantItemController::class, 'destroy'])->name('delete-product-variant-item');
        Route::put('product-variant-item-status/{id}', [ProductVariantItemController::class, 'changeStatus'])->name('product-variant-item.status');


        Route::get('product-gallery/{id}', [ProductGalleryController::class, 'index'])->name('product-gallery');
        Route::post('store-product-gallery', [ProductGalleryController::class, 'store'])->name('store-product-gallery');
        Route::delete('delete-product-image/{id}', [ProductGalleryController::class, 'destroy'])->name('delete-product-image');
        Route::put('product-gallery-status/{id}', [ProductGalleryController::class, 'changeStatus'])->name('product-gallery.status');

        Route::resource('service', ServiceController::class);
        Route::put('service-status/{id}', [ServiceController::class, 'changeStatus'])->name('service.status');

        Route::resource('about-us', AboutUsController::class);
        Route::resource('contact-us', ContactPageController::class);

        Route::resource('custom-page', CustomPageController::class);
        Route::put('custom-page-status/{id}', [CustomPageController::class, 'changeStatus'])->name('custom-page.status');

        // Blog
        Route::resource('blog', \App\Http\Controllers\WEB\Admin\BlogController::class);
        Route::put('blog-status/{id}', [\App\Http\Controllers\WEB\Admin\BlogController::class, 'changeStatus'])->name('blog.status');
        Route::resource('blog-category', \App\Http\Controllers\WEB\Admin\BlogCategoryController::class);
        Route::put('blog-category-status/{id}', [\App\Http\Controllers\WEB\Admin\BlogCategoryController::class, 'changeStatus'])->name('blog-category.status');
        Route::post('store-blog-gallery', [\App\Http\Controllers\WEB\Admin\BlogController::class, 'storeGallery'])->name('store-blog-gallery');
        Route::delete('delete-blog-image/{id}', [\App\Http\Controllers\WEB\Admin\BlogController::class, 'destroyGallery'])->name('delete-blog-image');

        // Raporlar
        Route::get('report/orders', [\App\Http\Controllers\WEB\Admin\ReportController::class, 'orderReport'])->name('report.orders');
        Route::get('report/sellers', [\App\Http\Controllers\WEB\Admin\ReportController::class, 'sellerReport'])->name('report.sellers');
        Route::get('report/products', [\App\Http\Controllers\WEB\Admin\ReportController::class, 'productReport'])->name('report.products');
        Route::get('report/transactions', [\App\Http\Controllers\WEB\Admin\ReportController::class, 'transactionReport'])->name('report.transactions');
        Route::get('report/returns', [\App\Http\Controllers\WEB\Admin\ReportController::class, 'returnReport'])->name('report.returns');

        Route::resource('legal-documents', AdminLegalDocumentController::class)->except(['show', 'destroy']);
        Route::get('legal-documents/{legal_document}/consents', [AdminLegalDocumentController::class, 'consents'])->name('legal-documents.consents');

        Route::resource('terms-and-condition', TermsAndConditionController::class);
        Route::resource('privacy-policy', PrivacyPolicyController::class);


        Route::put('update-database', [SettingController::class, 'update_database'])->name('update-database');

        Route::get('subscriber', [SubscriberController::class, 'index'])->name('subscriber');
        Route::delete('delete-subscriber/{id}', [SubscriberController::class, 'destroy'])->name('delete-subscriber');
        Route::post('specification-subscriber-email/{id}', [SubscriberController::class, 'specificationSubscriberEmail'])->name('specification-subscriber-email');
        Route::post('each-subscriber-email', [SubscriberController::class, 'eachSubscriberEmail'])->name('each-subscriber-email');

        Route::prefix('sms-campaigns')->name('sms-campaigns.')->group(function () {
            Route::get('/', [SmsCampaignController::class, 'index'])->name('index');
            Route::get('/create', [SmsCampaignController::class, 'create'])->name('create');
            Route::post('/', [SmsCampaignController::class, 'store'])->name('store');
            Route::post('/preview', [SmsCampaignController::class, 'preview'])->name('preview');
            Route::get('/messages', [SmsCampaignController::class, 'messages'])->name('messages');
            Route::get('/messages/create', [SmsCampaignController::class, 'createMessage'])->name('messages.create');
            Route::post('/messages', [SmsCampaignController::class, 'storeMessage'])->name('messages.store');
            Route::get('/messages/{id}/edit', [SmsCampaignController::class, 'editMessage'])->name('messages.edit');
            Route::put('/messages/{id}', [SmsCampaignController::class, 'updateMessage'])->name('messages.update');
            Route::delete('/messages/{id}', [SmsCampaignController::class, 'deleteMessage'])->name('messages.delete');
            Route::get('/{id}', [SmsCampaignController::class, 'show'])->name('show');
        });

        Route::get('contact-message', [ContactMessageController::class, 'index'])->name('contact-message');
        Route::get('show-contact-message/{id}', [ContactMessageController::class, 'show'])->name('show-contact-message');
        Route::delete('delete-contact-message/{id}', [ContactMessageController::class, 'destroy'])->name('delete-contact-message');
        Route::put('enable-save-contact-message', [ContactMessageController::class, 'handleSaveContactMessage'])->name('enable-save-contact-message');

        Route::get('email-configuration', [EmailConfigurationController::class, 'index'])->name('email-configuration');
        Route::put('update-email-configuraion', [EmailConfigurationController::class, 'update'])->name('update-email-configuraion');

        Route::get('email-template', [EmailTemplateController::class, 'index'])->name('email-template');
        Route::get('edit-email-template/{id}', [EmailTemplateController::class, 'edit'])->name('edit-email-template');
        Route::put('update-email-template/{id}', [EmailTemplateController::class, 'update'])->name('update-email-template');

        Route::get('general-setting', [SettingController::class, 'index'])->name('general-setting');
        Route::put('update-general-setting', [SettingController::class, 'updateGeneralSetting'])->name('update-general-setting');

        Route::put('update-theme-color', [SettingController::class, 'updateThemeColor'])->name('update-theme-color');

        Route::put('update-logo-favicon', [SettingController::class, 'updateLogoFavicon'])->name('update-logo-favicon');
        Route::put('update-cookie-consent', [SettingController::class, 'updateCookieConset'])->name('update-cookie-consent');
        Route::put('update-google-recaptcha', [SettingController::class, 'updateGoogleRecaptcha'])->name('update-google-recaptcha');
        Route::put('update-facebook-comment', [SettingController::class, 'updateFacebookComment'])->name('update-facebook-comment');
        Route::put('update-tawk-chat', [SettingController::class, 'updateTawkChat'])->name('update-tawk-chat');
        Route::put('update-google-analytic', [SettingController::class, 'updateGoogleAnalytic'])->name('update-google-analytic');
        Route::put('update-custom-pagination', [SettingController::class, 'updateCustomPagination'])->name('update-custom-pagination');
        Route::put('update-social-login', [SettingController::class, 'updateSocialLogin'])->name('update-social-login');
        Route::put('update-facebook-pixel', [SettingController::class, 'updateFacebookPixel'])->name('update-facebook-pixel');
        Route::put('update-pusher', [SettingController::class, 'updatePusher'])->name('update-pusher');
        Route::get('geliver-settings', [GeliverSettingsController::class, 'index'])->name('geliver-settings');
        Route::put('update-geliver-settings', [GeliverSettingsController::class, 'update'])->name('update-geliver-settings');
        Route::post('geliver-settings/create-sender-address', [GeliverSettingsController::class, 'createSenderAddress'])->name('geliver-settings.create-sender-address');


        Route::resource('admin', AdminController::class);
        Route::put('admin-status/{id}', [AdminController::class, 'changeStatus'])->name('admin-status');

        Route::resource('faq', FaqController::class);
        Route::put('faq-status/{id}', [FaqController::class, 'changeStatus'])->name('faq-status');


        Route::get('product-review', [ProductReviewController::class, 'index'])->name('product-review');
        Route::put('product-review-status/{id}', [ProductReviewController::class, 'changeStatus'])->name('product-review-status');
        Route::get('show-product-review/{id}', [ProductReviewController::class, 'show'])->name('show-product-review');
        Route::delete('delete-product-review/{id}', [ProductReviewController::class, 'destroy'])->name('delete-product-review');

        Route::get('product-report', [ProductReportController::class, 'index'])->name('product-report');
        Route::get('show-product-report/{id}', [ProductReportController::class, 'show'])->name('show-product-report');
        Route::delete('delete-product-report/{id}', [ProductReportController::class, 'destroy'])->name('delete-product-report');
        Route::put('de-active-product/{id}', [ProductReportController::class, 'deactiveProduct'])->name('de-active-product');

        Route::get('customer-list', [CustomerController::class, 'index'])->name('customer-list');
        Route::get('customer-show/{id}', [CustomerController::class, 'show'])->name('customer-show');
        Route::put('customer-status/{id}', [CustomerController::class, 'changeStatus'])->name('customer-status');
        Route::delete('customer-delete/{id}', [CustomerController::class, 'destroy'])->name('customer-delete');
        Route::get('pending-customer-list', [CustomerController::class, 'pendingCustomerList'])->name('pending-customer-list');
        Route::get('send-email-to-all-customer', [CustomerController::class, 'sendEmailToAllUser'])->name('send-email-to-all-customer');
        Route::post('send-mail-to-all-user', [CustomerController::class, 'sendMailToAllUser'])->name('send-mail-to-all-user');
        Route::post('send-mail-to-single-user/{id}', [CustomerController::class, 'sendMailToSingleUser'])->name('send-mail-to-single-user');


        Route::get('seller-list', [SellerController::class, 'index'])->name('seller-list');
        Route::get('call-center-registrations', [CallCenterRegistrationController::class, 'index'])->name('call-center-registrations.index');
        Route::post('call-center-registrations/{id}/resend-sms', [CallCenterRegistrationController::class, 'resendSms'])
            ->middleware('throttle:10,1')
            ->name('call-center-registrations.resend-sms');
        Route::post('call-center-registrations/{id}/pay-commission', [CallCenterRegistrationController::class, 'payCommission'])
            ->middleware('throttle:20,1')
            ->name('call-center-registrations.pay-commission');
        Route::get('seller-show/{id}', [SellerController::class, 'show'])->name('seller-show');
        Route::post('seller-show/{id}/resend-first-login-sms', [SellerController::class, 'resendFirstLoginSms'])
            ->middleware('throttle:10,1')
            ->name('seller-resend-first-login-sms');
        Route::put('seller-status/{id}', [SellerController::class, 'changeStatus'])->name('seller-status');
        Route::delete('seller-delete/{id}', [SellerController::class, 'destroy'])->name('seller-delete');
        Route::get('pending-seller-list', [SellerController::class, 'pendingSellerList'])->name('pending-seller-list');
        Route::get('seller-kyc', [AdminSellerKycController::class, 'index'])->name('kyc.index');
        Route::get('seller-kyc/{id}', [AdminSellerKycController::class, 'show'])->name('kyc.show');
        Route::put('seller-kyc/vendor/{id}/approve-all', [AdminSellerKycController::class, 'approveVendor'])->name('kyc.approve-vendor');
        Route::put('seller-kyc/{id}/approve', [AdminSellerKycController::class, 'approve'])->name('kyc.approve');
        Route::put('seller-kyc/{id}/reject', [AdminSellerKycController::class, 'reject'])->name('kyc.reject');
        Route::get('seller-kyc/document/{id}/download', [AdminSellerKycController::class, 'download'])->name('kyc.download');
        Route::post('seller-kyc/{id}/create-sub-merchant', [AdminSellerKycController::class, 'createSubMerchant'])->name('kyc.create-sub-merchant');
        Route::get('seller-product-overview', [SellerProductOverviewController::class, 'index'])->name('seller-product-overview.index');
        Route::put('seller-update/{id}', [SellerController::class, 'updateSeller'])->name('seller-update');
        Route::get('seller-shop-detail/{id}', [SellerController::class, 'sellerShopDetail'])->name('seller-shop-detail');
        Route::put('remove-seller-social-link/{id}', [SellerController::class, 'removeSellerSocialLink'])->name('remove-seller-social-link');


        Route::put('update-seller-shop/{id}', [SellerController::class, 'updateSellerSop'])->name('update-seller-shop');
        Route::get('seller-reviews/{id}', [SellerController::class, 'sellerReview'])->name('seller-reviews');
        Route::get('show-seller-review-details/{id}', [SellerController::class, 'showSellerReviewDetails'])->name('show-seller-review-details');
        Route::get('send-email-to-seller/{id}', [SellerController::class, 'sendEmailToSeller'])->name('send-email-to-seller');
        Route::post('send-mail-to-single-seller/{id}', [SellerController::class, 'sendMailtoSingleSeller'])->name('send-mail-to-single-seller');
        Route::get('email-history/{id}', [SellerController::class, 'emailHistory'])->name('email-history');
        Route::get('product-by-seller/{id}', [SellerController::class, 'productBySaller'])->name('product-by-seller');
        Route::get('send-email-to-all-seller', [SellerController::class, 'sendEmailToAllSeller'])->name('send-email-to-all-seller');
        Route::post('send-mail-to-all-seller', [SellerController::class, 'sendMailToAllSeller'])->name('send-mail-to-all-seller');
        Route::get('withdraw-list/{id}', [SellerController::class, 'sellerWithdrawList'])->name('withdraw-list');


        Route::get('state-by-country/{id}', [SellerController::class, 'stateByCountry'])->name('state-by-country');
        Route::get('city-by-state/{id}', [SellerController::class, 'cityByState'])->name('city-by-state');

        Route::resource('error-page', ErrorPageController::class);

        Route::get('maintainance-mode', [ContentController::class, 'maintainanceMode'])->name('maintainance-mode');
        Route::put('maintainance-mode-update', [ContentController::class, 'maintainanceModeUpdate'])->name('maintainance-mode-update');

        Route::get('announcement', [ContentController::class, 'announcementModal'])->name('announcement');
        Route::post('announcement-update', [ContentController::class, 'announcementModalUpdate'])->name('announcement-update');

        Route::get('topbar-contact', [ContentController::class, 'headerPhoneNumber'])->name('topbar-contact');
        Route::put('update-topbar-contact', [ContentController::class, 'updateHeaderPhoneNumber'])->name('update-topbar-contact');

        Route::get('product-quantity-progressbar', [ContentController::class, 'productProgressbar'])->name('product-quantity-progressbar');
        Route::put('update-product-quantity-progressbar', [ContentController::class, 'updateProductProgressbar'])->name('update-product-quantity-progressbar');

        Route::get('default-avatar', [ContentController::class, 'defaultAvatar'])->name('default-avatar');
        Route::post('update-default-avatar', [ContentController::class, 'updateDefaultAvatar'])->name('update-default-avatar');

        Route::get('seller-conditions', [ContentController::class, 'sellerCondition'])->name('seller-conditions');
        Route::put('update-seller-conditions', [ContentController::class, 'updatesellerCondition'])->name('update-seller-conditions');

        Route::get('subscription-banner', [ContentController::class, 'subscriptionBanner'])->name('subscription-banner');
        Route::post('update-subscription-banner', [ContentController::class, 'updatesubscriptionBanner'])->name('update-subscription-banner');




        Route::get('flash-sale', [FlashSaleController::class, 'index'])->name('flash-sale');
        Route::put('update-flash-sale', [FlashSaleController::class, 'update'])->name('update-flash-sale');
        Route::get('flash-sale-product', [FlashSaleController::class, 'flash_sale_product'])->name('flash-sale-product');
        Route::post('store-flash-sale-product', [FlashSaleController::class, 'store'])->name('store-flash-sale-product');
        Route::put('flash-sale-product-status/{id}', [FlashSaleController::class, 'changeStatus'])->name('flash-sale-product-status');
        Route::delete('delete-flash-sale-product/{id}', [FlashSaleController::class, 'destroy'])->name('delete-flash-sale-product');


        Route::get('advertisement', [AdvertisementController::class, 'index'])->name('advertisement');

        Route::post('mega-menu-banner-update', [AdvertisementController::class, 'megaMenuBannerUpdate'])->name('mega-menu-banner-update');


        Route::post('slider-banner-one', [AdvertisementController::class, 'updateSliderBannerOne'])->name('slider-banner-one');

        Route::post('slider-banner-two', [AdvertisementController::class, 'updateSliderBannerTwo'])->name('slider-banner-two');

        Route::post('popular-category-sidebar', [AdvertisementController::class, 'updatePopularCategorySidebar'])->name('popular-category-sidebar');


        Route::post('homepage-two-col-first-banner', [AdvertisementController::class, 'homepageTwoColFirstBanner'])->name('homepage-two-col-first-banner');


        Route::post('homepage-two-col-second-banner', [AdvertisementController::class, 'homepageTwoColSecondBanner'])->name('homepage-two-col-second-banner');


        Route::post('homepage-single-first-banner', [AdvertisementController::class, 'homepageSinleFirstBanner'])->name('homepage-single-first-banner');

        Route::post('homepage-single-second-banner', [AdvertisementController::class, 'homepageSinleSecondBanner'])->name('homepage-single-second-banner');


        Route::post('homepage-flash-sale-sidebar-banner', [AdvertisementController::class, 'homepageFlashSaleSidebarBanner'])->name('homepage-flash-sale-sidebar-banner');


        Route::post('shop-page-center-banner', [AdvertisementController::class, 'shopPageCenterBanner'])->name('shop-page-center-banner');

        Route::post('shop-page-sidebar-banner', [AdvertisementController::class, 'shopPageSidebarBanner'])->name('shop-page-sidebar-banner');

        Route::get('login-page', [ContentController::class, 'loginPage'])->name('login-page');
        Route::post('update-login-page', [ContentController::class, 'updateloginPage'])->name('update-login-page');

        Route::get('image-content', [ContentController::class, 'image_content'])->name('image-content');
        Route::post('update-image-content', [ContentController::class, 'updateImageContent'])->name('update-image-content');

        Route::get('shop-page', [ContentController::Class, 'shopPage'])->name('shop-page');
        Route::put('update-filter-price', [ContentController::Class, 'updateFilterPrice'])->name('update-filter-price');

        Route::get('seo-setup', [ContentController::Class, 'seoSetup'])->name('seo-setup');
        Route::put('update-seo-setup/{id}', [ContentController::Class, 'updateSeoSetup'])->name('update-seo-setup');
        Route::get('get-seo-setup/{id}', [ContentController::Class, 'getSeoSetup'])->name('get-seo-setup');



        Route::resource('country', CountryController::class);
        Route::put('country-status/{id}', [CountryController::class, 'changeStatus'])->name('country-status');

        Route::get('country-import-page', [CountryController::class, 'country_import_page'])->name('country-import-page');
        Route::get('country-export', [CountryController::class, 'country_export'])->name('country-export');
        Route::get('country-demo-export', [CountryController::class, 'demo_country_export'])->name('country-demo-export');
        Route::post('country-import', [CountryController::class, 'country_import'])->name('country-import');

        Route::resource('state', CountryStateController::class);
        Route::put('state-status/{id}', [CountryStateController::class, 'changeStatus'])->name('state-status');

        Route::get('state-import-page', [CountryStateController::class, 'state_import_page'])->name('state-import-page');
        Route::get('state-export', [CountryStateController::class, 'state_export'])->name('state-export');
        Route::get('state-demo-export', [CountryStateController::class, 'demo_state_export'])->name('state-demo-export');
        Route::post('state-import', [CountryStateController::class, 'state_import'])->name('state-import');

        Route::resource('city', CityController::class);
        Route::put('city-status/{id}', [CityController::class, 'changeStatus'])->name('city-status');

        Route::get('city-import-page', [CityController::class, 'city_import_page'])->name('city-import-page');
        Route::get('city-export', [CityController::class, 'city_export'])->name('city-export');
        Route::get('city-demo-export', [CityController::class, 'demo_city_export'])->name('city-demo-export');
        Route::post('city-import', [CityController::class, 'city_import'])->name('city-import');

        Route::get('payment-method', [PaymentMethodController::class, 'index'])->name('payment-method');
        Route::put('update-bank', [PaymentMethodController::class, 'updateBank'])->name('update-bank');
        Route::put('update-cash-on-delivery', [PaymentMethodController::class, 'updateCashOnDelivery'])->name('update-cash-on-delivery');
        Route::put('update-iyzico', [PaymentMethodController::class, 'updateIyzico'])->name('update-iyzico');

        Route::resource('mega-menu-category', MegaMenuController::class);
        Route::put('mega-menu-category-status/{id}', [MegaMenuController::class, 'changeStatus'])->name('mega-menu-category-status');

        Route::get('mega-menu-sub-category/{id}', [MegaMenuSubCategoryController::class, 'index'])->name('mega-menu-sub-category');
        Route::get('create-mega-menu-sub-category/{id}', [MegaMenuSubCategoryController::class, 'create'])->name('create-mega-menu-sub-category');
        Route::get('get-mega-menu-sub-category/{id}', [MegaMenuSubCategoryController::class, 'show'])->name('get-mega-menu-sub-category');
        Route::post('store-mega-menu-sub-category/{id}', [MegaMenuSubCategoryController::class, 'store'])->name('store-mega-menu-sub-category');
        Route::get('edit-mega-menu-sub-category/{id}', [MegaMenuSubCategoryController::class, 'edit'])->name('edit-mega-menu-sub-category');
        Route::put('update-mega-menu-sub-category/{id}', [MegaMenuSubCategoryController::class, 'update'])->name('update-mega-menu-sub-category');
        Route::delete('delete-mega-menu-sub-category/{id}', [MegaMenuSubCategoryController::class, 'destroy'])->name('delete-mega-menu-sub-category');
        Route::put('mega-menu-sub-category-status/{id}', [MegaMenuSubCategoryController::class, 'changeStatus'])->name('mega-menu-sub-category-status');


        Route::resource('slider', SliderController::class);
        Route::put('slider-status/{id}', [SliderController::class, 'changeStatus'])->name('slider-status');
        Route::get('mobile-slider', [MobileSliderController::class, 'index'])->name('mobile-slider.index');
        Route::post('mobile-slider', [MobileSliderController::class, 'store'])->name('mobile-slider.store');
        Route::delete('mobile-slider/{id}', [MobileSliderController::class, 'destroy'])->name('mobile-slider.destroy');


        Route::get('popular-category', [HomePageController::class, 'popularCategory'])->name('popular-category');
        Route::post('store-popular-category', [HomePageController::class, 'storePopularCategory'])->name('store-popular-category');
        Route::delete('destroy-popular-category/{id}', [HomePageController::class, 'destroyPopularCategory'])->name('destroy-popular-category');

        Route::put('popular-category-banner', [HomePageController::class, 'bannerPopularCategory'])->name('popular-category-banner');

        Route::put('featured-category-banner', [HomePageController::class, 'bannerFeaturedCategory'])->name('featured-category-banner');

        Route::get('featured-category', [HomePageController::class, 'featuredCategory'])->name('featured-category');
        Route::post('store-featured-category', [HomePageController::class, 'storeFeaturedCategory'])->name('store-featured-category');
        Route::delete('destroy-featured-category/{id}', [HomePageController::class, 'destroyFeaturedCategory'])->name('destroy-featured-category');


        Route::get('homepage-section-title', [HomePageController::class, 'homepage_section_content'])->name('homepage-section-title');
        Route::post('update-homepage-section-title', [HomePageController::class, 'update_homepage_section_content'])->name('update-homepage-section-title');



        Route::get('homepage-visibility', [HomepageVisibilityController::class, 'index'])->name('homepage-visibility');
        Route::put('update-homepage-visibility', [HomepageVisibilityController::class, 'update'])->name('update-homepage-visibility');

        Route::get('menu-visibility', [MenuVisibilityController::class, 'index'])->name('menu-visibility');
        Route::put('update-menu-visibility/{id}', [MenuVisibilityController::class, 'update'])->name('update-menu-visibility');

        Route::resource('shipping', ShippingMethodController::class);
        Route::get('city-wise-shipping/{city_id}', [ShippingMethodController::class, 'cityWiseShipping'])->name('city-wise-shipping');

        Route::get('shipping-import-page', [ShippingMethodController::class, 'shipping_import_page'])->name('shipping-import-page');
        Route::get('shipping-export', [ShippingMethodController::class, 'shipping_export'])->name('shipping-export');
        Route::get('shipping-demo-export', [ShippingMethodController::class, 'demo_shipping_export'])->name('shipping-demo-export');
        Route::post('shipping-import', [ShippingMethodController::class, 'shipping_import'])->name('shipping-import');

        Route::put('update-per-km-price/{id}', [ShippingMethodController::class, 'updatePerKmPrice'])->name('update-per-km-price');

        Route::resource('withdraw-method', WithdrawMethodController::class);
        Route::put('withdraw-method-status/{id}', [WithdrawMethodController::class, 'changeStatus'])->name('withdraw-method-status');

        Route::get('seller-withdraw', [SellerWithdrawController::class, 'index'])->name('seller-withdraw');
        Route::get('pending-seller-withdraw', [SellerWithdrawController::class, 'pendingSellerWithdraw'])->name('pending-seller-withdraw');

        Route::get('show-seller-withdraw/{id}', [SellerWithdrawController::class, 'show'])->name('show-seller-withdraw');
        Route::delete('delete-seller-withdraw/{id}', [SellerWithdrawController::class, 'destroy'])->name('delete-seller-withdraw');
        Route::put('approved-seller-withdraw/{id}', [SellerWithdrawController::class, 'approvedWithdraw'])->name('approved-seller-withdraw');

        Route::get('all-order', [OrderController::class, 'index'])->name('all-order');
        Route::get('pending-order', [OrderController::class, 'pendingOrder'])->name('pending-order');
        Route::get('pregress-order', [OrderController::class, 'pregressOrder'])->name('pregress-order');
        Route::get('delivered-order', [OrderController::class, 'deliveredOrder'])->name('delivered-order');
        Route::get('completed-order', [OrderController::class, 'completedOrder'])->name('completed-order');
        Route::get('declined-order', [OrderController::class, 'declinedOrder'])->name('declined-order');
        Route::get('cash-on-delivery', [OrderController::class, 'cashOnDelivery'])->name('cash-on-delivery');
        Route::get('bank-transfer-pending', [OrderController::class, 'bankTransferPending'])->name('bank-transfer-pending');
        Route::get('approve-payment/{id}', [OrderController::class, 'approvePayment'])->name('approve-payment');
        
        Route::get('order-show/{id}', [OrderController::class, 'show'])->name('order-show');
        Route::post('orders/{id}/payout/block', [OrderController::class, 'blockPayout'])->name('orders.payout.block');
        Route::post('orders/{id}/payout/unblock', [OrderController::class, 'unblockPayout'])->name('orders.payout.unblock');
        Route::post('orders/{id}/payout/hold', [OrderController::class, 'holdPayout'])->name('orders.payout.hold');
        Route::post('orders/{id}/payout/hold/clear', [OrderController::class, 'clearHoldPayout'])->name('orders.payout.hold.clear');
        Route::post('orders/{id}/payout/process', [OrderController::class, 'processPayout'])->name('orders.payout.process');
        Route::get('orders/{orderId}/cargo', [OrderCargoController::class, 'show'])->name('orders.cargo.show');
        Route::get('orders/{orderId}/cargo/offers', [OrderCargoController::class, 'offers'])->middleware('geliver.enabled')->name('orders.cargo.offers');
        Route::post('orders/{orderId}/cargo', [OrderCargoController::class, 'createShipment'])->middleware('geliver.enabled')->name('orders.cargo.create');
        Route::delete('orders/{orderId}/cargo', [OrderCargoController::class, 'cancel'])->middleware('geliver.enabled')->name('orders.cargo.cancel');
        Route::delete('delete-order/{id}', [OrderController::class, 'destroy'])->name('delete-order');
        Route::put('update-order-status/{id}', [OrderController::class, 'updateOrderStatus'])->name('update-order-status');

        // Commission Routes
        Route::get('commission-settings', [CommissionController::class, 'settings'])->name('commission-settings');
        Route::put('update-global-commission-rate', [CommissionController::class, 'updateGlobalRate'])->name('update-global-commission-rate');
        Route::put('update-payout-settings', [CommissionController::class, 'updatePayoutSettings'])->name('update-payout-settings');
        Route::put('update-vendor-commission-rate/{id}', [CommissionController::class, 'updateVendorRate'])->name('update-vendor-commission-rate');
        Route::delete('reset-vendor-commission-rate/{id}', [CommissionController::class, 'resetVendorRate'])->name('reset-vendor-commission-rate');
        Route::get('commission-report', [CommissionController::class, 'report'])->name('commission-report');

        // AI Settings Routes
        Route::get('ai-settings', [AiSettingsController::class, 'settings'])->name('ai-settings');
        Route::put('update-ai-settings', [AiSettingsController::class, 'update'])->name('update-ai-settings');
        Route::post('test-ai-connection', [AiSettingsController::class, 'testConnection'])->name('test-ai-connection');
        Route::post('ai-generate-content', [AiSettingsController::class, 'generateContent'])->name('ai-generate-content');

        // AI Chat Routes
        Route::get('ai-chat-settings', [App\Http\Controllers\WEB\Admin\AiChatController::class, 'settings'])->name('ai-chat-settings');
        Route::put('update-ai-chat-settings', [App\Http\Controllers\WEB\Admin\AiChatController::class, 'updateSettings'])->name('update-ai-chat-settings');
        Route::post('ai-chat-knowledge', [App\Http\Controllers\WEB\Admin\AiChatController::class, 'storeKnowledge'])->name('ai-chat-knowledge.store');
        Route::put('ai-chat-knowledge/{id}', [App\Http\Controllers\WEB\Admin\AiChatController::class, 'updateKnowledge'])->name('ai-chat-knowledge.update');
        Route::delete('ai-chat-knowledge/{id}', [App\Http\Controllers\WEB\Admin\AiChatController::class, 'deleteKnowledge'])->name('ai-chat-knowledge.delete');
        Route::put('ai-chat-knowledge/{id}/toggle', [App\Http\Controllers\WEB\Admin\AiChatController::class, 'toggleKnowledge'])->name('ai-chat-knowledge.toggle');
        Route::get('ai-chat-conversation/{id}/messages', [App\Http\Controllers\WEB\Admin\AiChatController::class, 'conversationMessages'])->name('ai-chat-conversation.messages');

        // Return Request Routes
        Route::get('return-requests', [App\Http\Controllers\WEB\Admin\ReturnRequestController::class, 'index'])->name('return-requests.index');
        Route::get('show-return-request/{id}', [App\Http\Controllers\WEB\Admin\ReturnRequestController::class, 'show'])->name('return-requests.show');
        Route::put('update-return-request/{id}', [App\Http\Controllers\WEB\Admin\ReturnRequestController::class, 'updateStatus'])->name('return-requests.update-status');

        Route::resource('coupon', CouponController::class);
        Route::put('coupon-status/{id}', [CouponController::class, 'changeStatus'])->name('coupon-status');

        Route::resource('currency', CurrencyController::class);
        Route::put('currency-status/{id}', [CurrencyController::class, 'changeStatus'])->name('coupon.status');

        Route::resource('banner-image', BreadcrumbController::class);

        Route::resource('footer', FooterController::class);
        Route::resource('social-link', FooterSocialLinkController::class);
        Route::resource('footer-link', FooterLinkController::class);
        Route::get('second-col-footer-link', [FooterLinkController::class, 'secondColFooterLink'])->name('second-col-footer-link');
        Route::get('third-col-footer-link', [FooterLinkController::class, 'thirdColFooterLink'])->name('third-col-footer-link');
        Route::put('update-col-title/{id}', [FooterLinkController::class, 'updateColTitle'])->name('update-col-title');


        Route::get('admin-language', [LanguageController::class, 'adminLnagugae'])->name('admin-language');
        Route::post('update-admin-language', [LanguageController::class, 'updateAdminLanguage'])->name('update-admin-language');

        Route::get('admin-validation-language', [LanguageController::class, 'adminValidationLnagugae'])->name('admin-validation-language');
        Route::post('update-admin-validation-language', [LanguageController::class, 'updateAdminValidationLnagugae'])->name('update-admin-validation-language');


        Route::get('website-language', [LanguageController::class, 'websiteLanguage'])->name('website-language');
        Route::post('update-language', [LanguageController::class, 'updateLanguage'])->name('update-language');

        Route::get('website-validation-language', [LanguageController::class, 'websiteValidationLanguage'])->name('website-validation-language');
        Route::post('update-validation-language', [LanguageController::class, 'updateValidationLanguage'])->name('update-validation-language');

        Route::get('languages', [AdminLanguageController::class, 'languages'])->name('languages');
        Route::get('language-create', [AdminLanguageController::class, 'create'])->name('language.create');
        Route::post('language-store', [AdminLanguageController::class, 'store'])->name('language.store');
        Route::get('language-edit/{id}', [AdminLanguageController::class, 'edit'])->name('language.edit');
        Route::put('language-update/{id}', [AdminLanguageController::class, 'update'])->name('language.update');
        Route::delete('language-delete/{id}', [AdminLanguageController::class, 'destroy'])->name('language-delete');


        Route::get('sms-notification', [NotificationController::class, 'sms_configuration'])->name('sms-notification');
        Route::put('update-netgsm-configuration', [NotificationController::class, 'update_netgsm'])->name('update-netgsm-configuration');

        Route::get('sms-template', [NotificationController::class, 'sms_template'])->name('sms-template');
        Route::get('edit-sms-template/{id}', [NotificationController::class, 'edit_sms_template'])->name('edit-sms-template');
        Route::put('update-sms-template/{id}', [NotificationController::class, 'update_sms_template'])->name('update-sms-template');

        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory');
        Route::get('stock-history/{id}', [InventoryController::class, 'show_inventory'])->name('stock-history');
        Route::post('add-stock', [InventoryController::class, 'add_stock'])->name('add-stock');
        Route::delete('delete-stock/{id}', [InventoryController::class, 'delete_stock'])->name('delete-stock');
        Route::get('stock-alerts', [WebAdminStockAlertController::class, 'index'])->name('stock-alerts.index');
        Route::put('stock-alerts', [WebAdminStockAlertController::class, 'update'])->name('stock-alerts.update');
        Route::get('push-notifications', [WebAdminPushNotificationController::class, 'index'])->name('push-notifications.index');
        Route::post('push-notifications', [WebAdminPushNotificationController::class, 'store'])->name('push-notifications.store');

        //Delivery man route
        Route::resource('delivery-man', DeliveryManController::class);
        Route::put('delivery-man-status/{id}', [DeliveryManController::class, 'changeStatus'])->name('delivery-man-status');

        Route::get('delivery-man-review', [DeliveryManController::class, 'review'])->name('delivery-man-review');
        Route::put('delivery-man-review-status/{id}', [DeliveryManController::class, 'changeReviewStatus'])->name('delivery-man-review-status');
        Route::delete('delete-delivery-man-review/{id}', [DeliveryManController::class, 'deleteReview'])->name('delete-delivery-man-review');

        Route::resource('delivery-man-withdraw-method', DeliveryManWithdrawMethodController::class);
        Route::put('delivery-man-withdraw-method-status/{id}', [DeliveryManWithdrawMethodController::class, 'changeStatus']);

        Route::get('delivery-man-withdraw', [DeliveryManWithdrawController::class, 'index'])->name('delivery-man-withdraw');
        Route::get('pending-delivery-man-withdraw', [DeliveryManWithdrawController::class, 'pendingDeliveryManWithdraw'])->name('pending-delivery-man-withdraw');

        Route::get('show-delivery-man-withdraw/{id}', [DeliveryManWithdrawController::class, 'show'])->name('show-delivery-man-withdraw');
        Route::delete('delete-delivery-man-withdraw/{id}', [DeliveryManWithdrawController::class, 'destroy'])->name('delete-delivery-man-withdraw');
        Route::put('approved-delivery-man-withdraw/{id}', [DeliveryManWithdrawController::class, 'approvedWithdraw'])->name('approved-delivery-man-withdraw');
        Route::get('delivery-man-withdraw-list/{id}', [DeliveryManWithdrawController::class, 'withdrawList'])->name('delivery-man-withdraw-list');


        Route::get('delivery-man-order-amount', [DeliveryManOrderAmountController::class, 'index'])->name('delivery-man-order-amount');
        Route::get('get-deliveryman-account-info/{id}', [DeliveryManOrderAmountController::class, 'getWithDeliveryManAccountInfo'])->name('get-deliveryman-account-info');

        Route::get('delivery-man-order-amount/create', [DeliveryManOrderAmountController::class, 'create'])->name('delivery-man-order-amount.create');
        Route::post('delivery-man-order-amount', [DeliveryManOrderAmountController::class, 'store'])->name('delivery-man-order-amount.store');

        Route::delete('delete-delivery-order-amount/{id}', [DeliveryManOrderAmountController::class, 'destroy'])->name('delivery-man-order-amount.delete');

        // Pos Routes........
        Route::get('/pos', [PosController::class, 'Index'])->name('pos.index');
        Route::get('/pos/category/{id}', [PosController::class, 'categoryIndex'])->name('pos.category.index');
        Route::get('/products/search', [PosController::class, 'search'])->name('pos.product.search');
        Route::get('/pos/add/product/{id}', [PosController::class, 'AddProduct'])->name('pos.add.product');
        Route::get('/pos/product/delete/{id}', [PosController::class, 'Destroy'])->name('pos.destroy.product');
        Route::get('/pos/product/cart/increment/{id}', [PosController::class, 'cartIncremet'])->name('pos.cart.increment.product');
        Route::get('/pos/product/cart/decrement/delete/{id}', [PosController::class, 'cartDecrement'])->name('pos.cart.decrement.product');
        Route::get('/pos/product/cart/clear', [PosController::class, 'clearCart'])->name('pos.cart.clear.product');
        Route::post('/pos/add/customer', [PosController::class, 'addCustomer'])->name('pos.add.customer');
        Route::get('/pos/apply/cupon', [PosController::class, 'applyCupon'])->name('pos.apply.cupon');
        Route::post('/pos/order/submit', [PosController::class, 'orderSubmit'])->name('pos.order.submit');
        Route::get('/pos/bulk/order', [PosController::class, 'bulkOrder'])->name('pos.bulk.order');
        Route::get('/pos/bulk/order/serch', [PosController::class, 'bulkOrderSerch'])->name('pos.bulk.order.serch');
        Route::put('/pos/bulk/order/status/change', [PosController::class, 'updateOrderStatus'])->name('pos.bulk.order.status.change');
        Route::put('/pos/update/cart/product', [PosController::class, 'updatePosCart'])->name('pos.update.cart.order');
        Route::post('/pos/add/product/with/detils/{id}', [PosController::class, 'AddProductWithDetils'])->name('pos.cart.order.detils');


        Route::post('/add-new-product-in-order/{id}', [OrderController::class, 'addNewProduct'])->name('add-new-product-in-order');
        Route::get('/increment-order-quantity/{id}/{order_id}', [OrderController::class, 'incrementOrderQuantity'])->name('order-quantity-increment');
        Route::get('/decrement-order-quantity/{id}/{order_id}', [OrderController::class, 'decrementOrderQuantity'])->name('order-quantity-decrement');
        Route::delete('/delete-order-product/{id}/{order_id}', [OrderController::class, 'deleteOrderProduct'])->name('delete-order-product');
        });
    });
});










Route::get('/run_migration', function () {
    try {


        Artisan::call('migrate');

        // Update the setting
        $setting = Setting::first();
        if ($setting) {
            $setting->current_version = '7.0.0';
            $setting->save();
        }

        Artisan::call('optimize:clear');

        // Notification message
        $notification = trans('Successfully Updated');
        $notification = array('messege' => $notification, 'alert-type' => 'success');
        return redirect()->route('admin.dashboard')->with($notification);
    } catch (Exception $e) {

        Log::info('update migrate');
        Log::info($e->getMessage());

        $notification = $e->getMessage();
        $notification = array('messege' => $notification, 'alert-type' => 'error');
        return redirect()->route('admin.dashboard')->with($notification);
    }
})->middleware('auth:admin')->name('run_migration');

Route::middleware(['auth:admin'])->group(function () {
    Route::get('admin/product-stats', [App\Http\Controllers\WEB\Admin\ProductStatsController::class, 'index'])->name('admin.product-stats.index');
    Route::get('admin/product-views', [App\Http\Controllers\WEB\Admin\ProductViewsController::class, 'index'])->name('admin.product-views.index');
    Route::get('admin/product-carts', [App\Http\Controllers\WEB\Admin\ProductCartsController::class, 'index'])->name('admin.product-carts.index');
    Route::get('admin/user-analytics', [App\Http\Controllers\WEB\Admin\UserAnalyticsController::class, 'index'])->name('admin.user-analytics.index');
    Route::get('admin/sales-analytics', [App\Http\Controllers\WEB\Admin\SalesAnalyticsController::class, 'index'])->name('admin.sales-analytics.index');
    Route::get('admin/seller-performance', [App\Http\Controllers\WEB\Admin\SellerPerformanceController::class, 'index'])->name('admin.seller-performance.index');
});
