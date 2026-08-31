import 'package:flutter/services.dart';

import '/state_packages_names.dart';
import '../../core/push/push_notification_service.dart';
import '../../utils/k_images.dart';
import '../../widgets/confirm_dialog.dart';
import '../authentication/controller/login/login_bloc.dart';
import '../profile/buyer_personalization_onboarding_screen.dart';
import '../profile/controllers/updated_info/updated_info_cubit.dart';
import '../profile/model/user_info/user_updated_info.dart';
import '../../utils/utils.dart';
import '../home/home_screen.dart';
import '../order/order_screen.dart';
import '../profile/profile_screen.dart';
import '../salon_hub/salon_hub_screen.dart';
import 'component/bottom_navigation_bar.dart';
import 'main_controller.dart';

class MainPage extends StatefulWidget {
  const MainPage({super.key});

  @override
  State<MainPage> createState() => _MainPageState();
}

class _MainPageState extends State<MainPage> {
  final _homeController = MainController();
  bool _personalizationPromptShown = false;

  late List<Widget> pageList;

  @override
  void initState() {
    super.initState();

    pageList = [
      const SalonHubScreen(),
      const HomeScreen(),
      const OrderScreen(isFromPayment: false),
      const ProfileScreen(),
    ];

    context.read<ProductDetailsCubit>().getGuestSavedProduct();

    context.read<CountryStateByIdCubit>().countryListLoaded();
    context.read<UserProfileInfoCubit>().getUserProfileInfo();
    context.read<CartCubit>().getCartProducts();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      PushNotificationService.instance.registerDeviceToken(context);
      _checkPersonalizationFromCache();
    });
  }

  void _checkPersonalizationFromCache() {
    if (!mounted) return;
    final info = context.read<UserProfileInfoCubit>().updatedInfo;
    if (info != null) {
      _maybeShowBuyerPersonalization(info);
    }
  }


  void _maybeShowBuyerPersonalization(UserProfileInfo info) {
    if (_personalizationPromptShown) return;
    if (!Utils.isLoggedIn(context)) return;
    if (Utils.isSeller(context)) return;
    if (!info.buyerPersonalization.shouldPrompt) return;

    _personalizationPromptShown = true;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      Navigator.of(context).push(
        MaterialPageRoute(
          fullscreenDialog: true,
          builder: (_) => BuyerPersonalizationOnboardingScreen(
            initialData: info.buyerPersonalization,
          ),
        ),
      );
    });
  }


  @override
  Widget build(BuildContext context) {
    // context.read<CartCubit>().getCartProducts();
    // context.read<WishListCubit>().getWishList();
    return MultiBlocListener(
      listeners: [
        BlocListener<UserProfileInfoCubit, UserProfileInfoState>(
          listener: (context, state) {
            if (state is UpdatedLoaded) {
              _maybeShowBuyerPersonalization(state.updatedInfo);
            }
          },
        ),
        BlocListener<LoginBloc, LoginModelState>(
          listenWhen: (previous, current) =>
              current.state is LoginStateLoaded &&
              previous.state is! LoginStateLoaded,
          listener: (context, state) {
            context.read<UserProfileInfoCubit>().getUserProfileInfo();
          },
        ),
      ],
      child: WillPopScope(
      onWillPop: () async {
        showDialog(
          context: context,
          barrierDismissible: false,
          builder: (context) => ConfirmDialog(
            icon: Kimages.logout2,
            message: 'Are you sure, you\nwant to EXIT?',
            confirmText: 'Yes, Exit',
            onTap: () => SystemNavigator.pop(),
          ),
        );
        return true;
      },
      child: Scaffold(
        extendBody: true,
        // key: _homeController.scaffoldKey,
        // drawer: const DrawerWidget(),
        body: StreamBuilder<int>(
          initialData: 0,
          stream: _homeController.naveListener.stream,
          builder: (context, AsyncSnapshot<int> snapshot) {
            int index = snapshot.data ?? 0;
            return pageList[index];
          },
        ),
        bottomNavigationBar: const MyBottomNavigationBar(),
      ),
      ),
    );
  }
}
