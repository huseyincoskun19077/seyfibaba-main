import appConfig from ".";

// api constants for api route strings
const ABOUT_US = "about-us";
const CONTACT_US = "contact-us";
const SUBSCRIBE_REQUEST = "subscribe-request";
const SEND_CONTACT_MESSAGE = "send-contact-message";
const PRODUCT_INQUIRY = "product-inquiry";
const STORE_REGISTER = "store-register";
const STORE_LOGIN = "store-login";
const USER_VERIFICATION = "user-verification";
const RESEND_REGISTER_CODE = "resend-register-code";
const SEND_FORGET_PASSWORD = "send-forget-password";
const UPDATE_PASSWORD = "user/update-password";
const STORE_RESET_PASSWORD = "store-reset-password";
const USER_DASHBOARD = "user/dashboard";
const USER_MY_PROFILE = "user/my-profile";
const WISHLISTS = "user/wishlist";
const ADD_TO_WISHLIST = "user/add-to-wishlist";
const REMOVE_FROM_WISHLIST = "user/remove-wishlist";
const CLEAR_WISHLIST = "user/clear-wishlist";
const ADD_TO_CART = "add-to-cart";
const REMOVE_CART_ITEM = "cart-item-remove";
const CLEAR_CART = "cart-clear";
const INCREMENT_QUANTITY = "cart-item-increment";
const DECREMENT_QUANTITY = "cart-item-decrement";
const CART_REFRESH_PRICES = "cart/refresh-prices";
const PRODUCTS = "product";
const SEARCH_PRODUCT = "search-product";
const REPORT_PRODUCT = "user/product-report";
const APPLY_COUPON = "apply-coupon";
const PRODUCT_REVIEW = "user/store-product-review";
const GET_REVIEW = "user/review";
const DELETE_USER = "user/remove-account";

const MESSAGE_WITH_SELLER = "user/message-with-seller";
const SEND_MESSAGE_TO_SELLER = "user/send-message-to-seller";
const LOAD_ACTIVE_SELLER_MESSAGE = "user/load-active-seller-message";
const ADDRESS = "user/address";
const GET_GUEST_CHECKOUT_DATA = "user/checkout/guest/without-token";
const GET_CHECKOUT_DATA = "user/checkout";
const CASH_ON_DELIVERY = "user/checkout/cash-on-delivery";
const CASH_ON_DELIVERY_GUEST = "user/checkout/guest/cash-on-delivery";
const BANK_PAYMENT = "user/checkout/pay-with-bank";
const BANK_PAYMENT_GUEST = "user/checkout/guest/pay-with-bank";
const DRAFT_ORDER = "user/checkout/store-draft-order";
const DRAFT_ORDER_GUEST = "user/checkout/guest/store-draft-order";
const ORDERS = "user/order";
const ORDER_DETAILS = "user/order-show";
const ORDER_CONFIRM_DELIVERY = "user/order-confirm-delivery";
const ORDER_TRACK = "track-order-response";
const COMPARE = "user/compare-product";
const ADD_PRODUCT_FOR_COMPARE = "user/add-compare-product";
const REMOVE_COMPARE_ITEM = "user/delete-compare-product";
const WEBSITE_SETUP = "website-setup";
const LOGOUT = "user/logout";
const FAQ = "faq";
const FLASH_SALE = "flash-sale";
const SELLER_TERMS_CONDITION = "seller-terms-conditoins";
const PRIVACY_POLICY = "privacy-policy";
const SELLERS = "sellers";
const TERMS_CONDITION = "terms-and-conditions";
const LEGAL_DOCUMENTS = "legal-documents";
const LEGAL_CONSENTS = "legal-consents";
const GOOGLE_LOGIN = "login/google";
const FACEBOOK_LOGIN = "login/facebook";
const GET_COUNTRY_LIST = "user/address/create";
const GET_COUNTRY_LIST_GUEST = "user/country-list";
const GET_STATE_LIST = "user/state-by-country";
const GET_CITY_LIST = "user/city-by-state";
const UPDATE_PROFILE = "user/update-profile";
const LIVE_TRACK_ORDER = "live-track-order";
const SELLER_REQUEST = "user/seller-request";
const GOOGLE_CALLBACK = "callback/google";
const FACEBOOK_CALLBACK = "callback/facebook";
const TWITTER_CALLBACK = "callback/twitter";

/**
 * Function to combine the base url with the api url
 * @param {string} url
 * @returns {string}
 */
