
import 'modules/animated_splash_screen/controller/currency/currency_cubit.dart';
import 'modules/animated_splash_screen/controller/internet_status/internet_status_bloc.dart';
import 'modules/animated_splash_screen/controller/translate_cubit/translate_cubit.dart';
import 'modules/cart/controllers/checkout_info/checkout_info_cubit.dart';
import 'modules/notification/controller/notification_cubit.dart';
import 'modules/order/controllers/order_tracking/order_tracking_cubit.dart';
import 'modules/place_order/controllers/iyzico/iyzico_cubit.dart';
import 'modules/product_details/controller/guest/guest_cubit.dart';
import 'modules/profile/controllers/map/map_cubit.dart';
import 'state_packages_names.dart';

class StateInjector {
  static late final SharedPreferences _sharedPreferences;

  static Future<void> init() async {
    _sharedPreferences = await SharedPreferences.getInstance();

    // Stripe.publishableKey = RemoteUrls.publishableKey;
  }

  static final repositoryProviders = <RepositoryProvider>[
    ///network client
    RepositoryProvider<Client>(
      create: (context) => Client(),
    ),
    RepositoryProvider<SharedPreferences>(
      create: (context) => _sharedPreferences,
    ),

    ///data source repository
    RepositoryProvider<RemoteDataSource>(
      create: (context) => RemoteDataSourceImpl(
        client: context.read(),
      ),
    ),

    RepositoryProvider<LocalDataSource>(
      create: (context) => LocalDataSourceImpl(
        sharedPreferences: context.read(),
      ),
    ),

    ///repository
    RepositoryProvider<AppSettingRepository>(
      create: (context) => AppSettingRepositoryImp(
        remoteDataSource: context.read(),
        localDataSource: context.read(),
      ),
    ),
    RepositoryProvider<HomeRepository>(
      create: (context) => HomeRepositoryImp(
        remoteDataSource: context.read(),
      ),
    ),

    RepositoryProvider<AuthRepository>(
      create: (context) => AuthRepositoryImp(
        remoteDataSource: context.read(),
        localDataSource: context.read(),
      ),
    ),

    RepositoryProvider<CategoryRepository>(
      create: (context) => CategoryRepositoryImp(
        remoteDataSource: context.read(),
      ),
    ),

    RepositoryProvider<SettingRepository>(
      create: (context) => SettingRepositoryImp(
        remoteDataSource: context.read(),
      ),
    ),

    RepositoryProvider<ProductDetailsRepository>(
      create: (context) => ProductDetailsRepositoryImp(
        remoteDataSource: context.read(),
        localDataSource: context.read(),
      ),
    ),


    RepositoryProvider<ProfileRepository>(
      create: (context) => ProfileRepositoryImp(
        remoteDataSource: context.read(),
        localDataSource: context.read(),
      ),
    ),
    RepositoryProvider<FlashRepository>(
      create: (context) => FlashRepositoryImp(
        remoteDataSource: context.read(),
      ),
    ),
    RepositoryProvider<OrderRepository>(
      create: (context) => OrderRepositoryImp(
        context.read(),
      ),
    ),
    RepositoryProvider<SearchRepository>(
      create: (context) => SearchRepositoryImp(
        remoteDataSource: context.read(),
      ),
    ),
    RepositoryProvider<CartRepository>(
      create: (context) => CartRepositoryImp(
        remoteDataSource: context.read(),
        localDataSource: context.read(),
      ),
    ),
    RepositoryProvider<PaymentRepository>(
      create: (context) => PaymentRepositoryImp(
        context.read(),
        context.read(),
      ),
    ),
    RepositoryProvider<SubmitReviewRepository>(
      create: (context) => SubmitReviewRepositoryImp(
        remoteDataSource: context.read(),
      ),
    ),
    RepositoryProvider<ChatRepository>(
      create: (context) => ChatRepository(
        remoteDataSource: context.read(),
      ),
    ),
    RepositoryProvider<BecomeSellerRequestRepository>(
      create: (context) => BecomeSellerRequestRepositoryImpl(
        remoteDataSource: context.read(),
      ),
    ),
  ];

