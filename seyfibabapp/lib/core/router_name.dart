import '../modules/cart/guest_cart_screen.dart';
import '../modules/cart/guest_checkout_screen.dart';
import '../modules/category/child_category_list_screen.dart';
import '../modules/category/child_category_product_screen.dart';
import '../modules/category/single_category_product_screen.dart';
import '../modules/category/sub_category_list_screen.dart';
import '../modules/category/model/category_navigation_args.dart';
import '../modules/onboarding/onboarding_screen.dart';
import '../modules/order/model/order_model.dart';
import '../modules/order/order_tracking_form_screen.dart';
import '../modules/order/order_tracking_screen.dart';
import '../modules/order/tracking_location_screen.dart';
import '../modules/place_order/guest_place_order_screen.dart';
import '../modules/product_details/component/more_video_screen.dart';
import '../modules/product_details/model/video_model.dart';
import '../modules/setting/guest_address_screen.dart';
import '../utils/language_string.dart';
import 'error/router_package_name.dart';

class RouteNames {
  static const String onBoardingScreen = '/onBoardingScreen';
  static const String animatedSplashScreen = '/';
  static const String mainPage = '/mainPage';
  static const String homeScreen = '/homeScreen';
  static const String authenticationScreen = '/authenticationScreen';
  static const String forgotScreen = '/forgotScreen';
  static const String verificationCodeScreen = '/verificationCodeScreen';
  static const String setPasswordScreen = '/setPasswordScreen';
  static const String allCategoryListScreen = '/allCategoryListScreen';
  static const String allSellerList = '/allSellerList';
  static const String allPopularProductScreen = '/allPopularProductScreen';
  static const String notificationScreen = '/notificationScreen';
  static const String messageScreen = '/messageScreen';
  static const String chatListScreen = '/chatListScreen';
  static const String singleCategoryProductScreen =
      '/singleCategoryProductScreen';
  static const String brandProductScreen = '/brandProductScreen';
  static const String subCategoryProductScreen = '/subCategoryProductScreen';
  static const String subCategoryListScreen = '/subCategoryListScreen';
  static const String childCategoryListScreen = '/childCategoryListScreen';
  static const String childCategoryProductScreen = '/childCategoryProductScreen';
  static const String orderScreen = '/orderScreen';
  static const String singleOrderScreen = '/singleOrder';
  static const String orderTrackingScreen = '/orderTrackingScreen';
  static const String orderTrackingFormScreen = '/orderTrackingFormScreen';
  static const String settingScreen = '/settingScreen';
  static const String termsConditionScreen = '/termsConditionScreen';
  static const String privacyPolicyScreen = '/privacyPolicyScreen';
  static const String legalDocumentsHubScreen = '/legalDocumentsHubScreen';
  static const String legalDocumentScreen = '/legalDocumentScreen';
  static const String faqScreen = '/faqScreen';
  static const String aboutUsScreen = '/aboutUsScreen';
  static const String contactUsScreen = '/contactUsScreen';
  static const String profileEditScreen = '/profileEditScreen';
  static const String profileOfferScreen = '/profileOfferScreen';
  static const String wishlistOfferScreen = '/wishlistOfferScreen';
  static const String addAddressScreen = '/addAddressScreen';
  static const String editAddressScreen = '/editAddressScreen';
  static const String addNewPaymentCardScreen = '/addNewPaymentCardScreen';
  static const String cartScreen = '/cartScreen';
  static const String checkoutScreen = '/checkoutScreen';
  static const String guestCartScreen = '/guestCartScreen';
  static const String guestAddressScreen = '/guestAddressScreen';
  static const String guestCheckoutScreen = '/guestCheckoutScreen';
  static const String productDetailsScreen = '/productDetailsScreen';
  static const String submitFeedBackScreen = '/submitFeedBackScreen';
  static const String addressScreen = '/addressScreen';
  static const String paymentsScreen = '/paymentsScreen';
  static const String productSearchScreen = '/productSearchScreen';
  static const String allFlashDealProductScreen = '/allFlashDealProductScreen';
  static const String reviewListScreen = '/reviewListScreen';
  static const String changePasswordScreen = '/changePasswordScreen';
  static const String placeOrderScreen = '/placeOrderScreen';
  static const String guestPlaceOrderScreen = '/guestPlaceOrderScreen';
  static const String trackingLocationScreen = '/trackingLocationScreen';
  static const String bankScreen = '/bankScreen';
  static const String iyzicoPaymentScreen = '/iyzicoPaymentScreen';
  static const String flashScreen = '/flashScreen';
  static const String bannerProducts = '/bannerProducts';
  static const String sellerScreen = '/sellerScreen';
  static const String maintainScreen = '/maintainScreen';
  static const String chatScreen = '/chatScreen';
  static const String becomeSellerScreen = '/becomeSellerScreen';
  static const String sellerPanelScreen = '/sellerPanelScreen';
  static const String sellerQuickProductScreen = '/sellerQuickProductScreen';
  static const String sellerEditProductScreen = '/sellerEditProductScreen';
  static const String sellerBulkImportScreen = '/sellerBulkImportScreen';
  static const String sellerEarningsScreen = '/sellerEarningsScreen';
  static const String sellerReturnsScreen = '/sellerReturnsScreen';
  static const String sellerShopProfileScreen = '/sellerShopProfileScreen';
  static const String sellerProductGalleryScreen =
      '/sellerProductGalleryScreen';
  static const String sellerProductVariantsScreen =
      '/sellerProductVariantsScreen';
  static const String sellerAdminContactScreen = '/sellerAdminContactScreen';
  static const String sellerKycScreen = '/sellerKycScreen';
  static const String sellerNotificationsScreen = '/sellerNotificationsScreen';
  static const String sellerInventoryScreen = '/sellerInventoryScreen';
  static const String sellerReviewsScreen = '/sellerReviewsScreen';
  static const String sellerBrandsScreen = '/sellerBrandsScreen';
  static const String sellerFullProductScreen = '/sellerFullProductScreen';
  static const String sellerFaqScreen = '/sellerFaqScreen';
  static const String sellerGuideScreen = '/sellerGuideScreen';
  static const String secondHandListScreen = '/secondHandListScreen';
  static const String secondHandDetailScreen = '/secondHandDetailScreen';
  static const String secondHandHubScreen = '/secondHandHubScreen';
  static const String secondHandConversationScreen =
      '/secondHandConversationScreen';
  static const String salonHubScreen = '/salonHubScreen';
  static const String salonCrmGateScreen = '/salonCrmGateScreen';
  static const String salonCrmPatronSetupScreen = '/salonCrmPatronSetupScreen';
  static const String salonCrmAltAuthScreen = '/salonCrmAltAuthScreen';
  static const String salonCrmPatronAuthScreen = '/salonCrmPatronAuthScreen';
  static const String salonCrmStaffAuthScreen = '/salonCrmStaffAuthScreen';
  static const String salonCrmCustomerAuthScreen = '/salonCrmCustomerAuthScreen';
  static const String salonCrmCustomerHomeScreen = '/salonCrmCustomerHomeScreen';
  static const String salonCrmCustomerBookingScreen =
      '/salonCrmCustomerBookingScreen';
  static const String salonCrmHomeScreen = '/salonCrmHomeScreen';
  static const String salonCrmStaffScreen = '/salonCrmStaffScreen';
  static const String salonCrmStaffDetailScreen = '/salonCrmStaffDetailScreen';
  static const String salonCrmAppointmentsScreen = '/salonCrmAppointmentsScreen';
  static const String salonCrmCustomersScreen = '/salonCrmCustomersScreen';
  static const String salonCrmLedgerScreen = '/salonCrmLedgerScreen';
  static const String salonCrmPerformanceScreen =
      '/salonCrmPerformanceScreen';
  static const String salonCrmProfileScreen = '/salonCrmProfileScreen';
  static const String salonCrmServicesScreen = '/salonCrmServicesScreen';
  static const String salonCrmCustomerCodeScreen =
      '/salonCrmCustomerCodeScreen';
  static const String salonCrmCalendarShareScreen =
      '/salonCrmCalendarShareScreen';
  static const String salonCrmMyPhotoScreen = '/salonCrmMyPhotoScreen';
  static const String salonCrmCustomerLinkScreen = '/salonCrmCustomerLinkScreen';
  static const String salonCrmCustomerQrScanScreen =
      '/salonCrmCustomerQrScanScreen';
  static const String deliveryManRegistrationScreen =
      '/deliveryManRegistrationScreen';
  static const String moreVideoScreen = '/moreVideoScreen';