function serverApiOrigin() {
  const fromEnv = String(process.env.NEXT_SERVER_BASE_URL || "").trim();
  if (fromEnv) {
    return fromEnv.replace(/\/+$/, "").replace(/\/api$/i, "") + "/";
  }
  try {
    const host = new URL(appConfig.BASE_URL).hostname.replace(/^www\./, "");
    if (host.endsWith("seyfibaba.com")) {
      return "https://admin.seyfibaba.com/";
    }
  } catch {
    /* use public */
  }
  return appConfig.BASE_URL;
}

const combinedUrl = (url) => {
  const origin =
    typeof window === "undefined" ? serverApiOrigin() : appConfig.BASE_URL;
  return origin.replace(/\/+$/, "") + "/api/" + url;
};

// api routes
const apiRoutes = {
  // shopo
  shopo: combinedUrl(""),
  about: combinedUrl(ABOUT_US),

  // Website Setup
  websiteSetup: combinedUrl(WEBSITE_SETUP),

  // Authentication
  signup: combinedUrl(STORE_REGISTER),
  login: combinedUrl(STORE_LOGIN),
  logout: combinedUrl(LOGOUT),
  verification: combinedUrl(USER_VERIFICATION),
  resendCode: combinedUrl(RESEND_REGISTER_CODE),
  forgotPassword: combinedUrl(SEND_FORGET_PASSWORD),
  updatePassword: combinedUrl(UPDATE_PASSWORD),
  resetPassword: combinedUrl(STORE_RESET_PASSWORD),

  sendOtp: combinedUrl("auth/otp/send"),
  verifyOtp: combinedUrl("auth/otp/verify"),
  resendOtp: combinedUrl("auth/otp/resend"),

  // User Dashboard
  dashboard: combinedUrl(USER_DASHBOARD),
  profileInfo: combinedUrl(USER_MY_PROFILE),
  updateProfile: combinedUrl(UPDATE_PROFILE),
  userNotifications: combinedUrl("user/notifications"),

  // Wishlist
  wishlists: combinedUrl(WISHLISTS),
  addToWishlist: combinedUrl(ADD_TO_WISHLIST),
  removeFromWishlist: combinedUrl(REMOVE_FROM_WISHLIST),
  clearWishlist: combinedUrl(CLEAR_WISHLIST),

  // Contact
  contact: combinedUrl(SEND_CONTACT_MESSAGE),
  productInquiry: combinedUrl(PRODUCT_INQUIRY),
  contactUs: combinedUrl(CONTACT_US),
  subscribeRequest: combinedUrl(SUBSCRIBE_REQUEST),
  faq: combinedUrl(FAQ),

  // Cart
  addToCart: combinedUrl(ADD_TO_CART),
  removeCartItem: combinedUrl(REMOVE_CART_ITEM),
  clearCart: combinedUrl(CLEAR_CART),
  incrementQuantity: combinedUrl(INCREMENT_QUANTITY),
  decrementQuantity: combinedUrl(DECREMENT_QUANTITY),
  cartRefreshPrices: combinedUrl(CART_REFRESH_PRICES),

  // Address
  address: combinedUrl(ADDRESS),

  // location
  getCountryList: combinedUrl(GET_COUNTRY_LIST),
  getCountryListGuest: combinedUrl(GET_COUNTRY_LIST_GUEST),
  getStateList: combinedUrl(GET_STATE_LIST),
  getCityList: combinedUrl(GET_CITY_LIST),

  // Checkout
  getGuestCheckoutData: combinedUrl(GET_GUEST_CHECKOUT_DATA),
  getCheckoutData: combinedUrl(GET_CHECKOUT_DATA),
  cashOnDelivery: combinedUrl(CASH_ON_DELIVERY),
  cashOnDeliveryGuest: combinedUrl(CASH_ON_DELIVERY_GUEST),
  bankPayment: combinedUrl(BANK_PAYMENT),
  bankPaymentGuest: combinedUrl(BANK_PAYMENT_GUEST),
  draftOrder: combinedUrl(DRAFT_ORDER),
  draftOrderGuest: combinedUrl(DRAFT_ORDER_GUEST),

  iyzicoCheckout: combinedUrl("user/checkout/pay-with-iyzico"),
  iyzicoCheckoutGuest: combinedUrl("user/checkout/guest/pay-with-iyzico"),
  // Orders
  orders: combinedUrl(ORDERS),
  returnRequests: combinedUrl("user/return-requests"),
  orderTrack: combinedUrl(ORDER_TRACK),
  orderDetails: combinedUrl(ORDER_DETAILS),
  orderConfirmDelivery: combinedUrl(ORDER_CONFIRM_DELIVERY),
  liveTrackOrder: combinedUrl(LIVE_TRACK_ORDER),

  // Compare
  compare: combinedUrl(COMPARE),
  addProductForCompare: combinedUrl(ADD_PRODUCT_FOR_COMPARE),
  removeCompareItem: combinedUrl(REMOVE_COMPARE_ITEM),

  // Product
  products: combinedUrl(PRODUCTS),
  reportProduct: combinedUrl(REPORT_PRODUCT),
  applyCoupon: combinedUrl(APPLY_COUPON),
  searchProduct: combinedUrl(SEARCH_PRODUCT),

  // Reviews
  productReview: combinedUrl(PRODUCT_REVIEW),
  getReview: combinedUrl(GET_REVIEW),


  // User Account
  deleteUser: combinedUrl(DELETE_USER),

  // message with seller
  messageWithSeller: combinedUrl(MESSAGE_WITH_SELLER),
  sendMessageToSeller: combinedUrl(SEND_MESSAGE_TO_SELLER),
  loadActiveSellerMessage: combinedUrl(LOAD_ACTIVE_SELLER_MESSAGE),

  // flash sale
  flashSale: combinedUrl(FLASH_SALE),

  // privacy policy
  privacyPolicy: combinedUrl(PRIVACY_POLICY),

  // seller terms condition
  sellerTermsCondition: combinedUrl(SELLER_TERMS_CONDITION),

  // sellers
  sellers: combinedUrl(SELLERS),

  sellerSsoTicket: combinedUrl("user/seller-sso-ticket"),

  // terms condition
  termsCondition: combinedUrl(TERMS_CONDITION),

  // legal documents
  legalDocuments: combinedUrl(LEGAL_DOCUMENTS),
  legalDocumentShow: combinedUrl(LEGAL_DOCUMENTS),
  legalConsents: combinedUrl(LEGAL_CONSENTS),

  // social
  googleGetLoginUrl: combinedUrl(GOOGLE_LOGIN),
  facebookGetLoginUrl: combinedUrl(FACEBOOK_LOGIN),

  // seller request
  sellerRequest: combinedUrl(SELLER_REQUEST),

  // seller tools
  sellerKycUpload: combinedUrl("seller/kyc/upload"),
  sellerKycDocuments: combinedUrl("seller/kyc/documents"),
  sellerKycStatus: combinedUrl("seller/kyc/status"),
  sellerBulkImportUpload: combinedUrl("seller/products/bulk-import"),
  sellerBulkImports: combinedUrl("seller/products/bulk-imports"),
  sellerBulkImportTemplate: combinedUrl("seller/products/bulk-import/template"),
  sellerLowStockProducts: combinedUrl("seller/products/low-stock"),
  sellerNotifications: combinedUrl("seller/notifications"),
  sellerUpdateOrderStatus: combinedUrl("seller/update-order-status"),

  // callback
  googleCallback: combinedUrl(GOOGLE_CALLBACK),
  facebookCallback: combinedUrl(FACEBOOK_CALLBACK),
  twitterCallback: combinedUrl(TWITTER_CALLBACK),

  // Blog
  blogs: combinedUrl("blogs"),
  blogDetail: combinedUrl("blog"),
  blogCategories: combinedUrl("blog-categories"),

  // Sitemaps
  productSitemap: combinedUrl("products/sitemap"),
  productCount: combinedUrl("products/active-count"),

  // Salon CRM — public calendar
  salonCrmCalendar: combinedUrl("user/salon-crm/calendar/"),
  secondHandListings: combinedUrl("second-hand"),
  secondHandListingShow: combinedUrl("second-hand/listings/"),
  secondHandListingImage: combinedUrl("second-hand/images/"),
  secondHandAgreements: combinedUrl("second-hand/agreements"),

  // İkinci el (C2C) — kullanıcı (JWT)
  secondHandUserVerification: combinedUrl("user/second-hand/verification"),
  secondHandUserListings: combinedUrl("user/second-hand/listings"),
  secondHandUserListingsMy: combinedUrl("user/second-hand/listings/my"),
  secondHandUserMessagesInbox: combinedUrl("user/second-hand/messages/inbox"),
  secondHandUserMessagesConversations: combinedUrl("user/second-hand/messages/conversations/"),
  secondHandUserMessagesListings: combinedUrl("user/second-hand/messages/listings/"),
  secondHandUserReports: combinedUrl("user/second-hand/reports"),
  secondHandUserBlocks: combinedUrl("user/second-hand/blocks"),

  /** Genel kategori listesi (ikinci el filtre vb.) */
  categoryList: combinedUrl("category-list"),
  subcategoryByCategory: combinedUrl("subcategory-by-category/"),
  childcategoryBySubcategory: combinedUrl("childcategory-by-subcategory/"),
  secondHandSitemap: combinedUrl("second-hand/sitemap"),

  publicSellerRegister: combinedUrl("public/seller-register"),
  publicSellerRegisterStates: combinedUrl("public/seller-register/states"),
};

export default apiRoutes;