  static final blocProviders = <BlocProvider>[

    BlocProvider<AppSettingCubit>(
      create: (BuildContext context) => AppSettingCubit(
        context.read(),
      ),
    ),

    BlocProvider<LoginBloc>(
      create: (BuildContext context) => LoginBloc(
        profileRepository: context.read(),
        authRepository: context.read(),
      ),
    ),

    BlocProvider<HomeControllerCubit>(
      create: (BuildContext context) => HomeControllerCubit(
        context.read(),
      ),
    ),
    BlocProvider<ProductsCubit>(
      create: (BuildContext context) => ProductsCubit(
        context.read(),
      ),
    ),
    BlocProvider<CategoryCubit>(
      create: (BuildContext context) => CategoryCubit(
        context.read(),
      ),
    ),
    BlocProvider<SubCategoryCubit>(
      create: (BuildContext context) => SubCategoryCubit(
        context.read(),
      ),
    ),
    BlocProvider<ChildCubit>(
      create: (BuildContext context) => ChildCubit(
        context.read(),
      ),
    ),
    BlocProvider<AboutUsCubit>(
      create: (BuildContext context) => AboutUsCubit(
        context.read(),
      ),
    ),
    BlocProvider<FaqCubit>(
      create: (BuildContext context) => FaqCubit(
        context.read(),
      ),
    ),
    BlocProvider<PrivacyAndTermConditionCubit>(
      create: (BuildContext context) => PrivacyAndTermConditionCubit(
        context.read(),
      ),
    ),
    BlocProvider<ContactUsCubit>(
      create: (BuildContext context) => ContactUsCubit(
        context.read(),
      ),
    ),
    BlocProvider<ContactUsCubit>(
      create: (BuildContext context) => ContactUsCubit(
        context.read(),
      ),
    ),
    BlocProvider<ContactUsFormBloc>(
      create: (BuildContext context) => ContactUsFormBloc(
        context.read(),
      ),
    ),
    BlocProvider<ProductDetailsCubit>(
      create: (BuildContext context) => ProductDetailsCubit(
        repository:context.read(),
        loginBloc:context.read(),
        cartRepository:context.read(),
      ),
    ),
    BlocProvider<GuestCubit>(
      create: (BuildContext context) => GuestCubit(context.read(),
      ),
    ),
    BlocProvider<FlashCubit>(
      create: (BuildContext context) => FlashCubit(
        context.read(),
      ),
    ),

    BlocProvider<DeliveryManCubit>(
      create: (BuildContext context) => DeliveryManCubit(
        authRepository: context.read(),
      ),
    ),
    BlocProvider<SignUpBloc>(
      create: (BuildContext context) => SignUpBloc(
        context.read(),
      ),
    ),
    BlocProvider<ForgotPasswordCubit>(
      create: (BuildContext context) => ForgotPasswordCubit(
        context.read(),
      ),
    ),

    BlocProvider<ChangePasswordCubit>(
      create: (BuildContext context) => ChangePasswordCubit(
        loginBloc: context.read(),
        profileRepository: context.read(),
      ),
    ),
    BlocProvider<ProfileEditCubit>(
      create: (BuildContext context) => ProfileEditCubit(
        loginBloc: context.read(),
        profileRepository: context.read(),
      ),
      lazy: true,
    ),
    BlocProvider<CountryStateByIdCubit>(
      create: (BuildContext context) => CountryStateByIdCubit(
        loginBloc: context.read(),
        profileRepository: context.read(),
      ),
      lazy: true,
    ),
    BlocProvider<OrderCubit>(
      create: (BuildContext context) => OrderCubit(
        loginBloc: context.read(),
        orderRepository: context.read(),
      ),
      lazy: true,
    ),
    BlocProvider<OrderTrackingCubit>(
      create: (BuildContext context) => OrderTrackingCubit(
        loginBloc: context.read(),
        orderRepository: context.read(),
      ),
    ),
    BlocProvider<AddressCubit>(
      create: (BuildContext context) => AddressCubit(
        loginBloc: context.read(),
        repository: context.read(),
      ),
      lazy: true,
    ),
    BlocProvider<WishListCubit>(
      create: (BuildContext context) => WishListCubit(
        loginBloc: context.read(),
        profileRepository: context.read(),
      ),
    ),
    BlocProvider<SearchBloc>(
      create: (context) => SearchBloc(
        searchRepository: context.read(),
      ),
    ),
    BlocProvider<CartCubit>(
      create: (context) => CartCubit(
        cartRepository: context.read(),
        loginBloc: context.read(),
      ),
    ),
    BlocProvider<PaymentCubit>(
      create: (context) => PaymentCubit(
        paymentRepository: context.read(),
        loginBloc: context.read(),
      ),
    ),
    BlocProvider<SubmitReviewCubit>(
      create: (context) => SubmitReviewCubit(
        repository: context.read(),
        loginBloc: context.read(),
      ),
    ),
    BlocProvider<AddToCartCubit>(
      create: (context) => AddToCartCubit(
        cartRepository: context.read(),
        loginBloc: context.read(),
      ),
    ),
    BlocProvider<CheckoutCubit>(
      create: (context) => CheckoutCubit(
        cartRepository: context.read(),
        loginBloc: context.read(),
      ),
    ),
    BlocProvider<CashOnPaymentCubit>(
      create: (context) => CashOnPaymentCubit(
        paymentRepository: context.read(),
        loginBloc: context.read(),
      ),
    ),
    BlocProvider<BankCubit>(
      create: (context) => BankCubit(
        paymentRepository: context.read(),
        loginBloc: context.read(),
      ),
    ),
    BlocProvider<IyzicoCubit>(
      create: (context) => IyzicoCubit(
        paymentRepository: context.read(),
        loginBloc: context.read(),
      ),
    ),
    BlocProvider<DeliveryChargesCubit>(
      create: (context) => DeliveryChargesCubit(),
    ),
    BlocProvider<UserProfileInfoCubit>(
      create: (context) => UserProfileInfoCubit(
        profileRepository: context.read(),
        loginBloc: context.read(),
      ),
    ),
    BlocProvider<EditAddressCubit>(
      create: (context) => EditAddressCubit(
        profileRepository: context.read(),
        loginBloc: context.read(),
      ),
    ),
    BlocProvider<DeleteUserCubit>(
      create: (context) => DeleteUserCubit(
        profileRepository: context.read(),
        loginBloc: context.read(),
      ),
    ),
    BlocProvider<ChatBloc>(
      create: (context) => ChatBloc(
        chatRepository: context.read(),
        loginBloc: context.read(),
      ),
    ),
    BlocProvider<InboxCubit>(
      create: (context) => InboxCubit(),
    ),
    BlocProvider<CurrencyCubit>(
      create: (context) => CurrencyCubit(),
    ),
    BlocProvider<CheckoutInfoCubit>(
      create: (context) => CheckoutInfoCubit(),
    ),
    BlocProvider<BecomeSellerCubit>(
      create: (context) => BecomeSellerCubit(
        sellerRequestRepository: context.read(),
        loginBloc: context.read(),
      ),
    ),
    BlocProvider<NotificationCubit>(
      create: (context) => NotificationCubit(
        repository: context.read(),
        loginBloc: context.read(),
      ),
    ),
    BlocProvider<InternetStatusBloc>(create: (context) => InternetStatusBloc()),
    BlocProvider<MapCubit>(
      create: (context) => MapCubit(
        loginBloc: context.read(),
        orderRepository: context.read(),
      ),
    ),
    BlocProvider<TranslateCubit>(
      create: (BuildContext context) => TranslateCubit(),
    ),
  ];
}
