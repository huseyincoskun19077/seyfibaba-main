import '../core/data/datasources/remote_data_source.dart';

String _s(String key, String fallback) => myMap[key]?.toString() ?? fallback;

class Language {
  static String get enabledLocation => _s('Enabled_Location', 'Konum Etkin');
  static String get onBoardingTitle1 => _s('Choose_Product', 'Ürün Seç');
  static String get onBoardingTitle2 => _s('Make_Your_Payment', 'Ödeme Yap');
  static String get onBoardingTitle3 => _s('Fast_Delivery', 'Hızlı Teslimat');
  static String get onBoardingSubTitle => _s('on_boarding_subtitle', 'Seyfibaba Pazaryeri');
  static String get next => _s('next', 'Sonraki');
  static String get ecoShop => _s('ecoshop', 'Seyfibaba');
  static String get ecoShopSubTitle => _s('Buy_groceries_and_feed_yourself', 'Türkiye\'nin Kuaför Marketi');
  static String get dismiss => _s('Dismiss', 'Kapat');
  static String get developedBy => _s('Developed_By', 'Geliştiren');
  static String get version => _s('Version', 'Sürüm');
  static String get confirmation => _s('Confirmation', 'Onay');
  static String get wishToDelete => _s('you_wish_to_delete_this_address', 'Bu adresi silmek istiyor musunuz?');
  static String get delete => _s('delete', 'Sil');
  static String get zipCode => _s('ZipCode', 'Posta Kodu');
  static String get enterCode => _s('Enter_Code', 'Kodu Girin');
  static String get resend => _s('Resend', 'Tekrar Gönder');
  static String get verificationCode => _s('Verification_Code', 'Doğrulama Kodu');
  static String get dontReceivedCode => _s('I_dont_received_a_code', 'Kod gelmedi mi?');
  static String get fieldRequired => _s('field_required', 'Bu alan zorunlu');
  static String get enterValid => _s('Enter_valid', 'Geçerli değer girin');
  static String get enterValidEmail => _s('Enter_valid_email', 'Geçerli e-posta girin');
  static String get noCategory => _s('No_Category', 'Kategori Yok');
  static String get allSeller => _s('All_Seller', 'Tüm Satıcılar');
  static String get newArrival => _s('New_Arrival', 'Yeni Ürünler');
  static String get bestSelling => _s('Best_Selling', 'Çok Satanlar');
  static String get discountProduct => _s('Discount_Products', 'İndirimli Ürünler');
  static String get highestPrice => _s('Highest_Price', 'En Yüksek Fiyat');
  static String get lowPrice => _s('Low_Price', 'En Düşük Fiyat');
  static String get freeDelivery => _s('Free_Delivery', 'Ücretsiz Teslimat');
  static String get california => _s('California', 'California');
  static String get victoria => _s('Victoria', 'Victoria');
  static String get toronto => _s('Toronto', 'Toronto');
  static String get emailOrPhone => _s('Email_or_Phone', 'E-posta veya Telefon');
  static String get welcomeToProfile => _s('Welcome_to_your_Profile', 'Profilinize Hoş Geldiniz');
  static String get createAccount => _s('Create_Account', 'Hesap Oluştur');
  static String get login => _s('Login', 'Giriş Yap');
  static String get singUp => _s('Signup', 'Kayıt Ol');
  static String get name => _s('Name', 'Ad');
  static String get email => _s('Email', 'E-posta');
  static String get password => _s('Password', 'Şifre');
  static String get updatePassword => _s('Update_Password', 'Şifreyi Güncelle');
  static String get forgotPassword => _s('Forgot_password', 'Şifremi Unuttum');
  static String get reEnterPassword => _s('Re_enter_Password', 'Şifreyi Tekrar Girin');
  static String get confirmPassword => _s('Confirm_Password', 'Şifreyi Onayla');
  static String get passwordNotMatch => _s('Password_dosent_match', 'Şifreler eşleşmiyor');
  static String get rememberMe => _s('Remember_Me', 'Beni Hatırla');
  static String get addToCart => _s('Add_To_Cart', 'Sepete Ekle');
  static String get searchProduct => _s('Search_products', 'Ürün Ara');
  static String get searchProductHint =>
      _s('Search_products_hint', 'Ürün adı veya kategori ara…');
  static String get searchEmptyHint => _s(
        'Search_empty_hint',
        'Aramak istediğiniz ürünü yazın',
      );
  static String get searchTryDifferent => _s(
        'Search_try_different',
        'Farklı bir kelime deneyin',
      );
  static String get shareProduct => _s('Share_product', 'Paylaş');
  static String get lowStock => _s('Low_stock', 'Son birkaç ürün');
  static String get inStock => _s('In_stock', 'Stokta');
  static String get recentSearches => _s('Recent_searches', 'Son Aramalar');
  static String get popularSearches => _s('Popular_searches', 'Popüler Aramalar');
  static String get description => _s('Description', 'Açıklama');
  static String get reviews => _s('Reviews', 'Yorumlar');
  static String get seeAllReview => _s('See_all_Reviews', 'Tüm Yorumları Gör');
  static String get sellerInformation => _s('Seller_Information', 'Satıcı Bilgileri');
  static String get sellerInfo => _s('Seller_Info', 'Satıcı Bilgisi');
  static String get quantity => _s('quantity', 'Adet');
  static String get home => _s('home', 'Ana Sayfa');
  static String get order => _s('Order', 'Siparişler');
  static String get profile => _s('profile', 'Profilim');
  static String get shopNow => _s('Shop_Now', 'Hemen Al');
  static String get saleOver => _s('Sale_Over', 'İndirim Bitti');
  static String get availability => _s('Availability', 'Stok Durumu');
  static String get stockOut => _s('Out_of_Stock', 'Stokta Yok');
  static String get productsAvailable => _s('Products_Available', 'Ürün Mevcut');
  static String get products => _s('products', 'Ürünler');
  static String get product => _s('Product', 'Ürün');
  static String get category => _s('category', 'Kategori');
  static String get cart => _s('Cart', 'Sepet');
  static String get checkoutNow => _s('Checkout_Now', 'Hemen Öde');
  static String get checkout => _s('Checkout', 'Ödeme');
  static String get total => _s('total', 'Toplam');
  static String get apply => _s('Apply', 'Uygula');
  static String get discountCoupon => _s('Discount_coupon', 'İndirim Kuponu');
  static String get applyCoupon => _s('Apply_Coupon', 'Kupon Uygula');
  static String get billingAddress => _s('Billing_Address', 'Fatura Adresi');
  static String get shippingAddress => _s('Shipping_Address', 'Teslimat Adresi');
  static String get subTotal => _s('SUBTOTAL', 'Ara Toplam');
  static String get placeOrderNow => _s('Place_Order', 'Sipariş Ver');
  static String get agreeTermAndCondition => _s('I_agree_all_terms_and_condition_in_ecoShop', 'Şartları ve koşulları kabul ediyorum');
  static String get termsConsentSuffix =>
      _s('terms_consent_suffix', "'ı okudum ve kabul ediyorum.");
  static String get termAndCondition =>
      _s('Please_agree_terms_condition', 'Lütfen zorunlu yasal metinleri kabul edin');
  static String get checkoutLegalTitle =>
      _s('checkout_legal_title', 'Yasal Onaylar');
  static String get shippingCost => _s('Shipping_Cost', 'Kargo Ücreti');
  static String get selectPaymentOption => _s('Please_Select_Your_Payment_Method', 'Ödeme Yöntemi Seçin');
  static String get cashOnDelivery => _s('Cash_On_Delivery', 'Kapıda Ödeme');
  static String get bankPayment => _s('Bank_Payment', 'Banka Ödemesi');
  static String get bankInfo => _s('Please_enter_bank_information', 'Banka bilgilerini girin');
  static String get pending => _s('Pending', 'Beklemede');
  static String get progress => _s('Progress', 'Hazırlanıyor');
  static String get delivered => _s('Delivered', 'Teslim Edildi');
  static String get completed => _s('Completed', 'Tamamlandı');
  static String get declined => _s('Declined', 'İptal Edildi');
  static String get totalOrders => _s('Total_Orders', 'Toplam Sipariş');
  static String get cancel => _s('Cancel', 'İptal');
  static String get viewDetails => _s('View_Details', 'Detayları Gör');
  static String get orderTrackingNumber => _s('order_tracking_nubmer', 'Sipariş Takip No');
  static String get orderNumber => _s('order_number', 'Sipariş No');
  static String get backToShop => _s('Back_to_Shop', 'Alışverişe Dön');
  static String get writeYourReviews => _s('Write_Your_Reviews', 'Yorumunuzu Yazın');
  static String get writeSomething => _s('Write_something', 'Bir şeyler yazın');
  static String get pleaseWriteSomething => _s('Please_write_something', 'Lütfen bir şeyler yazın');
  static String get submitReview => _s('Submit_Review', 'Yorumu Gönder');
  static String get writeReview => _s('Write_Review', 'Yorum Yaz');
  static String get reviewSubmitted => _s('Review_Submitted', 'Yorum Yapıldı');
  static String get confirmDeliveryReceived =>
      _s('Confirm_Delivery_Received', 'Teslim Aldım');
  static String get confirmingDelivery =>
      _s('Confirming_Delivery', 'Onaylanıyor...');
  static String get deliveryConfirmed => _s(
        'Delivery_Confirmed',
        'Ürün teslim onayınız alındı. Teşekkürler.',
      );
  static String get deliveryConfirmedBadge =>
      _s('Delivery_Confirmed_Badge', 'Teslim onaylandı');
  static String get cargoLabel => _s('Cargo', 'Kargo');
  static String get trackingNumber => _s('Tracking_Number', 'Takip No');
  static String get trackingLink => _s('Tracking_Link', 'Takip Linki');
  static String get trackLocation => _s('Track_Location', 'Konumu Takip Et');
  static String get orderIsDeclined => _s('Order_Declined', 'Sipariş iptal edildi');
  static String get sendOtp => _s('Send_OTP', 'OTP Gönder');
  static String get seeAll => _s('see_all', 'Tümünü Gör');
  static String get productDetails => _s('Product_Details', 'Ürün Detayı');
  static String get relatedProduct => _s('Related_Product', 'İlgili Ürünler');
  static String get tags => _s('Tags', 'Etiketler');
  static String get bearFormer => _s('Beer_Former', 'Beer Former');
  static String get mobileElectronics => _s('MobileElectronics', 'Elektronik');
  static String get totalPrice => _s('Total_Price', 'Toplam Fiyat');
  static String get pleaseWaitAMoment =>
      _s('Please_wait_a_moment', 'Seyfibaba ürünleri geliyor...');
  static String get seyfibabaLoading1 =>
      _s('seyfibaba_loading_1', 'Seyfibaba rafları hazırlanıyor...');
  static String get seyfibabaLoading2 =>
      _s('seyfibaba_loading_2', 'Salon malzemeleri toplanıyor...');
  static String get seyfibabaLoading3 =>
      _s('seyfibaba_loading_3', 'Makaslar bileniyor...');
  static String get seyfibabaLoading4 =>
      _s('seyfibaba_loading_4', 'Kuaför çantanız dolduruluyor...');
  static String get seyfibabaLoading5 =>
      _s('seyfibaba_loading_5', 'En iyi ürünler seçiliyor...');
  static String get seyfibabaLoading6 =>
      _s('seyfibaba_loading_6', 'Bir saniye, Seyfibaba geliyor...');
  static String get seyfibabaLoading7 =>
      _s('seyfibaba_loading_7', 'Mağaza vitrini güncelleniyor...');
  static String get appInfo => _s('app_info', 'Uygulama Bilgisi');
  static String get active => _s('Active', 'Aktif');
  static String get itemsInYourCart => _s('Items_in_Your_Cart', 'Sepetinizdeki Ürünler');
  static String get itemInYourCart => _s('Item_in_your_cart', 'Sepetinizdeki Ürün');
  static String get orderAmount => _s('Order_Amount', 'Sipariş Tutarı');
  static String get totalAmount => _s('Total_Amount', 'Toplam Tutar');
  static String get billDetails => _s('Bill_Details', 'Fatura Detayı');
  static String get promoCode => _s('promo_code', 'Promosyon Kodu');
  static String get deliveryLocation => _s('Delivery_Location', 'Teslimat Konumu');
  static String get add => _s('Add', 'Ekle');
  static String get loading => _s('Loading', 'Yükleniyor');
  static String get noAddress => _s('No_Address', 'Adres Yok');
  static String get somethingWentWrong => _s('Something_went_wrong', 'Bir sorun oluştu');
  static String get fees => _s('fees', 'Ücretler');
  static String get freeShipping => _s('free_shipping', 'Ücretsiz Kargo');
  static String get homeDeliveryFree => _s('home_delivery_free_shipping', 'Eve Ücretsiz Teslimat');
  static String get shippingRules => _s('shipping_rules_based_on_qty_6_10', 'Kargo Kuralları');
  static String get homeDelivery => _s('home_delivery', 'Eve Teslimat');
  static String get guestCheckoutDisabled => _s(
        'Guest_checkout_disabled',
        'Misafir sipariş geçici olarak kapalı. Lütfen giriş yapın veya üye olun.',
      );
  static String get loginRequiredForCheckout => _s(
        'Login_required_for_checkout',
        'Ödeme için giriş yapmanız gerekiyor.',
      );
  static String get selectLocation => _s('Please_add_new_location_or_select_exiting_location', 'Lütfen teslimat adresi seçin');
  static String get selectBillingAddress => _s('Please_select_billing_address', 'Lütfen fatura adresi seçin');
  static String get selectShippingMethod => _s('Please_select_shipping_method', 'Lütfen kargo yöntemi seçin');
  static String get shippingCharge => _s('Shipping_charge', 'Kargo Ücreti');
  static String get deliveryCharge => _s('Delivery_Charge', 'Teslimat Ücreti');
  static String get fee => _s('Fee', 'Ücret');
  static String get basedOnDistance => _s('Based_on_Distance', 'Mesafeye göre');
  static String get orderSummary => _s('Order_Summary', 'Sipariş Özeti');
  static String get location => _s('Select_Location', 'Konum Seç');
  static String get noItemsFound => _s('No_items_found', 'Ürün bulunamadı');
  static String get noCartItem => _s('Empty_You_dont_Cart_any_Products', 'Sepetiniz boş');
  static String get singleOrder => _s('Single_Order', 'Sipariş Detayı');
  static String get orderReceived => _s('Order_Received', 'Sipariş Alındı');
  static String get orderStatusTitle => _s('Order_Status', 'Sipariş Durumu');
  static String get orderProductsTitle => _s('Order_Products', 'Siparişteki Ürünler');
  static String get paymentMethod => _s('Payment_Method', 'Ödeme Yöntemi');
  static String get paymentPaid => _s('Payment_Paid', 'Ödendi');
  static String get paymentPending => _s('Payment_Pending', 'Ödeme Bekliyor');
  static String get shippingMethod => _s('Shipping_Method', 'Kargo Yöntemi');
  static String get trackCargo => _s('Track_Cargo', 'Kargoyu Takip Et');
  static String get copiedToClipboard => _s('Copied_to_clipboard', 'Panoya kopyalandı');
  static String orderProductCount(int count) {
    final raw = _s('Order_product_count', '{count} ürün');
    return raw.replaceAll('{count}', '$count');
  }
  static String get emptyAllOrders =>
      _s('Empty_all_orders', 'Henüz siparişiniz yok');
  static String get emptyAllOrdersHint => _s(
        'Empty_all_orders_hint',
        'Alışverişe başlayın, siparişleriniz burada görünecek.',
      );
  static String get emptyPendingOrders =>
      _s('Empty_pending_orders', 'Bekleyen sipariş yok');
  static String get emptyProgressOrders =>
      _s('Empty_progress_orders', 'Hazırlanan sipariş yok');
  static String get emptyDeliveredOrders =>
      _s('Empty_delivered_orders', 'Teslim edilen sipariş yok');
  static String get emptyCompletedOrders =>
      _s('Empty_completed_orders', 'Tamamlanan sipariş yok');
  static String get emptyDeclinedOrders =>
      _s('Empty_declined_orders', 'İptal edilen sipariş yok');
  static String get emptyTabOrdersHint => _s(
        'Empty_tab_orders_hint',
        'Bu durumda sipariş bulunmuyor.',
      );
  static String get emptyCartTitle => _s('Empty_cart_title', 'Sepetiniz boş');
  static String get emptyCartHint => _s(
        'Empty_cart_hint',
        'Beğendiğiniz ürünleri sepete ekleyerek alışverişe başlayın.',
      );
  static String get emptyFilterTitle =>
      _s('Empty_filter_title', 'Filtreye uygun ürün yok');
  static String get emptyFilterHint => _s(
        'Empty_filter_hint',
        'Filtreleri değiştirerek tekrar deneyin.',
      );
  static String get pageNotFound => _s('Page_not_found', 'Sayfa bulunamadı');
  static String get startShopping => _s('Start_shopping', 'Alışverişe Başla');
  static String get emptyWishlistTitle =>
      _s('Empty_wishlist_title', 'Favori listeniz boş');
  static String get emptyWishlistHint => _s(
        'Empty_wishlist_hint',
        'Beğendiğiniz ürünleri kalp ikonuna basarak ekleyin.',
      );
  static String get emptyAddressHint => _s(
        'Empty_address_hint',
        'Teslimat için yeni adres ekleyin.',
      );
  static String get removeProductConfirm => _s(
        'Remove_product_confirm',
        'Bu ürünü kaldırmak istiyor musunuz?',
      );
  static String get yesRemove => _s('Yes_remove', 'Evet, Kaldır');
  static String get selectVariantItem =>
      _s('Select_variant_item', 'Varyant seçin');
  static String get emptySearchHint => _s(
        'Empty_search_hint',
        'Aramak istediğiniz ürünü yazın veya önerilere dokunun.',
      );
  static String get whatIsYourRate => _s('What_is_your_Rate', 'Puanınız nedir?');
  static String get whatIsYourReview => _s('Write_Your_Reviews', 'Yorumunuzu Yazın');
  static String get notNow => _s('Not_Now', 'Şimdi Değil');
  static String get yourAddress => _s('Your_address', 'Adresiniz');
  static String get termsCon => _s('Term_and_Conditions', 'Şartlar ve Koşullar');
  static String get privacyPolicy => _s('Privacy_Policy', 'Gizlilik Politikası');
  static String get faq => _s('FAQ', 'Sıkça Sorulan Sorular');
  static String get aboutUs => _s('About_us', 'Hakkımızda');
  static String get contactUs => _s('Contact_Us', 'İletişim');
  static String get logout => _s('Sign_Out', 'Çıkış Yap');
  static String get other => _s('Other', 'Diğer');
  static String get offers => _s('Offers', 'Teklifler');
  static String get wishlist => _s('Wishlist', 'Favoriler');
  static String get clearWishlist => _s('Clear_wishlist', 'Favorileri Temizle');
  static String get address => _s('Address', 'Adres');
  static String get swipeToDelete => _s('swipe_right_to_delete_any_item', 'Silmek için sola kaydırın');
  static String get areYouSure => _s('Are_You_Sure', 'Emin misiniz?');
  static String get firstName => _s('First_Name', 'Ad');
  static String get addNewAddress => _s('Add_New_Address', 'Yeni Adres Ekle');
  static String get emailAddress => _s('Email_Address', 'E-posta Adresi');
  static String get phoneNumber => _s('Phone_Number', 'Telefon Numarası');
  static String get country => _s('Country', 'Ülke');
  static String get state => _s('State', 'Şehir');
  static String get city => _s('City', 'İlçe');
  static String get updateAddress => _s('Update_address', 'Adresi Güncelle');
  static String get updateProfile => _s('Update_Profile', 'Profili Güncelle');
  static String get editProfile => _s('Edit_Profile', 'Profili Düzenle');
  static String get exitApp => _s('You_Want_to_Exit_from_Application', 'Uygulamadan çıkmak istiyor musunuz?');
  static String get yesExit => _s('Yes_Exit', 'Evet, Çık');
  static String get myOffers => _s('My_Offers', 'Tekliflerim');
  static String get allCategories => _s('All_Categories', 'Tüm Kategoriler');
  static String get allProducts => _s('All_Products', 'Tüm Ürünler');
  static String get sendUsMessage => _s('Send_Us_A_Message', 'Bize Mesaj Gönderin');
  static String get subject => _s('Subject', 'Konu');
  static String get message => _s('Message', 'Mesaj');
  static String get sendNow => _s('Send_Now', 'Gönder');
  static String get usernameOrEmail => _s('username_or_email', 'Kullanıcı Adı veya E-posta');
  static String get continueAsGuest => _s('Continue_as_Guest', 'Misafir Olarak Devam Et');
  static String get socialLogin => _s('SignIn_with_Social', 'Sosyal Medya ile Giriş');
  static String get signUpCondition => _s('I_Consent_to_the_Privacy_Policy', 'Gizlilik Politikasını kabul ediyorum');
  static String get price => _s('Price', 'Fiyat');
  static String get brand => _s('Brands', 'Markalar');
  static String get filter => _s('filter', 'Filtrele');
  static String get size => _s('Size', 'Boyut');
  static String get findProduct => _s('Find_Product', 'Ürün Bul');
  static String get allPopularProduct => _s('All_Popular_Product', 'Tüm Popüler Ürünler');
  static String get days => _s('Days', 'Gün');
  static String get minutes => _s('Minutes', 'Dakika');
  static String get second => _s('Seconds', 'Saniye');
  static String get hours => _s('Hours', 'Saat');
  static String get typeHere => _s('Type_Here', 'Buraya yazın');
  static String get alreadyInCart => _s('Already_in_Cart', 'Zaten Sepette');
  static String get deleteAccount => _s('Delete_Account', 'Hesabı Sil');
  static String get doYouWantToDeleteAccount => _s('Do_you_want_to_Delete_account', 'Hesabınızı silmek istiyor musunuz?');
  static String get areYouSureYouWantToLogOut => _s('Are_you_sure_you_want_to_Logout', 'Çıkış yapmak istediğinize emin misiniz?');
  static String get becomeSeller => _s('Become_seller', 'Satıcı Ol');
  static String get secondHand => _s('Second_Hand', 'İkinci El');
  static String get sellerPanelEnter =>
      _s('Seller_Panel_Enter', 'Satıcı Panele Geç');
  static String get sellerPanel => _s('Seller_Panel', 'Satıcı Paneli');
  static String get sellerDashboard => _s('Seller_Dashboard', 'Özet');
  static String get sellerProducts => _s('Seller_Products', 'Ürünler');
  static String get sellerOrders => _s('Seller_Orders', 'Siparişler');
  static String get sellerMore => _s('Seller_More', 'Daha fazla');
  static String get sellerBackToShop =>
      _s('Seller_Back_To_Shop', 'Alışverişe Dön');
  static String get sellerInactiveHint => _s(
        'Seller_Inactive_Hint',
        'Satıcı hesabınız henüz aktif değil veya erişim yok.',
      );
  static String get shopName => _s('Shop_Name', 'Mağaza Adı');
  static String get becomeSellerRequest => _s('Become_Seller_Request', 'Satıcı Başvurusu');
  static String get trackYourOrder =>
      _s('Track_Your_Order', 'Siparişinizi Takip Edin');
  static String get trackYourOrderHint => _s(
        'Track_order_hint',
        'Sipariş numaranızı girerek durumunu görüntüleyin.',
      );
  static String get enterOrderId =>
      _s('Enter_Order_Id', 'Sipariş numarası girin');
  static String get orderNumberRequired => _s(
        'Order_number_required',
        'Sipariş numarası zorunlu',
      );
  static String get trackOrderButton =>
      _s('Track_Order', 'Siparişi Takip Et');
  static String get trackingOrderTitle =>
      _s('Tracking_Order', 'Sipariş Takibi');
  static String get settings => _s('Settings', 'Ayarlar');
  static String get notifications => _s('Notifications', 'Bildirimler');
  static String get darkMode => _s('Dark_Mode', 'Karanlık Mod');
  static String get activeLocationSetting =>
      _s('Active_Location_Setting', 'Konum Etkin');
  static String get languageSetting => _s('Language_Setting', 'Dil');
  static String get oneClickLogin =>
      _s('One_Click_Login', 'Tek Tıkla Giriş');
  static String get bannerProducts =>
      _s('Banner_Products', 'Kampanya Ürünleri');
  static String get noOfferAvailable =>
      _s('No_Offer_Available', 'Şu an aktif teklif yok');
  static String get addressDeletedSuccessfully => _s(
        'Address_deleted_successfully',
        'Adres başarıyla silindi',
      );
  static String get deleteAddressConfirm => _s(
        'Delete_address_confirm',
        'Bu adresi silmek istiyor musunuz?',
      );
  static String get yesDelete => _s('Yes_Delete', 'Evet, Sil');
  static String get noThanks => _s('No_Thanks', 'Hayır');
  static String get clearFilter => _s('Clear_filter', 'Temizle');
  static String get discountedProducts =>
      _s('Discounted_products', 'İndirimli ürünler');
  static String get paymentLoadFailed => _s(
        'Payment_load_failed',
        'Ödeme bilgileri yüklenemedi.',
      );
  static String get accountVerification =>
      _s('Account_verification', 'Hesap doğrulama');
  static String get editAddress => _s('Edit_Address', 'Adresi Düzenle');
  static String get identityType =>
      _s('Identity_Type', 'Kimlik Türü');
  static String get deliveryManType =>
      _s('Delivery_Man_Type', 'Kurye Türü');
  static String get emptyNotificationsTitle =>
      _s('Empty_notifications_title', 'Bildirim yok');
  static String get emptyNotificationsHint => _s(
        'Empty_notifications_hint',
        'Yeni bildirimler burada görünecek.',
      );
  static String get notificationSettings =>
      _s('Notification_settings', 'Bildirim Ayarları');
  static String get inbox => _s('Inbox', 'Gelen Kutusu');
  static String get deliveryManRegister =>
      _s('Delivery_Man_Register', 'Kurye Kaydı');
  static String get changePassword =>
      _s('Change_password', 'Şifre Değiştir');
  static String get payments => _s('Payments', 'Ödemeler');
  static String get addPaymentMethod =>
      _s('Add_Payment_Method', 'Ödeme Yöntemi Ekle');
  static String get videos => _s('Videos', 'Videolar');
  static String get currentPassword =>
      _s('Current_password', 'Mevcut şifre');
  static String get enterCurrentPassword => _s(
        'Enter_current_password',
        'Mevcut şifrenizi girin',
      );
  static String get enterNewPassword =>
      _s('Enter_new_password', 'Yeni şifrenizi girin');
  static String get enterConfirmPassword => _s(
        'Enter_confirm_password',
        'Şifrenizi tekrar girin',
      );
  static String get profileUpdatedSuccessfully => _s(
        'Profile_updated_successfully',
        'Profil başarıyla güncellendi',
      );
  static String get formSubmitSuccess => _s(
        'Form_submit_success',
        'Formunuz başarıyla gönderildi',
      );
  static String get paymentFailed =>
      _s('Payment_failed', 'Ödeme başarısız');
  static String get paymentCancelled =>
      _s('Payment_cancelled', 'Ödeme iptal edildi');
  static String get cardType => _s('Card_type', 'Kart türü');
  static String get noDataAvailable =>
      _s('No_data_available', 'Gösterilecek veri yok');
  static String get emptyInboxTitle =>
      _s('Empty_inbox_title', 'Mesaj kutunuz boş');
  static String get emptyInboxHint => _s(
        'Empty_inbox_hint',
        'Satıcılarla yaptığınız yazışmalar burada görünecek.',
      );
}