  static Route<dynamic> generateRoute(RouteSettings settings) {
    switch (settings.name) {
      case RouteNames.onBoardingScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const OnboardingScreen());
      case RouteNames.changePasswordScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const ChangePasswordScreen());
      case RouteNames.productSearchScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const ProductSearchScreen());

      case RouteNames.maintainScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const MaintainScreen());

      case RouteNames.mainPage:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const MainPage());
      case RouteNames.homeScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const HomeScreen());
      case RouteNames.animatedSplashScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const AnimatedSplashScreen());
      case RouteNames.authenticationScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const AuthenticationScreen());
      case RouteNames.forgotScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const ForgotScreen());
      case RouteNames.verificationCodeScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const VerificationCodeScreen());
      case RouteNames.setPasswordScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const SetPasswordScreen());

      case RouteNames.allCategoryListScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const AllCategoryListScreen());
      case RouteNames.allSellerList:
        final sellerList = settings.arguments as List<HomeSellerModel>;
        return MaterialPageRoute(
            settings: settings,
            builder: (_) => AllSellerList(sellers: sellerList));

      case RouteNames.sellerScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const SellerDetailsScreen());
      case RouteNames.allPopularProductScreen:
        final keyword = settings.arguments as String;
        return MaterialPageRoute(
            settings: settings,
            builder: (_) => AllPopularProductScreen(keyword: keyword));
      case RouteNames.singleCategoryProductScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) =>
              SingleCategoryProductScreen.fromArgs(settings.arguments),
        );
      case RouteNames.brandProductScreen:
        final slug = settings.arguments as String;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => BrandProductScreen(slug: slug),
        );
      case RouteNames.chatScreen:
        // final slug = settings.arguments as String;
        return MaterialPageRoute(
            settings: settings, builder: (_) => const ChatScreen());

      case RouteNames.becomeSellerScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const BecomeSellerScreen());

      case RouteNames.sellerPanelScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SellerPanelScreen(),
        );

      case RouteNames.sellerQuickProductScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SellerQuickProductScreen(),
        );

      case RouteNames.sellerEditProductScreen:
        final productId = settings.arguments as int;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => SellerEditProductScreen(productId: productId),
        );

      case RouteNames.sellerBulkImportScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SellerBulkImportScreen(),
        );

      case RouteNames.sellerEarningsScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SellerEarningsScreen(),
        );

      case RouteNames.sellerReturnsScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SellerReturnsScreen(),
        );

      case RouteNames.sellerShopProfileScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SellerShopProfileScreen(),
        );

      case RouteNames.sellerProductGalleryScreen:
        final galleryProductId = settings.arguments as int;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) =>
              SellerProductGalleryScreen(productId: galleryProductId),
        );

      case RouteNames.sellerProductVariantsScreen:
        final variantProductId = settings.arguments as int;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) =>
              SellerProductVariantsScreen(productId: variantProductId),
        );

      case RouteNames.sellerAdminContactScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SellerAdminContactScreen(),
        );

      case RouteNames.sellerKycScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SellerKycScreen(),
        );

      case RouteNames.sellerNotificationsScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SellerNotificationsScreen(),
        );

      case RouteNames.sellerInventoryScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SellerInventoryScreen(),
        );

      case RouteNames.sellerReviewsScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SellerReviewsScreen(),
        );

      case RouteNames.sellerBrandsScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SellerBrandsScreen(),
        );

      case RouteNames.sellerFullProductScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SellerFullProductScreen(),
        );

      case RouteNames.sellerFaqScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SellerFaqScreen(),
        );

      case RouteNames.sellerGuideScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SellerGuideScreen(),
        );

      case RouteNames.secondHandListScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SecondHandListScreen(),
        );

      case RouteNames.secondHandDetailScreen:
        final raw = settings.arguments;
        final listingId = raw is int
            ? raw
            : int.tryParse('$raw') ?? 0;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => SecondHandDetailScreen(listingId: listingId),
        );

      case RouteNames.secondHandHubScreen:
        final initialTab = settings.arguments is int ? settings.arguments as int : 0;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => SecondHandHubScreen(initialTab: initialTab),
        );

      case RouteNames.secondHandConversationScreen:
        final conversationId = settings.arguments as int;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) =>
              SecondHandConversationScreen(conversationId: conversationId),
        );

      case RouteNames.salonHubScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonHubScreen(),
        );

      case RouteNames.salonCrmGateScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmGateScreen(),
        );

      case RouteNames.salonCrmPatronSetupScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmPatronSetupScreen(),
        );

      case RouteNames.salonCrmAltAuthScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmAltAuthScreen(),
        );

      case RouteNames.salonCrmPatronAuthScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmPatronAuthScreen(),
        );

      case RouteNames.salonCrmStaffAuthScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmStaffAuthScreen(),
        );

      case RouteNames.salonCrmCustomerAuthScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmCustomerAuthScreen(),
        );

      case RouteNames.salonCrmCustomerHomeScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmCustomerHomeScreen(),
        );

      case RouteNames.salonCrmCustomerBookingScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmCustomerBookingScreen(),
        );

      case RouteNames.salonCrmHomeScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmHomeScreen(),
        );

      case RouteNames.salonCrmStaffScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmStaffScreen(),
        );

      case RouteNames.salonCrmStaffDetailScreen:
        final args = settings.arguments as Map<String, dynamic>? ?? {};
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => SalonCrmStaffDetailScreen(
            staffId: int.tryParse('${args['staff_id'] ?? 0}') ?? 0,
            isOwner: args['is_owner'] != false,
          ),
        );

      case RouteNames.salonCrmAppointmentsScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmAppointmentsScreen(),
        );

      case RouteNames.salonCrmCustomersScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmCustomersScreen(),
        );

      case RouteNames.salonCrmLedgerScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmLedgerScreen(),
        );

      case RouteNames.salonCrmPerformanceScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmPerformanceScreen(),
        );

      case RouteNames.salonCrmProfileScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmProfileScreen(),
        );

      case RouteNames.salonCrmServicesScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmServicesScreen(),
        );

      case RouteNames.salonCrmCustomerCodeScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmCustomerCodeScreen(),
        );

      case RouteNames.salonCrmCalendarShareScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmCalendarShareScreen(),
        );

      case RouteNames.salonCrmMyPhotoScreen:
        final args = settings.arguments as Map<String, dynamic>? ?? {};
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => SalonCrmMyPhotoScreen(
            staffId: int.tryParse('${args['staff_id'] ?? 0}') ?? 0,
            staffName: '${args['staff_name'] ?? 'Fotoğrafım'}',
            initialPhoto: args['photo']?.toString(),
            initialShowToCustomers: args['show_photo_to_customers'] != false,
            canWrite: args['can_write'] != false,
          ),
        );

      case RouteNames.salonCrmCustomerLinkScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmCustomerLinkScreen(),
        );

      case RouteNames.salonCrmCustomerQrScanScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const SalonCrmCustomerQrScanScreen(),
        );

      case RouteNames.deliveryManRegistrationScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const DeliveryManRegistrationScreen(),
        );
      case RouteNames.moreVideoScreen:
        final videos = settings.arguments as List<VideoModel>;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => MoreVideoScreen(videos: videos),
        );

      case RouteNames.subCategoryListScreen:
        final subListArgs = settings.arguments as CategoryNavigationArgs;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => SubCategoryListScreen(args: subListArgs),
        );
      case RouteNames.childCategoryListScreen:
        final childListArgs = settings.arguments as CategoryNavigationArgs;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => ChildCategoryListScreen(args: childListArgs),
        );
      case RouteNames.childCategoryProductScreen:
        final childProductArgs = settings.arguments as CategoryProductArgs;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => ChildCategoryProductScreen(args: childProductArgs),
        );
      case RouteNames.subCategoryProductScreen:
        final subProductArgs = settings.arguments;
        if (subProductArgs is CategoryProductArgs) {
          return MaterialPageRoute(
            settings: settings,
            builder: (_) => SubCategoryProductScreen(args: subProductArgs),
          );
        }
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => SubCategoryProductScreen(
            args: CategoryProductArgs(
              slug: subProductArgs as String,
              name: '',
            ),
          ),
        );
      case RouteNames.allFlashDealProductScreen:
        final products = settings.arguments as List<ProductModel>;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => AllFlashDealProductScreen(products: products),
        );
      case RouteNames.notificationScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const NotificationScreen());
      case RouteNames.chatListScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const ChatListScreen());
      case RouteNames.orderScreen:
      final isPayment = settings.arguments as bool;
        return MaterialPageRoute(
            settings: settings, builder: (_) =>  OrderScreen(isFromPayment:isPayment));
      case RouteNames.singleOrderScreen:
        // final trackNumber = settings.arguments as String;
        return MaterialPageRoute(
            settings: settings,
            builder: (_) => const SingleOrderDetails());

      case RouteNames.orderTrackingScreen:
        final orders = settings.arguments as OrderModel;
        return MaterialPageRoute(
            settings: settings,
            builder: (_) => OrderTrackingScreen(orders: orders));

      case RouteNames.orderTrackingFormScreen:
        return MaterialPageRoute(
            settings: settings,
            builder: (_) => const OrderTrackingFormScreen());
      case RouteNames.settingScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const SettingScreen());
      case RouteNames.termsConditionScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const TermsConditionScreen());
      case RouteNames.privacyPolicyScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const PrivacyPolicyScreen());
      case RouteNames.legalDocumentsHubScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const LegalDocumentsHubScreen());
      case RouteNames.legalDocumentScreen:
        final args = settings.arguments as Map<String, dynamic>? ?? {};
        return MaterialPageRoute(
            settings: settings,
            builder: (_) => LegalDocumentScreen(
                  slug: '${args['slug'] ?? 'terms'}',
                  title: args['title']?.toString(),
                ));
      case RouteNames.faqScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const FaqScreen());
      case RouteNames.aboutUsScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const AboutUsScreen());
      case RouteNames.contactUsScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const ContactUsScreen());
      case RouteNames.profileEditScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const ProfileEditScreen());
      case RouteNames.profileOfferScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const ProfileOfferScreen());
      case RouteNames.wishlistOfferScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const WishlistOfferScreen());
      case RouteNames.paymentsScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const PaymentsScreen());
      case RouteNames.addressScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const AddressScreen());

      case RouteNames.addAddressScreen:
        final addArgs = settings.arguments as Map<String, dynamic>? ?? const {};
        return MaterialPageRoute(
            settings: settings,
            builder: (_) => AddAddressScreen(
                  showInvoice: addArgs['show_invoice'] != false,
                ));

      case RouteNames.editAddressScreen:
        final map = settings.arguments as Map<String, dynamic>? ?? const {};
        return MaterialPageRoute(
            settings: settings, builder: (_) => EditAddressScreen(map: map));

      case RouteNames.addNewPaymentCardScreen:
        return MaterialPageRoute(
            settings: settings,
            builder: (_) => const AddNewPaymentCardScreen());
      case RouteNames.cartScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const CartScreen());
      case RouteNames.checkoutScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const CheckoutScreen());
        case RouteNames.guestCartScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const GuestCartScreen());
        case RouteNames.guestAddressScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const GuestAddressScreen());
        case RouteNames.guestCheckoutScreen:
        return MaterialPageRoute(
            settings: settings, builder: (_) => const GuestCheckoutScreen());
      case RouteNames.productDetailsScreen:
        final slug = settings.arguments as String;
        return MaterialPageRoute(
            settings: settings,
            builder: (_) => ProductDetailsScreen(slug: slug));
      case RouteNames.reviewListScreen:
        final productReviews =
            settings.arguments as List<DetailsProductReviewModel>;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => SeelAllReviewsScreen(productReviews: productReviews),
        );

      case RouteNames.submitFeedBackScreen:
        final orderItem = settings.arguments as OrderedProductModel;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => SubmitFeedBackScreen(orderItem: orderItem),
        );

      case RouteNames.placeOrderScreen:
        // final shippingMethod = settings.arguments as Map<String, dynamic>;
        return MaterialPageRoute(
            settings: settings, builder: (_) => const PlaceOrderScreen());

        case RouteNames.guestPlaceOrderScreen:
        // final shippingMethod = settings.arguments as Map<String, dynamic>;
        return MaterialPageRoute(
            settings: settings, builder: (_) => const GuestPlaceOrderScreen());

      case RouteNames.trackingLocationScreen:
        // final order = settings.arguments as OrderAddressModel;
        return MaterialPageRoute(
            settings: settings, builder: (_) => const TrackingLocationScreen());

      case RouteNames.bankScreen:
        final body = settings.arguments as Map<String, dynamic>;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => BankPaymentScreen(mapBody: body),
        );

      case RouteNames.iyzicoPaymentScreen:
        final args = settings.arguments;
        final paymentArgs = args is IyzicoPaymentArgs
            ? args
            : IyzicoPaymentArgs(
                checkoutUrl: args as String,
                orderId: '',
              );
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => IyzicoPaymentScreen(args: paymentArgs),
        );

      case RouteNames.flashScreen:
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => const FlashScreen(),
        );
      case RouteNames.bannerProducts:
        final slug = settings.arguments as String;
        return MaterialPageRoute(
          settings: settings,
          builder: (_) => BannerProductScreen(
            slug: slug,
          ),
        );

      default:
        return MaterialPageRoute(
          builder: (_) => Scaffold(
            body: Center(
              child: Text(Language.pageNotFound),
            ),
          ),
        );
    }
  }
}
