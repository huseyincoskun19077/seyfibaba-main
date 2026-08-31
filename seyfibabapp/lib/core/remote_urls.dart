class RemoteUrls {
   static const String rootUrl = "https://admin.seyfibaba.com/";
   //static const String rootUrl = "https://shopo.mamunuiux.com/";
   //static const String rootUrl = "https://workzone.minionionbd.com/";
  // static const String rootUrl = "https://cnth.store/admin/";
 // static const String rootUrl = "https://mamunuiux.com/shopo_laravel/";  // live url

  static const String baseUrl = '${rootUrl}api/';
  static const String homeUrl = baseUrl;
  static const String userRegister = '${baseUrl}store-register';
  static const String userLogin = '${baseUrl}store-login';
  static const String sendOtp = '${baseUrl}auth/otp/send';
  static const String verifyOtp = '${baseUrl}auth/otp/verify';
  static const String resendOtp = '${baseUrl}auth/otp/resend';

  static String socialSignUrl(String userInfo) =>
      '${baseUrl}callback/mobile-app?$userInfo';

  static String updateUserForPushNotification(String token) =>
      '${baseUrl}user/update-device-token?token=$token';

  static const String userNotifications = '${baseUrl}user/notifications';
  static String userNotificationRead(String id) =>
      '${baseUrl}user/notifications/$id/read';
  static const String userNotificationsReadAll =
      '${baseUrl}user/notifications/read-all';
  static const String userProductView = '${baseUrl}user/product-view';

  static const String createDeliveryMan = '${baseUrl}deliveryman/registration';

  // static String createDeliveryMan(String orderId, String token) =>
  //     '${baseUrl}live-track-order?order_id=$orderId&token=$token';

  static String userLogOut(String token) =>
      '${baseUrl}user/logout?token=$token';
  static const String sendForgetPassword = '${baseUrl}send-forget-password';
  static const String resendRegisterCode = '${baseUrl}resend-register-code';

  static String storeResetPassword(String code) =>
      '${baseUrl}store-reset-password/${Uri.encodeComponent(code)}';

  static String userVerification(String code) =>
      '${baseUrl}user-verification/$code';

  static const String tokenRefresh = '${baseUrl}user/token/refresh';

  static String userProfile(String token) =>
      '${baseUrl}user/my-profile?token=$token';

  static String updateProfile(String token) =>
      '${baseUrl}user/update-profile?token=$token';

  static String updateBuyerPersonalization(String token) =>
      '${baseUrl}user/update-buyer-personalization?token=$token';

  static String skipBuyerPersonalization(String token) =>
      '${baseUrl}user/skip-buyer-personalization?token=$token';

  static String becomeSellerRequest(String token) =>
      '${baseUrl}user/seller-request?token=$token';

  // Seller panel (JWT Bearer — checkseller)
  static const String sellerDashboard = '${baseUrl}seller/dashboard';
  static const String sellerProducts = '${baseUrl}seller/product';
  static String sellerProductsLight({
    required int page,
    int perPage = 20,
    String q = '',
    String filter = 'all',
  }) {
    final params = <String, String>{
      'light': '1',
      'page': '$page',
      'per_page': '$perPage',
      'filter': filter,
    };
    final query = q.trim();
    if (query.isNotEmpty) params['q'] = query;
    return Uri.parse('${baseUrl}seller/product')
        .replace(queryParameters: params)
        .toString();
  }
  static const String sellerProductCreateMeta = '${baseUrl}seller/product/create';
  static const String sellerProductQuickCreate =
      '${baseUrl}seller/product/quick-create';
  static String sellerProductShow(int id) => '${baseUrl}seller/product/$id';
  static String sellerProductEdit(int id) =>
      '${baseUrl}seller/product/$id/edit';
  static String sellerUpdateProduct(int id) =>
      '${baseUrl}seller/update-product/$id';
  static String sellerProductStatus(int id) =>
      '${baseUrl}seller/product-status/$id';
  static String sellerSubcategories(int categoryId) =>
      '${baseUrl}seller/subcategory-by-category/$categoryId';
  static String sellerChildCategories(int subCategoryId) =>
      '${baseUrl}seller/childcategory-by-subcategory/$subCategoryId';
  static const String sellerOrders = '${baseUrl}seller/all-order';
  static const String sellerPendingOrders = '${baseUrl}seller/pending-order';
  static const String sellerProgressOrders = '${baseUrl}seller/pregress-order';
  static const String sellerDeliveredOrders = '${baseUrl}seller/delivered-order';
  static const String sellerCompletedOrders = '${baseUrl}seller/completed-order';
  static const String sellerDeclinedOrders = '${baseUrl}seller/declined-order';
  static String sellerOrderShow(int id) => '${baseUrl}seller/order-show/$id';
  static String sellerUpdateOrderStatus(int id) =>
      '${baseUrl}seller/update-order-status/$id';
  static String sellerManualShip(int id) => '${baseUrl}seller/manual-ship/$id';
  static const String sellerEarnings = '${baseUrl}seller/earnings';
  static const String sellerEarningsOrders = '${baseUrl}seller/earnings/orders';
  static const String sellerWithdraws = '${baseUrl}seller/my-withdraw';
  static const String sellerWithdrawCreateMeta = '${baseUrl}seller/my-withdraw/create';
  static String sellerWithdrawAccountInfo(int methodId) =>
      '${baseUrl}seller/get-withdraw-account-info/$methodId';
  static const String sellerReturnRequests = '${baseUrl}seller/return-requests';
  static String sellerReturnRequestShow(int id) =>
      '${baseUrl}seller/return-requests/$id';
  static String sellerReturnRequestApprove(int id) =>
      '${baseUrl}seller/return-requests/$id/approve';
  static String sellerReturnRequestReject(int id) =>
      '${baseUrl}seller/return-requests/$id/reject';
  static const String sellerBulkImports = '${baseUrl}seller/products/bulk-imports';
  static const String sellerBulkImportUpload =
      '${baseUrl}seller/products/bulk-import';
  static const String sellerBulkImportTemplate =
      '${baseUrl}seller/products/bulk-import/template';
  static const String sellerBulkImportSample =
      '${baseUrl}seller/products/bulk-import/sample';
  static String sellerBulkImportShow(int id) =>
      '${baseUrl}seller/products/bulk-import/$id';
  static const String sellerAiGenerateContent =
      '${baseUrl}seller/ai/generate-content';
  static const String sellerKycStatus = '${baseUrl}seller/kyc/status';
  static const String sellerKycDocuments = '${baseUrl}seller/kyc/documents';
  static const String sellerKycUpload = '${baseUrl}seller/kyc/upload';
  static const String sellerKycUpdateInfo = '${baseUrl}seller/kyc/update-info';
  static String sellerKycDelete(int id) =>
      '${baseUrl}seller/kyc/documents/$id';
  static const String sellerNotifications = '${baseUrl}seller/notifications';
  static String sellerNotificationRead(String id) =>
      '${baseUrl}seller/notifications/$id/read';
  static const String sellerNotificationsReadAll =
      '${baseUrl}seller/notifications/read-all';
  static const String sellerInventory = '${baseUrl}seller/inventory';
  static const String sellerStockoutProducts =
      '${baseUrl}seller/stockout-products';
  static const String sellerLowStock = '${baseUrl}seller/products/low-stock';
  static String sellerStockHistory(int productId) =>
      '${baseUrl}seller/stock-history/$productId';
  static const String sellerAddStock = '${baseUrl}seller/add-stock';
  static String sellerDeleteStock(int id) =>
      '${baseUrl}seller/delete-stock/$id';
  static const String sellerBrands = '${baseUrl}seller/brands';
  static String sellerBrandUpdate(int id) => '${baseUrl}seller/brands/$id';
  static String sellerBrandDelete(int id) => '${baseUrl}seller/brands/$id';
  static const String sellerProductReviews = '${baseUrl}seller/product-review';
  static String sellerProductReviewShow(int id) =>
      '${baseUrl}seller/show-product-review/$id';
  static const String sellerFaq = '${baseUrl}seller/faq';
  static const String sellerGuide = '${baseUrl}seller/guide';
  static const String sellerAiAssistantChat =
      '${baseUrl}seller/ai-assistant/chat';
  static const String sellerContactAdmin = '${baseUrl}seller/contact-admin';
  static const String sellerShopProfile = '${baseUrl}seller/shop-profile';
  static const String sellerUpdateShop = '${baseUrl}seller/update-seller-shop';
  static String sellerProductGallery(int productId) =>
      '${baseUrl}seller/product-gallery/$productId';
  static const String sellerStoreProductGallery =
      '${baseUrl}seller/store-product-gallery';
  static String sellerDeleteProductImage(int id) =>
      '${baseUrl}seller/delete-product-image/$id';
  static String sellerProductVariants(int productId) =>
      '${baseUrl}seller/product-variant/$productId';
  static const String sellerStoreProductVariant =
      '${baseUrl}seller/store-product-variant';
  static String sellerUpdateProductVariant(int id) =>
      '${baseUrl}seller/update-product-variant/$id';
  static String sellerDeleteProductVariant(int id) =>
      '${baseUrl}seller/delete-product-variant/$id';
  static const String sellerStoreProductVariantItem =
      '${baseUrl}seller/store-product-variant-item';
  static String sellerDeleteProductVariantItem(int id) =>
      '${baseUrl}seller/delete-product-variant-item/$id';
  static String sellerProductVariantItems({
    required int productId,
    required int variantId,
  }) =>
      '${baseUrl}seller/product-variant-item?product_id=$productId&variant_id=$variantId';

  static String changePassword(String token) =>
      '${baseUrl}user/update-password?token=$token';

  // static String countryListUrl(String token) =>
  //     '${baseUrl}user/address/create?token=$token';
  static String countryListUrl(String token) =>
      '${baseUrl}user/country-list?token=$token';

  static String editAddress(String id, String token) =>
      '${baseUrl}user/address/$id/edit?token=$token';

  static String stateByCountryId(String countryId, String token) =>
      '${baseUrl}user/state-by-country/$countryId?token=$token';

  static String sellerDetailsUrl(String slug) => '${baseUrl}sellers/$slug';

  static String citiesByStateId(String stateId, String token) =>
      '${baseUrl}user/city-by-state/$stateId?token=$token';

  static String orderList(String page, String token) =>
      '${baseUrl}user/order?token=$token&page=$page';

  static String deleteUserAccount(String token) =>
      '${baseUrl}user/remove-account?token=$token';

  static String allChartUrl(String token) =>
      '${baseUrl}user/message-with-seller?token=$token';

  static String sendMsgToSeller(String token) =>
      '${baseUrl}user/send-message-to-seller?token=$token';

  static String showOrderTracking(String trackNumber, String token) =>
      '${baseUrl}user/order-show/$trackNumber?token=$token';

  static String confirmOrderProductDelivery(String orderProductId, String token) =>
      '${baseUrl}user/order-products/$orderProductId/confirm-delivery?token=$token';

  static String trackingOrderResponse(String trackNumber) =>
      '${baseUrl}track-order-response/$trackNumber';

  static String deliveryLocation(String orderId, String token) =>
      '${baseUrl}live-track-order?order_id=$orderId&token=$token';

  static const String aboutUs = '${baseUrl}about-us';
  static const String faq = '${baseUrl}faq';
  static const String termsAndConditions = '${baseUrl}terms-and-conditions';
  static const String privacyPolicy = '${baseUrl}privacy-policy';
  static const String legalDocuments = '${baseUrl}legal-documents';
  static const String contactUs = '${baseUrl}contact-us';
  static const String sendContactMessage = '${baseUrl}send-contact-message';
  static const String productInquiry = '${baseUrl}product-inquiry';
  static const String websiteSetup = '${baseUrl}website-setup?lang_code=tr';

  static String productDetail(String slug) => '${baseUrl}product/$slug';

  static String address(String token) => '${baseUrl}user/address?token=$token';

  static String singleAddress(String id, String token) =>
      '${baseUrl}user/address/$id?token=$token';

  static String deleteAddress(String id, String token) =>
      '${baseUrl}user/address/$id?token=$token';

  static String billingAddress(String token) =>
      '${baseUrl}user/update-billing-address?token=$token';

  static String updateAddress(String id, String token) =>
      '${baseUrl}user/address/$id?token=$token';

  static String addAddressUrl(String token) =>
      '${baseUrl}user/address?token=$token';

  static String shippingAddress(String token) =>
      '${baseUrl}user/update-shipping-address?token=$token';

  static String wishList(String token) =>
      '${baseUrl}user/wishlist?token=$token';

  static String removeWish(String id, String token) =>
      '${baseUrl}user/remove-wishlist/$id?token=$token';

  static String clearWishList(String token) =>
      '${baseUrl}user/clear-wishlist?token=$token';

  static String addWish(String id, String token) =>
      '${baseUrl}user/add-to-wishlist/$id?token=$token';
  static const String searchProduct = '${baseUrl}product?';

  static String cartProduct(String token) => "${baseUrl}cart?token=$token";

  static String submitReviewUrl(String token) =>
      '${baseUrl}user/store-product-review?token=$token';

  static String cartCheckout(String token, String coupon) =>
      "${baseUrl}user/checkout?token=$token&coupon=$coupon";

  static String guestCheckout(String vendorId) => "${baseUrl}user/checkout/guest/without-token?vendor_id=$vendorId";

  static String incrementQuantity(String id, String token) =>
      "${baseUrl}cart-item-increment/$id?token=$token";

  static String decrementQuantity(String id, String token) =>
      "${baseUrl}cart-item-decrement/$id?token=$token";

  static String applyCoupon(String coupon, String token) =>
      "${baseUrl}apply-coupon?coupon=$coupon&token=$token";

  static String removeCartItem(String id, String token) =>

      "${baseUrl}cart-item-remove/$id?token=$token";
  static const String addToCart = '${baseUrl}add-to-cart?';
  static const String filterUrl = '${baseUrl}search-product?';
  static const String flashSaleUrl = '${baseUrl}flash-sale';

  // static String cashOnDelivery(String token) =>
  //     '${baseUrl}user/checkout/cash-on-delivery?token=$token';

  static String draftCheckout(String type){
    if(type == 'guest'){
      return '${baseUrl}user/checkout/guest/store-draft-order';
    }else{
      return '${baseUrl}user/checkout/store-draft-order';
    }
  }

  static String cashOnDelivery(String type){
    if(type == 'guest'){
      return  '${baseUrl}user/checkout/guest/cash-on-delivery';
    }else{
      return '${baseUrl}user/checkout/cash-on-delivery';
    }
  }

  static String payWithBankUrl(String token){
    if(token == 'guest'){
      return '${baseUrl}user/checkout/guest/pay-with-bank';
    }else{
      return '${baseUrl}user/checkout/pay-with-bank';
    }
  }

  static String payWithIyzicoUrl(String token) {
    if (token == 'guest') {
      return '${baseUrl}user/checkout/guest/pay-with-iyzico';
    }
    return '${baseUrl}user/checkout/pay-with-iyzico';
  }

  static const String salonCrmStatus = '${baseUrl}user/salon-crm/status';
  static const String salonCrmDeviceToken =
      '${baseUrl}user/salon-crm/device-token';
  static const String salonCrmRegister = '${baseUrl}user/salon-crm/register';
  static const String salonCrmStaff = '${baseUrl}user/salon-crm/staff';
  static String salonCrmStaffUpdate(int id) =>
      '${baseUrl}user/salon-crm/staff/$id';
  static String salonCrmStaffShow(int id) =>
      '${baseUrl}user/salon-crm/staff/$id';
  static String salonCrmStaffHours(int id) =>
      '${baseUrl}user/salon-crm/staff/$id/hours';
  static String salonCrmStaffServices(int id) =>
      '${baseUrl}user/salon-crm/staff/$id/services';
  static const String salonCrmSalaryPayments =
      '${baseUrl}user/salon-crm/salary-payments';
  static String salonCrmSalaryPaymentConfirm(int id) =>
      '${baseUrl}user/salon-crm/salary-payments/$id/confirm';
  static const String salonCrmServices = '${baseUrl}user/salon-crm/services';
  static String salonCrmServiceUpdate(int id) =>
      '${baseUrl}user/salon-crm/services/$id';
  static const String salonCrmAppointments = '${baseUrl}user/salon-crm/appointments';
  static String salonCrmAppointmentStatus(int id) =>
      '${baseUrl}user/salon-crm/appointments/$id/status';
  static String salonCrmAppointmentUpdate(int id) =>
      '${baseUrl}user/salon-crm/appointments/$id';
  static const String salonCrmCustomers = '${baseUrl}user/salon-crm/customers';
  static String salonCrmCustomerUpdate(int id) =>
      '${baseUrl}user/salon-crm/customers/$id';
  static const String salonCrmLedger = '${baseUrl}user/salon-crm/ledger';
  static const String salonCrmPerformance =
      '${baseUrl}user/salon-crm/performance';
  static const String salonCrmPatronRegister =
      '${baseUrl}user/salon-crm/auth/patron/register';
  static const String salonCrmPatronLogin =
      '${baseUrl}user/salon-crm/auth/patron/login';
  static const String salonCrmPatronBootstrap =
      '${baseUrl}user/salon-crm/auth/patron/bootstrap';
  static const String salonCrmPatronRegisterLinked =
      '${baseUrl}user/salon-crm/auth/patron/register-linked';
  static const String salonCrmPatronSalon =
      '${baseUrl}user/salon-crm/patron/salon';
  static const String salonCrmStaffLogin =
      '${baseUrl}user/salon-crm/auth/staff/login';
  static const String salonCrmCustomerRegister =
      '${baseUrl}user/salon-crm/auth/customer/register';
  static const String salonCrmCustomerLogin =
      '${baseUrl}user/salon-crm/auth/customer/login';
  static String salonCrmJoinPreview(String code) =>
      '${baseUrl}user/salon-crm/join/$code';
  static const String salonCrmCustomerCatalog =
      '${baseUrl}user/salon-crm/customer/catalog';
  static const String salonCrmCustomerAppointments =
      '${baseUrl}user/salon-crm/customer/appointments';
  static const String salonCrmProfile = '${baseUrl}user/salon-crm/profile';
  static const String salonCrmCalendarShare =
      '${baseUrl}user/salon-crm/calendar-share';
  static String salonCrmStaffPhoto(int id) =>
      '${baseUrl}user/salon-crm/staff/$id/photo';

  static const String secondHandListings = '${baseUrl}second-hand';
  static String secondHandListingShow(int id) =>
      '${baseUrl}second-hand/listings/$id';
  static String secondHandListingImage(int imageId) =>
      '${baseUrl}second-hand/images/$imageId';
  static const String secondHandAgreements = '${baseUrl}second-hand/agreements';

  static const String secondHandUserVerification =
      '${baseUrl}user/second-hand/verification';
  static const String secondHandUserListings =
      '${baseUrl}user/second-hand/listings';
  static const String secondHandUserListingsMy =
      '${baseUrl}user/second-hand/listings/my';
  static const String secondHandUserMessagesInbox =
      '${baseUrl}user/second-hand/messages/inbox';
  static const String secondHandUserMessagesConversations =
      '${baseUrl}user/second-hand/messages/conversations/';
  static const String secondHandUserMessagesListings =
      '${baseUrl}user/second-hand/messages/listings/';

  static const String categoryLists = '${baseUrl}category-list';
  static const String brandList = '${baseUrl}brand-list';

  static String subCategoryLists(String categoryId) =>
      '${baseUrl}subcategory-by-category/$categoryId';

  static String childCategoryLists(String subCategoryId) =>
      '${baseUrl}childcategory-by-subcategory/$subCategoryId';

  // static String highlightProductsUrl(String keyword) =>
  //     '${baseUrl}search-product?highlight=$keyword&page=1&per_page=10';
  static String getProductByKeyword(String page, String keyword) =>
      '${baseUrl}product?keyword=$keyword&page=$page';

  static String loadMoreProducts(String keyword, int page, int perPage) =>
      '${baseUrl}search-product?highlight=$keyword&page=$page&per_page=$perPage';

  static String searchHighlightProducts({
    required String highlight,
    required String page,
    String? categorySlug,
    String? subCategorySlug,
    int perPage = 12,
  }) {
    final params = <String, String>{
      'highlight': highlight,
      'page': page,
      'per_page': '$perPage',
    };
    if (categorySlug != null && categorySlug.isNotEmpty) {
      params['category'] = categorySlug;
    }
    if (subCategorySlug != null && subCategorySlug.isNotEmpty) {
      params['sub_category'] = subCategorySlug;
    }
    return Uri.parse('${baseUrl}search-product')
        .replace(queryParameters: params)
        .toString();
  }

  static String categoryProducts(String slug, int page) =>
      '${baseUrl}product?category=$slug&page=$page';

  static String brandProducts(String slug) => '${baseUrl}product?brand=$slug';

  static String subCategoryProducts(String slug, int page) =>
      '${baseUrl}product?sub_category=$slug&page=$page';

  static String childCategoryProducts(String slug, int page) =>
      '${baseUrl}product?child_category=$slug&page=$page';

  static String filterProductsUrl(String slug) =>
      '${baseUrl}product?sub_category=$slug';

  static String productShareUrl(String slug) =>
      'https://seyfibaba.com/product/$slug';

  static String imageUrl(String imageUrl) {
    final raw = imageUrl.trim();
    if (raw.isEmpty) {
      return '';
    }

    if (raw.startsWith('http://') || raw.startsWith('https://')) {
      return raw;
    }

    if (raw.startsWith('//')) {
      return 'https:$raw';
    }

    final normalized = raw.startsWith('/') ? raw.substring(1) : raw;
    return rootUrl + normalized;
  }

  static videoUrl(String videoUrl) => '${rootUrl}upload/$videoUrl';

  static String mapCoordinate(
      double sLat, double sLang, double dLat, double dLang,String key) =>
      'https://maps.googleapis.com/maps/api/directions/json?origin=$sLat,$sLang&destination=$dLat,$dLang&key=$key';
}
