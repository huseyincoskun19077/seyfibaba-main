<?php

use App\Http\Controllers\API\PublicSellerRegistrationController;
use App\Http\Controllers\API\Webhooks\GeliverWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;


use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\WEB\Admin\Auth\AdminLoginController;
use App\Http\Controllers\WEB\Admin\Auth\AdminForgotPasswordController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductSubCategoryController;
use App\Http\Controllers\Admin\ProductChildCategoryController;
use App\Http\Controllers\Admin\ProductBrandController;
use App\Http\Controllers\Admin\SpecificationKeyController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductGalleryController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\AboutUsController;
use App\Http\Controllers\Admin\ContactPageController;
use App\Http\Controllers\Admin\CustomPageController;
use App\Http\Controllers\Admin\TermsAndConditionController;
use App\Http\Controllers\Admin\PrivacyPolicyController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\ProductVariantItemController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SubscriberController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\EmailConfigurationController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\ProductReviewController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ErrorPageController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\CountryStateController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\SellerController;
use App\Http\Controllers\Admin\MegaMenuController;
use App\Http\Controllers\Admin\MegaMenuSubCategoryController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\HomePageController;
use App\Http\Controllers\Admin\ShippingMethodController;
use App\Http\Controllers\Admin\WithdrawMethodController;
use App\Http\Controllers\Admin\SellerWithdrawController;
use App\Http\Controllers\Admin\ProductReportController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\CommissionController as AdminCommissionController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\WEB\Admin\BreadcrumbController;
use App\Http\Controllers\Admin\FooterController;
use App\Http\Controllers\Admin\FooterSocialLinkController;
use App\Http\Controllers\Admin\FooterLinkController;
use App\Http\Controllers\WEB\Admin\HomepageVisibilityController;
use App\Http\Controllers\WEB\Admin\MenuVisibilityController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\AdvertisementController;
use App\Http\Controllers\Admin\FlashSaleController;




use App\Http\Controllers\Seller\SellerDashboardController;
use App\Http\Controllers\Seller\SellerProfileController;
use App\Http\Controllers\Seller\QuickProductController as ApiQuickProductController;
use App\Http\Controllers\Seller\SellerProductController;
use App\Http\Controllers\Seller\SellerProductGalleryController;
use App\Http\Controllers\Seller\SellerProductVariantController;
use App\Http\Controllers\Seller\SellerProductVariantItemController;
use App\Http\Controllers\Seller\SellerProductReviewController;
use App\Http\Controllers\Seller\SellerFinancialController;
use App\Http\Controllers\Seller\WithdrawController;
use App\Http\Controllers\Seller\EarningsController as SellerEarningsController;
use App\Http\Controllers\Seller\SellerProductReportControler;
use App\Http\Controllers\Seller\SellerOrderController;
use App\Http\Controllers\Seller\SellerMessageContoller;
use App\Http\Controllers\Seller\SellerContactAdminController;
use App\Http\Controllers\Seller\SellerInventoryController as ApiSellerInventoryController;
use App\Http\Controllers\Seller\SellerBrandController as ApiSellerBrandController;
use App\Http\Controllers\Seller\SellerAiAssistantController as ApiSellerAiAssistantController;
use App\Http\Controllers\Seller\SellerFaqController;
use App\Http\Controllers\Seller\SellerGuideController;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\User\UserProfileController;
use App\Http\Controllers\User\UserTokenController;
use App\Http\Controllers\User\CheckoutController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\User\IyzicoController;
use App\Http\Controllers\User\MessageController;
use App\Http\Controllers\User\AddressCotroller;
use App\Http\Controllers\User\SecondHandVerificationController;
use App\Http\Controllers\User\SecondHandListingController;
use App\Http\Controllers\User\SecondHandPublicController;
use App\Http\Controllers\User\SecondHandReportController;
use App\Http\Controllers\User\SecondHandMessagingController;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\RegisterController;

use App\Http\Controllers\Deliveryman\MyReviewController;
use App\Http\Controllers\Deliveryman\MyWithdrawController;

use App\Http\Controllers\Deliveryman\DeliveryMessageController;
use App\Http\Controllers\Deliveryman\DeliveryManOrderController;
use App\Http\Controllers\Deliveryman\DeliveryManProfileController;

use App\Http\Controllers\Deliveryman\DeliveryManDashboardController;
use App\Http\Controllers\Deliveryman\Auth\DeliveryManLoginController;
use App\Http\Controllers\Deliveryman\DeliveryManRegistrationController;
use App\Http\Controllers\Deliveryman\Auth\DeliveryManResetPasswordController;
use App\Http\Controllers\User\CheckoutWithoutTokenController;
use App\Http\Controllers\User\CountryGetController;
use App\Http\Controllers\User\ReturnRequestController as UserReturnRequestController;
use App\Http\Controllers\Seller\ReturnRequestController as SellerReturnRequestController;
use App\Http\Controllers\Admin\ReturnRequestController as AdminReturnRequestController;

Route::group([
    'prefix' => 'auth'

], function ($router) {

    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
    Route::post('otp/send', [OtpController::class, 'send'])->middleware('throttle:otp-send');
    Route::post('otp/verify', [OtpController::class, 'verify'])->middleware('throttle:otp-verify');
    Route::post('otp/resend', [OtpController::class, 'resend'])->middleware('throttle:otp-resend');

});

Route::get('/health-ping', function () {
    return response()->json(['ok' => true, 'time' => now()->toIso8601String()]);
});




Route::group(['middleware' => ['XSS']], function () {

Route::group([], function () {

    Route::get('/website-setup', [HomeController::class, 'websiteSetup'])->name('website-setup');
    Route::get('/subcategory-by-category/{id}', [HomeController::class, 'subCategoriesByCategory'])->name('subcategory-by-category');
    Route::get('/childcategory-by-subcategory/{id}', [HomeController::class, 'childCategoriesBySubCategory'])->name('childcategory-by-subcategory');
    Route::get('/category-list', [HomeController::class, 'categoryList'])->name('category-list');
    Route::get('/brand-list', [HomeController::class, 'brandList'])->name('brand-list');
    Route::get('/category/{id}', [HomeController::class, 'category'])->name('category');
    Route::get('/sub-category/{id}', [HomeController::class, 'subCategory'])->name('sub-category');
    Route::get('/child-category/{id}', [HomeController::class, 'childCategory'])->name('child-category');

    Route::get('/product-by-category/{id}', [HomeController::class, 'productByCategory'])->name('product-by-category');

    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/about-us', [HomeController::class, 'aboutUs'])->name('about-us');
    Route::get('/contact-us', [HomeController::class, 'contactUs'])->name('contact-us');
    Route::post('/send-contact-message', [HomeController::class, 'sendContactMessage'])->middleware('throttle:public-form')->name('send-contact-message');
    Route::post('/product-inquiry', [HomeController::class, 'sendProductInquiry'])->middleware('throttle:public-form')->name('product-inquiry');

    Route::get('/track-order-response/{id}', [HomeController::class, 'trackOrderResponse'])
        ->middleware('auth:api')
        ->name('track-order-response');
    Route::get('/faq', [HomeController::class, 'faq'])->name('faq');
    Route::get('/page', [HomeController::class, 'allCustomPage'])->name('custom-page');
    Route::get('/page/{slug}', [HomeController::class, 'customPage'])->name('page');
    Route::get('/terms-and-conditions', [HomeController::class, 'termsAndCondition'])->name('terms-and-conditions');
    Route::get('/privacy-policy', [HomeController::class, 'privacyPolicy'])->name('privacy-policy');
    Route::get('/seller-terms-conditoins', [HomeController::class, 'sellerTemsCondition'])->name('seller-terms-conditoins');

    Route::get('/legal-documents', [\App\Http\Controllers\API\LegalDocumentController::class, 'index'])->name('legal-documents.index');
    Route::get('/legal-documents/{slug}', [\App\Http\Controllers\API\LegalDocumentController::class, 'show'])->name('legal-documents.show');
    Route::post('/legal-consents', [\App\Http\Controllers\API\LegalDocumentController::class, 'storeConsents'])->name('legal-consents.store');
    Route::get('/user/legal-consents', [\App\Http\Controllers\API\LegalDocumentController::class, 'userConsents'])->middleware('auth:api')->name('legal-consents.user');

    Route::get('/products/sitemap', [HomeController::class, 'productSitemap'])->name('products.sitemap');
    Route::get('/products/active-count', [HomeController::class, 'productCount'])->name('products.count');
    Route::get('/second-hand/sitemap', [HomeController::class, 'secondHandSitemap'])->name('second-hand.sitemap');

    // Public satıcı vitrin API'leri kapalı — ürün odaklı sistem
    Route::get('/product', [HomeController::class, 'product'])->name('product');
    Route::get('/variant-items-by-variant/{variant_name}', [HomeController::class, 'variantItemsByVariant'])->name('variant-items-by-variant');
    Route::get('/search-product', [HomeController::class, 'searchProduct'])->name('search-product');
    Route::get('/product/{slug}', [HomeController::class, 'productDetail'])->name('api.product-detail');
    Route::get('/product-review-list/{id}', [HomeController::class, 'productReviewList'])->name('product-review-list');

    // İkinci El (Public)
    Route::get('/second-hand', [SecondHandPublicController::class, 'index'])->name('second-hand.public.index');
    Route::get('/second-hand/listings/{id}', [SecondHandPublicController::class, 'show'])->name('second-hand.public.show');
    Route::get('/second-hand/images/{imageId}', [SecondHandPublicController::class, 'image'])->name('second-hand.public.image');
    Route::get('/second-hand/agreements', [SecondHandPublicController::class, 'agreements'])->name('second-hand.public.agreements');

    // Blog API
    Route::get('/blogs', [\App\Http\Controllers\BlogController::class, 'index'])->name('blogs');
    Route::get('/blog-categories', [\App\Http\Controllers\BlogController::class, 'blogCategory'])->name('blog-categories');
    Route::get('/blog-category/{slug}', [\App\Http\Controllers\BlogController::class, 'blogCategoryDetail'])->name('blog-category-detail');
    Route::get('/blog/{slug}', [\App\Http\Controllers\BlogController::class, 'blogDetail'])->name('blog-detail');
    Route::post('/blog-comment', [\App\Http\Controllers\BlogController::class, 'blogComment'])->name('blog-comment');

    Route::get('/compare', [HomeController::class, 'compare'])->name('compare');
    Route::get('/add-to-compare/{id}', [HomeController::class, 'addToCompare'])->name('add-to-compare');
    Route::get('/remove-compare/{id}', [HomeController::class, 'removeCompare'])->name('remove-compare');
    Route::get('/flash-sale', [HomeController::class, 'flashSale'])->name('flash-sale');

    Route::post('subscribe-request', [HomeController::class, 'subscribeRequest'])->middleware('throttle:public-form', 'XSS')->name('subscribe-request');

    Route::prefix('public')->group(function () {
        Route::post('seller-register', [PublicSellerRegistrationController::class, 'store'])
            ->middleware('throttle:public-form')
            ->name('public.seller-register');
        Route::get('seller-register/states', [PublicSellerRegistrationController::class, 'turkiyeStates'])
            ->name('public.seller-register.states');
    });

    Route::get('subscriber-verification/{token}', [HomeController::class, 'subscriberVerifcation'])->name('subscriber-verification');

    // Route::get('live-track-order/{id}', [HomeController::class, 'liveTrackOrder'])->name('live-track-order');

    Route::get('live-track-order', [HomeController::class, 'liveTrackOrder'])
        ->middleware('auth:api')
        ->name('live-track-order');

    Route::get('/cart', [CartController::class, 'cart'])->name('cart');
    Route::get('/add-to-cart', [CartController::class, 'addToCart'])->name('add-to-cart');
    Route::get('/cart-clear', [CartController::class, 'cartClear'])->name('cart-clear');
    Route::get('/cart-item-remove/{id}', [CartController::class, 'cartItemRemove'])->name('cart-item-remove');
    Route::get('/cart-item-increment/{id}', [CartController::class, 'cartItemIncrement'])->name('cart-item-increment');
    Route::get('/cart-item-decrement/{id}', [CartController::class, 'cartItemDecrement'])->name('cart-item-decrement');

    Route::get('/apply-coupon', [CartController::class, 'applyCoupon'])->name('apply-coupon');
    Route::get('/calculate-product-price', [CartController::class, 'calculateProductPrice'])->name('calculate-product-price');
    Route::post('/cart/refresh-prices', [CartController::class, 'refreshPrices'])->name('cart-refresh-prices');

    Route::get('login/google',[LoginController::class, 'redirectToGoogle'])->name('login-google');
    Route::get('/callback/google',[LoginController::class,'googleCallBack'])->name('callback-google');

    Route::get('login/facebook',[LoginController::class, 'redirectToFacebook'])->name('login-facebook');
    Route::get('/callback/facebook',[LoginController::class,'facebookCallBack'])->name('callback-facebook');


    Route::get('/callback/mobile-app',[LoginController::class,'callback_mobileapp'])->name('callback-mobile-app');

    Route::get('/login', [LoginController::class, 'loginPage'])->name('login');
    Route::post('/store-login', [LoginController::class, 'storeLogin'])->middleware('throttle:auth-login')->name('store-login');
    Route::post('/resend-register-code', [RegisterController::class, 'resendRegisterCode'])->middleware('throttle:auth-otp')->name('resend-register-code');
    Route::post('/store-register', [RegisterController::class, 'storeRegister'])->middleware('throttle:auth-register')->name('store-register');
    Route::get('/user-verification/{token}', [RegisterController::class, 'userVerification'])->name('user-verification');

    Route::get('/forget-password', [LoginController::class, 'forgetPage'])->name('forget-password');
    Route::post('/send-forget-password', [LoginController::class, 'sendForgetPassword'])->middleware('throttle:password-reset')->name('send-forget-password');
    Route::get('/reset-password/{token}', [LoginController::class, 'resetPasswordPage'])->name('reset-password');
    Route::post('/store-reset-password/{token}', [LoginController::class, 'storeResetPasswordPage'])->name('store-reset-password');
    Route::get('/user/logout', [LoginController::class, 'userLogout'])->name('user.logout');

    Route::group(['as'=> 'user.', 'prefix' => 'user'],function (){
        Route::post('token/refresh', [UserTokenController::class, 'refresh'])->middleware('throttle:api')->name('token.refresh');
        Route::post('seller-sso-ticket', [UserTokenController::class, 'sellerSsoTicket'])
            ->middleware(['throttle:10,1'])
            ->name('seller-sso-ticket');

        Route::get('dashboard', [UserProfileController::class, 'dashboard'])->name('dashboard');
        Route::get('order', [UserProfileController::class, 'order'])->name('order');
        Route::get('pending-order', [UserProfileController::class, 'pendingOrder'])->name('pending-order');
        Route::get('complete-order', [UserProfileController::class, 'completeOrder'])->name('complete-order');
        Route::get('declined-order', [UserProfileController::class, 'declinedOrder'])->name('declined-order');
        Route::get('order-show/{id}', [UserProfileController::class, 'orderShow'])->name('order-show');
        Route::post('order-confirm-delivery/{id}', [UserProfileController::class, 'confirmDelivery'])->name('order-confirm-delivery');
        Route::post('order-products/{id}/confirm-delivery', [UserProfileController::class, 'confirmOrderProductDelivery'])->name('order-products.confirm-delivery');
        Route::get('review', [UserProfileController::class, 'review'])->name('review');
        Route::get('get-review/{id}', [UserProfileController::class, 'showReview'])->name('show-review');
        Route::get('my-profile', [UserProfileController::class, 'myProfile'])->name('my-profile');
        Route::post('update-profile', [UserProfileController::class, 'updateProfile'])->name('update-profile');
        Route::post('update-device-token', [UserProfileController::class, 'updateDeviceToken'])->name('update-device-token');
        Route::get('notifications', [\App\Http\Controllers\User\NotificationController::class, 'index'])->middleware('auth:api')->name('notifications.index');
        Route::put('notifications/{id}/read', [\App\Http\Controllers\User\NotificationController::class, 'markAsRead'])->middleware('auth:api')->name('notifications.read');
        Route::put('notifications/read-all', [\App\Http\Controllers\User\NotificationController::class, 'markAllAsRead'])->middleware('auth:api')->name('notifications.read-all');
        Route::post('product-view', [\App\Http\Controllers\User\ProductViewController::class, 'store'])->middleware('auth:api')->name('product-view.store');
        Route::get('address', [UserProfileController::class, 'address'])->name('address');
        Route::post('update-password', [UserProfileController::class, 'updatePassword'])->name('update-password');


        Route::resource('address', AddressCotroller::class);

        Route::get('compare-product', [UserProfileController::class, 'compareProducts'])->name('compare-product');
        Route::get('add-compare-product/{id}', [UserProfileController::class, 'addCompareProducts'])->name('add-compare-product');
        Route::delete('delete-compare-product/{id}', [UserProfileController::class, 'deleteCompareProduct'])->name('delete-compare-product');

        Route::post('seller-request', [UserProfileController::class, 'sellerRequest'])->name('seller-request');
        Route::get('wishlist', [UserProfileController::class, 'wishlist'])->name('wishlist');
        Route::get('add-to-wishlist/{id}', [UserProfileController::class, 'addToWishlist'])->name('add-to-wishlist');
        Route::get('remove-wishlist/{id}', [UserProfileController::class, 'removeWishlist'])->name('remove-wishlist');
        Route::get('clear-wishlist', [UserProfileController::class, 'clearWishlist'])->name('clear-wishlist');
        Route::post('product-report', [UserProfileController::class, 'storeProductReport'])->name('product-report');
        Route::post('store-product-review', [UserProfileController::class, 'storeProductReview'])->name('store-product-review');
        Route::post('update-review/{id}', [UserProfileController::class, 'updateReview'])->name('update-review');

        Route::delete('remove-account', [UserProfileController::class, 'remove_account'])->name('remove-account');

        Route::get('message-with-seller', [MessageController::class, 'index'])->name('message-with-seller');
        Route::post('send-message-to-seller', [MessageController::class, 'send_message_to_seller'])->name('send-message-to-seller');
        Route::get('load-active-seller-message/{id}', [MessageController::class, 'laod_active_seller_message'])->name('load-active-seller-message');

        // Salon CRM (kuaför / güzellik — alıcı hesabı)
        // Salon CRM — ayrı auth (alışveriş JWT değil)
        Route::post('salon-crm/auth/patron/register', [\App\Http\Controllers\User\SalonCrmAuthController::class, 'patronRegister']);
        Route::post('salon-crm/auth/patron/login', [\App\Http\Controllers\User\SalonCrmAuthController::class, 'patronLogin']);
        Route::middleware('auth:api')->group(function () {
            Route::post('salon-crm/auth/patron/bootstrap', [\App\Http\Controllers\User\SalonCrmAuthController::class, 'patronBootstrap']);
            Route::post('salon-crm/auth/patron/register-linked', [\App\Http\Controllers\User\SalonCrmAuthController::class, 'patronRegisterLinked']);
            Route::get('salon-crm/patron/salon', [\App\Http\Controllers\User\SalonCrmAuthController::class, 'patronSalonSummary']);
        });
        Route::post('salon-crm/auth/staff/login', [\App\Http\Controllers\User\SalonCrmAuthController::class, 'staffLogin']);
        Route::get('salon-crm/join/{code}', [\App\Http\Controllers\User\SalonCrmAuthController::class, 'customerJoinPreview']);
        Route::middleware('salon.crm:owner,staff')->group(function () {
            Route::get('salon-crm/calendar-share', [\App\Http\Controllers\User\SalonCrmCalendarController::class, 'show']);
            Route::patch('salon-crm/calendar-share', [\App\Http\Controllers\User\SalonCrmCalendarController::class, 'update']);
        });
        Route::get('salon-crm/calendar/{token}', [\App\Http\Controllers\User\SalonCrmCalendarController::class, 'publicShow'])
            ->where('token', '[A-Za-z0-9]+');
        Route::post('salon-crm/auth/customer/register', [\App\Http\Controllers\User\SalonCrmAuthController::class, 'customerRegister']);
        Route::post('salon-crm/auth/customer/login', [\App\Http\Controllers\User\SalonCrmAuthController::class, 'customerLogin']);

        Route::middleware('salon.crm:owner,staff,customer')->group(function () {
            Route::get('salon-crm/status', [\App\Http\Controllers\User\SalonCrmController::class, 'status']);
            Route::post('salon-crm/device-token', [\App\Http\Controllers\User\SalonCrmController::class, 'updateDeviceToken']);
        });

        Route::middleware('salon.crm:customer')->group(function () {
            Route::get('salon-crm/customer/catalog', [\App\Http\Controllers\User\SalonCrmController::class, 'customerCatalog']);
            Route::get('salon-crm/customer/appointments', [\App\Http\Controllers\User\SalonCrmController::class, 'customerAppointmentsIndex']);
            Route::post('salon-crm/customer/appointments', [\App\Http\Controllers\User\SalonCrmController::class, 'customerAppointmentsStore']);
        });

        Route::middleware('salon.crm:owner,staff')->group(function () {
            Route::get('salon-crm/staff/{id}', [\App\Http\Controllers\User\SalonCrmController::class, 'staffShow']);
            Route::get('salon-crm/salary-payments', [\App\Http\Controllers\User\SalonCrmController::class, 'salaryPaymentsIndex']);
            Route::patch('salon-crm/salary-payments/{id}/confirm', [\App\Http\Controllers\User\SalonCrmController::class, 'salaryPaymentsConfirm']);
            Route::get('salon-crm/services', [\App\Http\Controllers\User\SalonCrmController::class, 'servicesIndex']);
            Route::get('salon-crm/appointments', [\App\Http\Controllers\User\SalonCrmController::class, 'appointmentsIndex']);
            Route::post('salon-crm/appointments', [\App\Http\Controllers\User\SalonCrmController::class, 'appointmentsStore']);
            Route::patch('salon-crm/appointments/{id}', [\App\Http\Controllers\User\SalonCrmController::class, 'appointmentsUpdate']);
            Route::patch('salon-crm/appointments/{id}/status', [\App\Http\Controllers\User\SalonCrmController::class, 'appointmentsUpdateStatus']);
            Route::get('salon-crm/customers', [\App\Http\Controllers\User\SalonCrmController::class, 'customersIndex']);
            Route::post('salon-crm/customers', [\App\Http\Controllers\User\SalonCrmController::class, 'customersStore']);
            Route::patch('salon-crm/customers/{id}', [\App\Http\Controllers\User\SalonCrmController::class, 'customersUpdate']);
            Route::get('salon-crm/ledger', [\App\Http\Controllers\User\SalonCrmController::class, 'ledgerIndex']);
            Route::post('salon-crm/ledger', [\App\Http\Controllers\User\SalonCrmController::class, 'ledgerStore']);
            Route::get('salon-crm/performance', [\App\Http\Controllers\User\SalonCrmController::class, 'staffPerformance']);
            Route::get('salon-crm/profile', [\App\Http\Controllers\User\SalonCrmController::class, 'profileShow']);
            Route::post('salon-crm/staff/{id}/photo', [\App\Http\Controllers\User\SalonCrmController::class, 'staffPhotoUpdate']);
        });

        Route::middleware('salon.crm:owner')->group(function () {
            Route::get('salon-crm/staff', [\App\Http\Controllers\User\SalonCrmController::class, 'staffIndex']);
            Route::post('salon-crm/staff', [\App\Http\Controllers\User\SalonCrmController::class, 'staffStore']);
            Route::patch('salon-crm/staff/{id}', [\App\Http\Controllers\User\SalonCrmController::class, 'staffUpdate']);
            Route::delete('salon-crm/staff/{id}', [\App\Http\Controllers\User\SalonCrmController::class, 'staffDestroy']);
            Route::post('salon-crm/staff/{id}/hours', [\App\Http\Controllers\User\SalonCrmController::class, 'staffHoursSync']);
            Route::post('salon-crm/staff/{id}/services', [\App\Http\Controllers\User\SalonCrmController::class, 'staffServicesSync']);
            Route::post('salon-crm/salary-payments', [\App\Http\Controllers\User\SalonCrmController::class, 'salaryPaymentsStore']);
            Route::post('salon-crm/services', [\App\Http\Controllers\User\SalonCrmController::class, 'servicesStore']);
            Route::patch('salon-crm/services/{id}', [\App\Http\Controllers\User\SalonCrmController::class, 'servicesUpdate']);
            Route::post('salon-crm/register', [\App\Http\Controllers\User\SalonCrmController::class, 'register']);
            Route::post('salon-crm/profile', [\App\Http\Controllers\User\SalonCrmController::class, 'profileUpdate']);
        });

        // İkinci El (C2C) doğrulama
        Route::get('second-hand/verification', [SecondHandVerificationController::class, 'show'])->middleware('auth:api');
        Route::post('second-hand/verification', [SecondHandVerificationController::class, 'submit'])->middleware('auth:api');

        // İkinci El (C2C) ilanlar (draft → publish)
        Route::get('second-hand/listings/my', [SecondHandListingController::class, 'myListings'])->middleware('auth:api');
        Route::post('second-hand/listings', [SecondHandListingController::class, 'createDraft'])->middleware('auth:api');
        Route::put('second-hand/listings/{id}', [SecondHandListingController::class, 'updateDraft'])->middleware('auth:api');
        Route::post('second-hand/listings/{id}/publish', [SecondHandListingController::class, 'publish'])->middleware('auth:api');
        Route::post('second-hand/listings/{id}/images', [SecondHandListingController::class, 'uploadImage'])->middleware('auth:api');
        Route::delete('second-hand/listings/{listingId}/images/{imageId}', [SecondHandListingController::class, 'deleteImage'])->middleware('auth:api');
        Route::post('second-hand/listings/{id}/deactivate', [SecondHandListingController::class, 'deactivate'])->middleware('auth:api');
        Route::post('second-hand/listings/{id}/activate', [SecondHandListingController::class, 'activate'])->middleware('auth:api');
        Route::post('second-hand/listings/{id}/sold', [SecondHandListingController::class, 'markSold'])->middleware('auth:api');

        // İkinci El (C2C) mesajlaşma
        Route::get('second-hand/messages/inbox', [SecondHandMessagingController::class, 'inbox'])->middleware('auth:api');
        Route::get('second-hand/messages/conversations/{conversationId}', [SecondHandMessagingController::class, 'messages'])->middleware('auth:api');
        Route::post('second-hand/messages/conversations/{conversationId}/read', [SecondHandMessagingController::class, 'markRead'])->middleware('auth:api');
        Route::post('second-hand/messages/listings/{listingId}', [SecondHandMessagingController::class, 'sendToListing'])->middleware('auth:api');
        Route::post('second-hand/messages/conversations/{conversationId}', [SecondHandMessagingController::class, 'sendToConversation'])->middleware('auth:api');
    Route::post('second-hand/blocks', [SecondHandMessagingController::class, 'blockUser'])->middleware('auth:api');
    Route::delete('second-hand/blocks/{blockedId}', [SecondHandMessagingController::class, 'unblockUser'])->middleware('auth:api');

        // İkinci El (C2C) raporlama
        Route::post('second-hand/reports', [SecondHandReportController::class, 'store'])->middleware('auth:api');

        // AI içerik (web kullanıcı JWT) — ikinci el başlık/açıklama önerileri vb.
        Route::post('ai/generate-content', [\App\Http\Controllers\AiContentController::class, 'generate'])->middleware('auth:api');

        Route::get('chat-with-seller/{slug}', [MessageController::class, 'chatWithSeller'])->name('chat-with-seller');
        Route::get('message', [MessageController::class, 'index'])->name('message');
        Route::get('load-chat-box/{id}', [MessageController::class, 'loadChatBox'])->name('load-chat-box');
        Route::get('load-new-message/{id}', [MessageController::class, 'loadNewMessage'])->name('load-new-message');
        Route::get('send-message', [MessageController::class, 'sendMessage'])->name('send-message');

        Route::group(['as'=> 'checkout.', 'prefix' => 'checkout'],function (){
            Route::get('/', [CheckoutController::class, 'checkout'])->name('checkout');

            Route::post('/cash-on-delivery', [PaymentController::class, 'cashOnDelivery'])->name('cash-on-delivery');
            Route::post('/pay-with-bank', [PaymentController::class, 'payWithBank'])->name('pay-with-bank');

            Route::post('/store-draft-order', [PaymentController::class, 'store_draft_order'])->name('store-draft-order');

            Route::post('/pay-with-iyzico', [IyzicoController::class, 'createCheckoutSession'])->name('pay-with-iyzico');
        });

        // Return Request Routes
        Route::get('orders/{id}/returnable-items', [UserReturnRequestController::class, 'returnableItems']);
        Route::get('return-requests', [UserReturnRequestController::class, 'index']);
        Route::get('return-requests/{id}', [UserReturnRequestController::class, 'show']);
        Route::post('return-requests', [UserReturnRequestController::class, 'store']);
        Route::put('return-requests/{id}/cancel', [UserReturnRequestController::class, 'cancel']);


    });

    // Iyzico callback — auth disinda, Iyzico redirect ile gelir, rate limited
    Route::match(['get', 'post'], 'user/iyzico/callback', [IyzicoController::class, 'callback'])->middleware('throttle:10,1')->name('iyzico.callback');


    Route::group(['as'=> 'seller.', 'prefix' => 'seller','middleware' => ['checkseller']],function (){
        Route::get('dashboard',[SellerDashboardController::class,'index'])->name('dashboard');
        Route::get('my-profile',[SellerProfileController::class,'index'])->name('my-profile');
        Route::get('state-by-country/{id}',[SellerProfileController::class,'stateByCountry'])->name('state-by-country');
        Route::get('city-by-state/{id}',[SellerProfileController::class,'cityByState'])->name('city-by-state');
        Route::post('update-seller-profile',[SellerProfileController::class,'updateSellerProfile'])->name('update-seller-profile');
        Route::get('change-password',[SellerProfileController::class,'changePassword'])->name('change-password');
        Route::put('password-update',[SellerProfileController::class,'updatePassword'])->name('password-update');
        Route::get('shop-profile',[SellerProfileController::class,'myShop'])->name('shop-profile');
        Route::post('update-seller-shop',[SellerProfileController::class,'updateSellerSop'])->name('update-seller-shop');
        Route::put('remove-seller-social-link/{id}',[SellerProfileController::class,'removeSellerSocialLink'])->name('remove-seller-social-link');
        Route::get('email-history',[SellerProfileController::class,'emailHistory'])->name('email-history');

        Route::post('product/quick-create', [ApiQuickProductController::class, 'store'])->name('product.quick-create');

        Route::resource('product', SellerProductController::class);
        Route::post('update-product/{id}', [SellerProductController::class, 'update'])->name('update-product');

        Route::put('product-status/{id}', [SellerProductController::class,'changeStatus'])->name('product.status');
        Route::put('removed-product-exist-specification/{id}', [SellerProductController::class,'removedProductExistSpecification'])->name('removed-product-exist-specification');
        Route::get('pending-product', [SellerProductController::class,'pendingProduct'])->name('pending-product');
        Route::get('product-highlight/{id}', [SellerProductController::class,'productHighlight'])->name('product-highlight');
        Route::put('update-product-highlight/{id}', [SellerProductController::class,'productHighlightUpdate'])->name('update-product-highlight');


        Route::get('subcategory-by-category/{id}', [SellerProductController::class,'getSubcategoryByCategory'])->name('subcategory-by-category');
        Route::get('childcategory-by-subcategory/{id}', [SellerProductController::class,'getChildcategoryBySubCategory'])->name('childcategory-by-subcategory');


        Route::get('product-variant/{id}', [SellerProductVariantController::class,'index'])->name('product-variant');
        Route::get('create-product-variant/{id}', [SellerProductVariantController::class,'create'])->name('create-product-variant');
        Route::post('store-product-variant', [SellerProductVariantController::class,'store'])->name('store-product-variant');
        Route::get('get-product-variant/{id}', [SellerProductVariantController::class,'show'])->name('get-product-variant');
        Route::get('edit-product-variant/{id}', [SellerProductVariantController::class,'edit'])->name('edit-product-variant');
        Route::put('update-product-variant/{id}', [SellerProductVariantController::class,'update'])->name('update-product-variant');
        Route::delete('delete-product-variant/{id}', [SellerProductVariantController::class,'destroy'])->name('delete-product-variant');
        Route::put('product-variant-status/{id}', [SellerProductVariantController::class,'changeStatus'])->name('product-variant.status');

        Route::get('product-variant-item', [SellerProductVariantItemController::class,'index'])->name('product-variant-item');
        Route::get('create-product-variant-item/{id}', [SellerProductVariantItemController::class,'create'])->name('create-product-variant-item');
        Route::post('store-product-variant-item', [SellerProductVariantItemController::class,'store'])->name('store-product-variant-item');
        Route::get('edit-product-variant-item/{id}', [SellerProductVariantItemController::class,'edit'])->name('edit-product-variant-item');

        Route::get('get-product-variant-item/{id}', [SellerProductVariantItemController::class,'show'])->name('egetdit-product-variant-item');

        Route::put('update-product-variant-item/{id}', [SellerProductVariantItemController::class,'update'])->name('update-product-variant-item');
        Route::delete('delete-product-variant-item/{id}', [SellerProductVariantItemController::class,'destroy'])->name('delete-product-variant-item');
        Route::put('product-variant-item-status/{id}', [SellerProductVariantItemController::class,'changeStatus'])->name('product-variant-item.status');

        Route::get('product-gallery/{id}', [SellerProductGalleryController::class,'index'])->name('product-gallery');
        Route::post('store-product-gallery', [SellerProductGalleryController::class,'store'])->name('store-product-gallery');
        Route::delete('delete-product-image/{id}', [SellerProductGalleryController::class,'destroy'])->name('delete-product-image');
        Route::put('product-gallery-status/{id}', [SellerProductGalleryController::class,'changeStatus'])->name('product-gallery.status');


        Route::get('financial-dashboard', [SellerFinancialController::class, 'index'])->name('financial-dashboard');
        Route::get('product-review',[SellerProductReviewController::class,'index'])->name('product-review');
        Route::get('show-product-review/{id}',[SellerProductReviewController::class,'show'])->name('show-product-review');


        Route::get('product-report',[SellerProductReportControler::class, 'index'])->name('product-report');
        Route::get('show-product-report/{id}',[SellerProductReportControler::class, 'show'])->name('show-product-report');

        Route::resource('my-withdraw', WithdrawController::class);
        Route::get('get-withdraw-account-info/{id}', [WithdrawController::class, 'getWithDrawAccountInfo'])->name('get-withdraw-account-info');
        Route::get('earnings', [SellerEarningsController::class, 'summary'])->name('earnings');
        Route::get('earnings/orders', [SellerEarningsController::class, 'orders'])->name('earnings-orders');

        Route::get('all-order', [SellerOrderController::class, 'index'])->name('all-order');
        Route::get('pending-order', [SellerOrderController::class, 'pendingOrder'])->name('pending-order');
        Route::get('pregress-order', [SellerOrderController::class, 'pregressOrder'])->name('pregress-order');
        Route::get('delivered-order', [SellerOrderController::class, 'deliveredOrder'])->name('delivered-order');
        Route::get('completed-order', [SellerOrderController::class, 'completedOrder'])->name('completed-order');
        Route::get('declined-order', [SellerOrderController::class, 'declinedOrder'])->name('declined-order');
        Route::get('cash-on-delivery', [SellerOrderController::class, 'cashOnDelivery'])->name('cash-on-delivery');
        Route::get('order-show/{id}', [SellerOrderController::class, 'show'])->name('order-show');
        Route::put('update-order-status/{id}', [SellerOrderController::class, 'updateOrderStatus'])->name('seller-update-order-status');
        Route::post('update-order-status/{id}', [SellerOrderController::class, 'updateOrderStatus']);
        Route::post('manual-ship/{id}', [SellerOrderController::class, 'manualShip'])->name('seller-manual-ship');

        Route::get('message', [SellerMessageContoller::class, 'index'])->name('message');
        Route::get('load-chat-box/{id}', [SellerMessageContoller::class, 'loadChatBox'])->name('load-chat-box');
        Route::get('load-new-message/{id}', [SellerMessageContoller::class, 'loadNewMessage'])->name('load-new-message');
        Route::get('send-message', [SellerMessageContoller::class, 'sendMessage'])->name('send-message');

        // Seller → Admin contact (web contact-admin ile aynı ContactMessage kaydı)
        Route::get('contact-admin', [SellerContactAdminController::class, 'index']);
        Route::post('contact-admin', [SellerContactAdminController::class, 'store']);

        // Bulk Product Import
        Route::post('products/bulk-import', [\App\Http\Controllers\Seller\SellerBulkImportController::class, 'upload']);
        Route::get('products/bulk-imports', [\App\Http\Controllers\Seller\SellerBulkImportController::class, 'index']);
        Route::get('products/bulk-import/template', [\App\Http\Controllers\Seller\SellerBulkImportController::class, 'template']);
        Route::get('products/bulk-import/sample', [\App\Http\Controllers\Seller\SellerBulkImportController::class, 'sample']);
        Route::get('products/bulk-import/{id}', [\App\Http\Controllers\Seller\SellerBulkImportController::class, 'show']);

        // Return Request Routes
        Route::get('return-requests', [SellerReturnRequestController::class, 'index']);
        Route::get('return-requests/{id}', [SellerReturnRequestController::class, 'show']);
        Route::put('return-requests/{id}/approve', [SellerReturnRequestController::class, 'approve']);
        Route::put('return-requests/{id}/reject', [SellerReturnRequestController::class, 'reject']);
        Route::put('return-requests/{id}/update-status', [SellerReturnRequestController::class, 'updateStatus']);

        // Seller KYC
        Route::post('kyc/upload', [\App\Http\Controllers\Seller\SellerKycController::class, 'upload']);
        Route::get('kyc/documents', [\App\Http\Controllers\Seller\SellerKycController::class, 'documents']);
        Route::delete('kyc/documents/{id}', [\App\Http\Controllers\Seller\SellerKycController::class, 'destroy']);
        Route::get('kyc/status', [\App\Http\Controllers\Seller\SellerKycController::class, 'status']);
        Route::post('kyc/update-info', [\App\Http\Controllers\Seller\SellerKycController::class, 'updateInfo']);

        // Inventory / stock
        Route::get('inventory', [ApiSellerInventoryController::class, 'index']);
        Route::get('stockout-products', [ApiSellerInventoryController::class, 'stockout']);
        Route::get('stock-history/{productId}', [ApiSellerInventoryController::class, 'history']);
        Route::post('add-stock', [ApiSellerInventoryController::class, 'addStock']);
        Route::delete('delete-stock/{id}', [ApiSellerInventoryController::class, 'deleteStock']);

        // Brands
        Route::get('brands', [ApiSellerBrandController::class, 'index']);
        Route::post('brands', [ApiSellerBrandController::class, 'store']);
        Route::post('brands/{id}', [ApiSellerBrandController::class, 'update']);
        Route::delete('brands/{id}', [ApiSellerBrandController::class, 'destroy']);

        // FAQ & Guide
        Route::get('faq', [SellerFaqController::class, 'index']);
        Route::get('guide', [SellerGuideController::class, 'index']);

        // Stock Alerts
        Route::get('products/low-stock', [\App\Http\Controllers\Seller\StockAlertController::class, 'lowStockProducts']);

        // Notifications
        Route::get('notifications', [\App\Http\Controllers\Seller\NotificationController::class, 'index']);
        Route::put('notifications/{id}/read', [\App\Http\Controllers\Seller\NotificationController::class, 'markAsRead']);
        Route::put('notifications/read-all', [\App\Http\Controllers\Seller\NotificationController::class, 'markAllAsRead']);

        // AI Content Generation (seller)
        Route::post('ai/generate-content', [\App\Http\Controllers\AiContentController::class, 'generate']);
        Route::post('ai-assistant/chat', [ApiSellerAiAssistantController::class, 'chat']);

    });

    //delivery man routes — modül kapalı (FEATURE_DELIVERYMAN=false)
    Route::middleware('deliveryman.enabled')->group(function () {
    Route::post('deliveryman/registration', [DeliveryManRegistrationController::class,'registration'])->name('delivery.man.registration');
    Route::post('deliveryman/login', [DeliveryManLoginController::class,'dashboardLogin'])->name('delivery.man.api.login');
    Route::get('deliveryman/logout',[DeliveryManLoginController::class,'logout'])->name('deliveryman.api.logout');

    Route::post('deliveryman/password/reset/email',[DeliveryManResetPasswordController::class,'passwrodResetEmail'])->name('deliveryman.api.password.reset.email');
    Route::put('deliveryman/password/update',[DeliveryManResetPasswordController::class,'passwrodUpdate'])->name('deliveryman.api.pasword.update');

    Route::group(['as'=> 'deliveryman.', 'prefix' => 'deliveryman', 'middleware'=>'deliverymanapi'],function (){
        Route::get('dashboard',[DeliveryManDashboardController::class,'index'])->name('dashboard');
        Route::get('my-profile',[DeliveryManProfileController::class,'index'])->name('my-profile');
        Route::get('edit-profile',[DeliveryManProfileController::class,'edit'])->name('edit-profile');
        Route::post('update-profile',[DeliveryManProfileController::class,'update'])->name('update-profile');
        Route::put('update-password',[DeliveryManProfileController::class,'updatePassword'])->name('update-password');
        Route::post('lat-long-update',[DeliveryManProfileController::class,'UpdateDeliveryManLatlong'])->name('lat-long-update');
        Route::get('orders',[DeliveryManOrderController::class,'index'])->name('orders');
        Route::get('order-request',[DeliveryManOrderController::class,'orderRequest'])->name('order-request');
        Route::put('order-request-status/{id}',[DeliveryManOrderController::class,'orderRequestStatus'])->name('order-request-status');

        Route::get('completed-order',[DeliveryManOrderController::class,'completedOrder'])->name('completed-order');
        Route::get('order-show/{id}',[DeliveryManOrderController::class,'show'])->name('order-show');

        Route::put('update-order-status/{id}',[DeliveryManOrderController::class,'updateOrderStatus'])->name('update-order-status');

        Route::resource('withdraw', MyWithdrawController::class);

        Route::get('get-withdraw-account-info/{id}', [MyWithdrawController::class, 'getWithDrawAccountInfo'])->name('get-withdraw-account-info');

        Route::get('my-review', [MyReviewController::class, 'index'])->name('my-review');

        Route::get('message-with-customer/{order_id}', [DeliveryMessageController::class, 'message_with_customer'])->name('message-with-customer');
        Route::get('get-message-with-customer/{order_id}', [DeliveryMessageController::class, 'get_message_with_customer'])->name('get-message-with-customer');
        Route::post('sent-message-to-customer', [DeliveryMessageController::class, 'sent_message_to_customer'])->name('sent-message-to-customer');

        Route::get('logout',[DeliveryManLoginController::class,'logout'])->name('logout');
      });

    }); // deliveryman.enabled

}); // Route::group line 142

// start admin routes - route names removed to prevent conflicts with web.php
Route::group(['prefix' => 'admin'],function (){

    // start auth route
    Route::get('login', [AdminLoginController::class,'adminLoginPage']);
    Route::post('login', [AdminLoginController::class,'storeLogin'])->middleware('throttle:auth-login');
    Route::get('forget-password', [AdminForgotPasswordController::class,'forgetPassword']);
    Route::post('send-forget-password', [AdminForgotPasswordController::class,'sendForgetEmail'])->middleware('throttle:password-reset');
    Route::get('reset-password/{token}', [AdminForgotPasswordController::class,'resetPassword']);
    Route::post('password-store/{token}', [AdminForgotPasswordController::class,'storeResetData']);
    // end auth route

    Route::group(['middleware' => ['auth:admin-api']], function () {
    Route::post('logout', [AdminLoginController::class,'adminLogout']);
    Route::get('/', [DashboardController::class,'dashobard']);
    Route::get('dashboard', [DashboardController::class,'dashobard']);
    Route::get('profile', [AdminProfileController::class,'index']);
    Route::put('profile-update', [AdminProfileController::class,'update']);

    Route::resource('product-category', ProductCategoryController::class);
    Route::put('product-category-status/{id}', [ProductCategoryController::class,'changeStatus']);

    Route::resource('product-sub-category', ProductSubCategoryController::class);
    Route::put('product-sub-category-status/{id}', [ProductSubCategoryController::class,'changeStatus']);

    Route::resource('product-child-category', ProductChildCategoryController::class);
    Route::put('product-child-category-status/{id}', [ProductChildCategoryController::class,'changeStatus']);
    Route::get('subcategory-by-category/{id}', [ProductChildCategoryController::class,'getSubcategoryByCategory']);
    Route::get('childcategory-by-subcategory/{id}', [ProductChildCategoryController::class,'getChildcategoryBySubCategory']);

    Route::resource('product-brand', ProductBrandController::class);
    Route::put('product-brand-status/{id}', [ProductBrandController::class,'changeStatus']);

    Route::resource('specification-key', SpecificationKeyController::class);
    Route::put('specification-key-status/{id}', [SpecificationKeyController::class,'changeStatus']);

    Route::resource('testimonial', TestimonialController::class);
    Route::put('testimonial-status/{id}', [TestimonialController::class,'changeStatus']);

    Route::resource('product', ProductController::class);
    Route::get('create-product-info', [ProductController::class,'create']);
    Route::put('product-status/{id}', [ProductController::class,'changeStatus']);
    Route::put('removed-product-exist-specification/{id}', [ProductController::class,'removedProductExistSpecification']);
    Route::get('seller-product', [ProductController::class,'sellerProduct']);
    Route::get('seller-pending-product', [ProductController::class,'sellerPendingProduct']);
    Route::get('product-highlight/{id}', [ProductController::class,'productHighlight']);
    Route::put('update-product-highlight/{id}', [ProductController::class,'productHighlightUpdate']);



    Route::get('product-variant/{id}', [ProductVariantController::class,'index']);
    Route::get('create-product-variant/{id}', [ProductVariantController::class,'create']);
    Route::post('store-product-variant', [ProductVariantController::class,'store']);
    Route::get('get-product-variant/{id}', [ProductVariantController::class,'show']);
    Route::put('update-product-variant/{id}', [ProductVariantController::class,'update']);
    Route::delete('delete-product-variant/{id}', [ProductVariantController::class,'destroy']);
    Route::put('product-variant-status/{id}', [ProductVariantController::class,'changeStatus']);

    Route::get('product-variant-item', [ProductVariantItemController::class,'index']);
    Route::get('create-product-variant-item/{id}', [ProductVariantItemController::class,'create']);
    Route::post('store-product-variant-item', [ProductVariantItemController::class,'store']);
    Route::get('edit-product-variant-item/{id}', [ProductVariantItemController::class,'edit']);
    Route::get('get-product-variant-item/{id}', [ProductVariantItemController::class,'show']);

    Route::put('update-product-variant-item/{id}', [ProductVariantItemController::class,'update']);
    Route::delete('delete-product-variant-item/{id}', [ProductVariantItemController::class,'destroy']);
    Route::put('product-variant-item-status/{id}', [ProductVariantItemController::class,'changeStatus']);


    Route::get('product-gallery/{id}', [ProductGalleryController::class,'index']);
    Route::post('store-product-gallery', [ProductGalleryController::class,'store']);
    Route::delete('delete-product-image/{id}', [ProductGalleryController::class,'destroy']);
    Route::put('product-gallery-status/{id}', [ProductGalleryController::class,'changeStatus']);

    Route::resource('service', ServiceController::class);
    Route::put('service-status/{id}', [ServiceController::class,'changeStatus']);

    Route::resource('about-us', AboutUsController::class);
    Route::resource('contact-us', ContactPageController::class);

    Route::resource('custom-page', CustomPageController::class);
    Route::put('custom-page-status/{id}', [CustomPageController::class,'changeStatus']);

    Route::resource('terms-and-condition', TermsAndConditionController::class);
    Route::resource('privacy-policy', PrivacyPolicyController::class);

    Route::get('subscriber',[SubscriberController::class,'index']);
    Route::delete('delete-subscriber/{id}',[SubscriberController::class,'destroy']);
    Route::post('specification-subscriber-email/{id}',[SubscriberController::class,'specificationSubscriberEmail']);
    Route::post('each-subscriber-email',[SubscriberController::class,'eachSubscriberEmail']);

    Route::get('contact-message',[ContactMessageController::class,'index']);
    Route::delete('delete-contact-message/{id}',[ContactMessageController::class,'destroy']);
    Route::put('enable-save-contact-message',[ContactMessageController::class,'handleSaveContactMessage']);

    Route::get('email-configuration',[EmailConfigurationController::class,'index']);
    Route::put('update-email-configuraion',[EmailConfigurationController::class,'update']);

    Route::get('email-template',[EmailTemplateController::class,'index']);
    Route::get('edit-email-template/{id}',[EmailTemplateController::class,'edit']);
    Route::put('update-email-template/{id}',[EmailTemplateController::class,'update']);

    Route::get('general-setting',[SettingController::class,'index']);
    Route::put('update-general-setting',[SettingController::class,'updateGeneralSetting']);

    Route::put('update-theme-color',[SettingController::class,'updateThemeColor']);

    Route::put('update-logo-favicon',[SettingController::class,'updateLogoFavicon']);
    Route::put('update-cookie-consent',[SettingController::class,'updateCookieConset']);
    Route::put('update-google-recaptcha',[SettingController::class,'updateGoogleRecaptcha']);
    Route::put('update-facebook-comment',[SettingController::class,'updateFacebookComment']);
    Route::put('update-tawk-chat',[SettingController::class,'updateTawkChat']);
    Route::put('update-google-analytic',[SettingController::class,'updateGoogleAnalytic']);
    Route::put('update-custom-pagination',[SettingController::class,'updateCustomPagination']);
    Route::put('update-social-login',[SettingController::class,'updateSocialLogin']);
    Route::put('update-facebook-pixel',[SettingController::class,'updateFacebookPixel']);
    Route::put('update-pusher',[SettingController::class,'updatePusher']);


    Route::resource('admin', AdminController::class);
    Route::put('admin-status/{id}', [AdminController::class,'changeStatus']);

    Route::resource('faq', FaqController::class);
    Route::put('faq-status/{id}', [FaqController::class,'changeStatus']);


    Route::get('product-review',[ProductReviewController::class,'index']);
    Route::put('product-review-status/{id}',[ProductReviewController::class,'changeStatus']);
    Route::get('show-product-review/{id}',[ProductReviewController::class,'show']);
    Route::delete('delete-product-review/{id}',[ProductReviewController::class,'destroy']);

    Route::get('product-report',[ProductReportController::class, 'index']);
    Route::get('show-product-report/{id}',[ProductReportController::class, 'show']);
    Route::delete('delete-product-report/{id}',[ProductReportController::class, 'destroy']);
    Route::put('de-active-product/{id}',[ProductReportController::class, 'deactiveProduct']);

    Route::get('customer-list',[CustomerController::class,'index']);
    Route::get('customer-show/{id}',[CustomerController::class,'show']);
    Route::put('customer-status/{id}',[CustomerController::class,'changeStatus']);
    Route::delete('customer-delete/{id}',[CustomerController::class,'destroy']);
    Route::get('pending-customer-list',[CustomerController::class,'pendingCustomerList']);
    Route::get('send-email-to-all-customer',[CustomerController::class,'sendEmailToAllUser']);
    Route::post('send-mail-to-all-user',[CustomerController::class,'sendMailToAllUser']);
    Route::post('send-mail-to-single-user/{id}',[CustomerController::class,'sendMailToSingleUser']);


    Route::get('seller-list',[SellerController::class,'index']);
    Route::get('seller-show/{id}',[SellerController::class,'show']);
    Route::put('seller-status/{id}',[SellerController::class,'changeStatus']);
    Route::delete('seller-delete/{id}',[SellerController::class,'destroy']);
    Route::get('pending-seller-list',[SellerController::class,'pendingSellerList']);
    Route::put('seller-update/{id}',[SellerController::class,'updateSeller']);
    Route::get('seller-shop-detail/{id}',[SellerController::class,'sellerShopDetail']);
    Route::put('remove-seller-social-link/{id}',[SellerController::class,'removeSellerSocialLink']);


    Route::put('update-seller-shop/{id}',[SellerController::class,'updateSellerSop']);
    Route::get('seller-reviews/{id}',[SellerController::class,'sellerReview']);
    Route::get('show-seller-review-details/{id}',[SellerController::class,'showSellerReviewDetails']);
    Route::get('send-email-to-seller/{id}',[SellerController::class,'sendEmailToSeller']);
    Route::post('send-mail-to-single-seller/{id}',[SellerController::class,'sendMailtoSingleSeller']);
    Route::get('email-history/{id}',[SellerController::class,'emailHistory']);
    Route::get('product-by-seller/{id}',[SellerController::class,'productBySaller']);
    Route::get('send-email-to-all-seller',[SellerController::class,'sendEmailToAllSeller']);
    Route::post('send-mail-to-all-seller',[SellerController::class,'sendMailToAllSeller']);
    Route::get('withdraw-list/{id}',[SellerController::class,'sellerWithdrawList']);


    Route::get('state-by-country/{id}',[SellerController::class,'stateByCountry']);
    Route::get('city-by-state/{id}',[SellerController::class,'cityByState']);

    Route::resource('error-page', ErrorPageController::class);

    Route::get('maintainance-mode',[ContentController::class,'maintainanceMode']);
    Route::put('maintainance-mode-update',[ContentController::class,'maintainanceModeUpdate']);

    Route::get('announcement',[ContentController::class,'announcementModal']);
    Route::post('announcement-update',[ContentController::class,'announcementModalUpdate']);

    Route::get('topbar-contact', [ContentController::class, 'headerPhoneNumber']);
    Route::put('update-topbar-contact', [ContentController::class, 'updateHeaderPhoneNumber']);

    Route::get('product-quantity-progressbar', [ContentController::class, 'productProgressbar']);
    Route::put('update-product-quantity-progressbar', [ContentController::class, 'updateProductProgressbar']);

    Route::get('default-avatar', [ContentController::class, 'defaultAvatar']);
    Route::post('update-default-avatar', [ContentController::class, 'updateDefaultAvatar']);

    Route::get('seller-conditions', [ContentController::class, 'sellerCondition']);
    Route::put('update-seller-conditions', [ContentController::class, 'updatesellerCondition']);

    Route::get('subscription-banner', [ContentController::class, 'subscriptionBanner']);
    Route::post('update-subscription-banner', [ContentController::class, 'updatesubscriptionBanner']);




    Route::get('flash-sale', [FlashSaleController::class, 'index']);
    Route::put('update-flash-sale', [FlashSaleController::class, 'update']);
    Route::get('flash-sale-product', [FlashSaleController::class, 'flash_sale_product']);
    Route::post('store-flash-sale-product', [FlashSaleController::class, 'store']);
    Route::put('flash-sale-product-status/{id}', [FlashSaleController::class, 'changeStatus']);
    Route::delete('delete-flash-sale-product/{id}', [FlashSaleController::class,'destroy']);


    Route::get('advertisement',[AdvertisementController::class, 'index']);

    Route::post('mega-menu-banner-update', [AdvertisementController::class, 'megaMenuBannerUpdate']);


    Route::post('slider-banner-one', [AdvertisementController::class, 'updateSliderBannerOne']);

    Route::post('slider-banner-two', [AdvertisementController::class, 'updateSliderBannerTwo']);

    Route::post('popular-category-sidebar', [AdvertisementController::class, 'updatePopularCategorySidebar']);


    Route::post('homepage-two-col-first-banner', [AdvertisementController::class, 'homepageTwoColFirstBanner']);


    Route::post('homepage-two-col-second-banner', [AdvertisementController::class, 'homepageTwoColSecondBanner']);


    Route::post('homepage-single-first-banner', [AdvertisementController::class, 'homepageSinleFirstBanner']);

    Route::post('homepage-single-second-banner', [AdvertisementController::class, 'homepageSinleSecondBanner']);


    Route::post('homepage-flash-sale-sidebar-banner', [AdvertisementController::class, 'homepageFlashSaleSidebarBanner']);


    Route::post('shop-page-center-banner', [AdvertisementController::class, 'shopPageCenterBanner']);

    Route::post('shop-page-sidebar-banner', [AdvertisementController::class, 'shopPageSidebarBanner']);

    Route::get('login-page', [ContentController::class, 'loginPage']);
    Route::post('update-login-page', [ContentController::class, 'updateloginPage']);

    Route::get('shop-page',[ContentController::Class, 'shopPage']);
    Route::put('update-filter-price',[ContentController::Class, 'updateFilterPrice']);

    Route::get('seo-setup',[ContentController::Class, 'seoSetup']);
    Route::put('update-seo-setup/{id}',[ContentController::Class, 'updateSeoSetup']);
    Route::get('get-seo-setup/{id}',[ContentController::Class, 'getSeoSetup']);



    Route::resource('country', CountryController::class);
    Route::put('country-status/{id}',[CountryController::class,'changeStatus']);

    Route::resource('state', CountryStateController::class);
    Route::put('state-status/{id}',[CountryStateController::class,'changeStatus']);

    Route::resource('city', CityController::class);
    Route::put('city-status/{id}',[CityController::class,'changeStatus']);

    Route::get('payment-method',[PaymentMethodController::class,'index']);
    Route::put('update-bank',[PaymentMethodController::class,'updateBank']);
    Route::put('update-cash-on-delivery',[PaymentMethodController::class,'updateCashOnDelivery']);
    Route::put('update-iyzico',[PaymentMethodController::class,'updateIyzico']);

    Route::resource('mega-menu-category', MegaMenuController::class);
    Route::put('mega-menu-category-status/{id}',[MegaMenuController::class,'changeStatus']);

    Route::get('mega-menu-sub-category/{id}', [MegaMenuSubCategoryController::class, 'index']);
    Route::get('create-mega-menu-sub-category/{id}', [MegaMenuSubCategoryController::class, 'create']);
    Route::get('get-mega-menu-sub-category/{id}', [MegaMenuSubCategoryController::class, 'show']);
    Route::post('store-mega-menu-sub-category/{id}', [MegaMenuSubCategoryController::class, 'store']);
    Route::get('edit-mega-menu-sub-category/{id}', [MegaMenuSubCategoryController::class, 'edit']);
    Route::put('update-mega-menu-sub-category/{id}', [MegaMenuSubCategoryController::class, 'update']);
    Route::delete('delete-mega-menu-sub-category/{id}', [MegaMenuSubCategoryController::class, 'destroy']);
    Route::put('mega-menu-sub-category-status/{id}',[MegaMenuSubCategoryController::class,'changeStatus']);


    Route::resource('slider', SliderController::class);
    Route::put('slider-status/{id}',[SliderController::class,'changeStatus']);


    Route::get('popular-category', [HomePageController::class, 'popularCategory']);
    Route::post('store-popular-category', [HomePageController::class, 'storePopularCategory']);
    Route::delete('destroy-popular-category/{id}', [HomePageController::class, 'destroyPopularCategory']);

    Route::get('featured-category', [HomePageController::class, 'featuredCategory']);
    Route::post('store-featured-category', [HomePageController::class, 'storeFeaturedCategory']);
    Route::delete('destroy-featured-category/{id}', [HomePageController::class, 'destroyFeaturedCategory']);



    Route::get('homepage-visibility', [HomepageVisibilityController::class, 'index']);
    Route::put('update-homepage-visibility', [HomepageVisibilityController::class, 'update']);

    Route::get('menu-visibility', [MenuVisibilityController::class, 'index']);
    Route::put('update-menu-visibility/{id}', [MenuVisibilityController::class, 'update']);

    Route::resource('shipping', ShippingMethodController::class);
    Route::get('city-wise-shipping/{city_id}', [ShippingMethodController::class , 'cityWiseShipping']);

    Route::resource('withdraw-method', WithdrawMethodController::class);
    Route::put('withdraw-method-status/{id}',[WithdrawMethodController::class,'changeStatus']);

    Route::get('seller-withdraw', [SellerWithdrawController::class, 'index']);
    Route::get('pending-seller-withdraw', [SellerWithdrawController::class, 'pendingSellerWithdraw']);

    Route::get('show-seller-withdraw/{id}', [SellerWithdrawController::class, 'show']);
    Route::delete('delete-seller-withdraw/{id}', [SellerWithdrawController::class, 'destroy']);
    Route::put('approved-seller-withdraw/{id}', [SellerWithdrawController::class, 'approvedWithdraw']);

    Route::get('commission/settings', [AdminCommissionController::class, 'settings']);
    Route::put('commission/settings', [AdminCommissionController::class, 'updateSettings']);
    Route::get('commission/vendors', [AdminCommissionController::class, 'vendors']);
    Route::put('commission/vendors/{id}', [AdminCommissionController::class, 'updateVendorRate']);
    Route::delete('commission/vendors/{id}', [AdminCommissionController::class, 'resetVendorRate']);
    Route::get('commission/report', [AdminCommissionController::class, 'report']);
    Route::get('commission/ledger', [AdminCommissionController::class, 'ledger']);

    Route::get('all-order', [OrderController::class, 'index']);
    Route::get('pending-order', [OrderController::class, 'pendingOrder']);
    Route::get('pregress-order', [OrderController::class, 'pregressOrder']);
    Route::get('delivered-order', [OrderController::class, 'deliveredOrder']);
    Route::get('completed-order', [OrderController::class, 'completedOrder']);
    Route::get('declined-order', [OrderController::class, 'declinedOrder']);
    Route::get('cash-on-delivery', [OrderController::class, 'cashOnDelivery']);
    // Bank Transfer Routes
    Route::get('bank-transfer-pending', [OrderController::class, 'bankTransferPending']);
    Route::put('approve-payment/{id}', [OrderController::class, 'approvePayment']);
    
    Route::get('order-show/{id}', [OrderController::class, 'show']);
    Route::delete('delete-order/{id}', [OrderController::class, 'destroy']);
    Route::put('update-order-status/{id}', [OrderController::class, 'updateOrderStatus']);

    // Return Request Routes
    Route::get('return-requests/stats', [AdminReturnRequestController::class, 'stats']);
    Route::get('return-requests', [AdminReturnRequestController::class, 'index']);
    Route::get('return-requests/{id}', [AdminReturnRequestController::class, 'show']);
    Route::put('return-requests/{id}/approve', [AdminReturnRequestController::class, 'approve']);
    Route::put('return-requests/{id}/reject', [AdminReturnRequestController::class, 'reject']);
    Route::put('return-requests/{id}/mark-received', [AdminReturnRequestController::class, 'markReceived']);
    Route::put('return-requests/{id}/refund', [AdminReturnRequestController::class, 'refund']);
    Route::put('return-requests/{id}/update-status', [AdminReturnRequestController::class, 'updateStatus']);

    // Seller KYC
    Route::get('kyc/pending', [\App\Http\Controllers\Admin\SellerKycController::class, 'pending']);
    Route::get('kyc/seller/{id}', [\App\Http\Controllers\Admin\SellerKycController::class, 'seller']);
    Route::put('kyc/{id}/approve', [\App\Http\Controllers\Admin\SellerKycController::class, 'approve']);
    Route::put('kyc/{id}/reject', [\App\Http\Controllers\Admin\SellerKycController::class, 'reject']);
    Route::post('kyc/seller/{id}/create-sub-merchant', [\App\Http\Controllers\Admin\SellerKycController::class, 'createSubMerchant']);

    // Stock Alerts
    Route::get('stock-alerts/settings', [\App\Http\Controllers\Admin\StockAlertController::class, 'settings']);
    Route::put('stock-alerts/settings', [\App\Http\Controllers\Admin\StockAlertController::class, 'updateSettings']);

    // Seller Payout Routes
    Route::post('payout/{orderId}/process', [\App\Http\Controllers\Admin\OrderController::class, 'processSellerPayout']);
    Route::post('payout/{orderId}/block', [\App\Http\Controllers\Admin\OrderController::class, 'blockPayout']);
    Route::post('payout/{orderId}/unblock', [\App\Http\Controllers\Admin\OrderController::class, 'unblockPayout']);
    Route::post('payout/{orderId}/hold', [\App\Http\Controllers\Admin\OrderController::class, 'holdPayout']);
    Route::post('payout/{orderId}/hold/clear', [\App\Http\Controllers\Admin\OrderController::class, 'clearHoldPayout']);
    Route::get('products/low-stock', [\App\Http\Controllers\Admin\StockAlertController::class, 'lowStockProducts']);

    // Bulk Product Import
    Route::post('products/bulk-import', [\App\Http\Controllers\Admin\ProductBulkImportController::class, 'upload']);
    Route::get('products/bulk-imports', [\App\Http\Controllers\Admin\ProductBulkImportController::class, 'index']);

    // AI Settings
    Route::get('ai-settings', [\App\Http\Controllers\Admin\AiSettingsController::class, 'show']);
    Route::put('ai-settings', [\App\Http\Controllers\Admin\AiSettingsController::class, 'update']);

    // AI Content Generation (admin)
    Route::post('ai/generate-content', [\App\Http\Controllers\AiContentController::class, 'generate']);

    Route::resource('coupon', CouponController::class);
    Route::put('coupon-status/{id}',[CouponController::class,'changeStatus']);

    Route::resource('banner-image', BreadcrumbController::class);

    Route::resource('footer', FooterController::class);
    Route::resource('social-link', FooterSocialLinkController::class);
    Route::resource('footer-link', FooterLinkController::class);
    Route::get('second-col-footer-link', [FooterLinkController::class, 'secondColFooterLink']);
    Route::get('third-col-footer-link', [FooterLinkController::class, 'thirdColFooterLink']);
    Route::put('update-col-title/{id}', [FooterLinkController::class, 'updateColTitle']);


    Route::get('admin-language', [LanguageController::class, 'adminLnagugae']);
    Route::post('update-admin-language', [LanguageController::class, 'updateAdminLanguage']);

    Route::get('admin-validation-language', [LanguageController::class, 'adminValidationLnagugae']);
    Route::post('update-admin-validation-language', [LanguageController::class, 'updateAdminValidationLnagugae']);


    Route::get('website-language', [LanguageController::class, 'websiteLanguage']);
    Route::post('update-language', [LanguageController::class, 'updateLanguage']);

    Route::get('website-validation-language', [LanguageController::class, 'websiteValidationLanguage']);
    Route::post('update-validation-language', [LanguageController::class, 'updateValidationLanguage']);


});
    });

});


Route::group(['as'=> 'user.', 'prefix' => 'user'],function (){
    Route::get('country-list', [CountryGetController::class, 'countryList'])->name('country-list');
    Route::get('state-by-country/{id}', [CountryGetController::class, 'stateByCountry'])->name('state-by-country');
    Route::get('city-by-state/{id}', [CountryGetController::class, 'cityByState'])->name('city-by-state');
});

Route::group(['as'=> 'user.', 'prefix' => 'user'],function (){
    Route::group(['as'=> 'checkout.guest', 'prefix' => 'checkout/guest', 'middleware' => 'guest.checkout'],function (){
        Route::get('/without-token', [CheckoutWithoutTokenController::class, 'checkout'])->name('without-token');
        Route::post('/cash-on-delivery', [CheckoutWithoutTokenController::class, 'cashOnDelivery'])->name('cash-on-delivery');
        Route::post('/store-draft-order', [CheckoutWithoutTokenController::class, 'store_draft_order'])->name('store-draft-order');
        Route::post('/pay-with-bank', [CheckoutWithoutTokenController::class, 'payWithBank'])->name('pay-with-bank');
        Route::post('/pay-with-iyzico', [IyzicoController::class, 'createGuestCheckoutSession'])->name('pay-with-iyzico');
    });
});

// AI Chat Routes
Route::prefix('ai-chat')->group(function () {
    Route::get('/status', [\App\Http\Controllers\API\AiChatController::class, 'status']);
    Route::post('/guest/send', [\App\Http\Controllers\API\AiChatController::class, 'guestSend'])
        ->middleware('throttle:5,1');
});

Route::group(['middleware' => ['auth:api'], 'prefix' => 'user/ai-chat'], function () {
    Route::post('/send', [\App\Http\Controllers\API\AiChatController::class, 'send'])
        ->middleware('throttle:10,1');
    Route::get('/history', [\App\Http\Controllers\API\AiChatController::class, 'history']);
});
Route::post('webhooks/geliver', [GeliverWebhookController::class, 'handle'])
    ->middleware('geliver.enabled');
