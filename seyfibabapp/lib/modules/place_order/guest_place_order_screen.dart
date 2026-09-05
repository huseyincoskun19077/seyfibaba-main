import 'dart:convert';
import 'dart:developer';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../core/router_name.dart';
import '../../utils/language_string.dart';
import '../../utils/utils.dart';
import '../../widgets/capitalized_word.dart';
import '../../widgets/custom_text.dart';
import '../../widgets/rounded_app_bar.dart';
import '../cart/controllers/checkout/checkout_cubit.dart';
import '../cart/model/checkout_response_model.dart';
import '../product_details/controller/cubit/product_details_cubit.dart';
import '../profile/controllers/address/address_cubit.dart';
import 'controllers/bank/bank_cubit.dart';
import 'controllers/iyzico/iyzico_cubit.dart';
import 'model/iyzico_payment_args.dart';
import 'widgets/marketplace_payment_methods.dart';

class GuestPlaceOrderScreen extends StatefulWidget {
  const GuestPlaceOrderScreen({super.key});

  @override
  State<GuestPlaceOrderScreen> createState() => _GuestPlaceOrderScreenState();
}

class _GuestPlaceOrderScreenState extends State<GuestPlaceOrderScreen> {
  late CheckoutCubit checkCubit;
  late ProductDetailsCubit detailCubit;
  late Map<String, dynamic> guestBody;
  late CheckoutResponseModel checkoutData;
  bool _accessDenied = false;

  @override
  void initState() {
    super.initState();
    if (!Utils.isLoggedIn(context)) {
      _accessDenied = true;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        Utils.errorSnackBar(context, Language.guestCheckoutDisabled);
        Navigator.pushNamedAndRemoveUntil(
          context,
          RouteNames.authenticationScreen,
          (route) => route.isFirst,
        );
      });
      return;
    }
    _init();
  }

  void _init() {
    checkCubit = context.read<CheckoutCubit>();
    detailCubit = context.read<ProductDetailsCubit>();

    final cart = detailCubit.savedProduct.map((e) => e.toMap()).toList();
    guestBody = {
      'cart_products': cart,
      'address': context.read<AddressCubit>().state.toGuestMap(),
      ...checkCubit.state.checkoutBody?.toMap() ?? {},
    };

    checkoutData = checkCubit.guestInfo ?? checkCubit.checkoutResponseModel!;

    log('guest-payment-body ${jsonEncode(guestBody)}');
  }

  @override
  Widget build(BuildContext context) {
    if (_accessDenied) {
      return Scaffold(
        appBar: RoundedAppBar(
          titleText: Language.selectPaymentOption.capitalizeByWord(),
        ),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: CustomText(
              text:
                  'Misafir sipariş geçici olarak kapalı. Giriş yapmanız gerekiyor.',
              textAlign: TextAlign.center,
            ),
          ),
        ),
      );
    }

    return MultiBlocListener(
      listeners: [
        BlocListener<IyzicoCubit, IyzicoState>(
          listener: (context, state) {
            if (state is IyzicoLoadingState) {
              Utils.loadingDialog(context);
            } else {
              Utils.closeDialog(context);
              if (state is IyzicoLoadedState) {
                Navigator.pushNamed(
                  context,
                  RouteNames.iyzicoPaymentScreen,
                  arguments: IyzicoPaymentArgs(
                    checkoutUrl: state.checkoutUrl,
                    orderId: state.orderId,
                  ),
                );
              } else if (state is IyzicoErrorState) {
                Utils.errorSnackBar(context, state.message);
              }
            }
          },
        ),
        BlocListener<BankCubit, BankState>(
          listener: (context, state) {
            if (state is BankStateLoading) {
              Utils.loadingDialog(context);
            } else if (state is BankLoadedState) {
              FocusManager.instance.primaryFocus?.unfocus();
              Utils.closeDialog(context);
              Utils.showSnackBar(context, state.message);
              final navigator = Navigator.of(context, rootNavigator: true);
              WidgetsBinding.instance.addPostFrameCallback((_) {
                navigator.pushNamedAndRemoveUntil(
                  RouteNames.mainPage,
                  (route) => false,
                );
                Future.delayed(
                  const Duration(milliseconds: 1200),
                  detailCubit.clearGuestProduct,
                );
              });
            } else {
              Utils.closeDialog(context);
              if (state is BankStateError) {
                Utils.errorSnackBar(context, state.message);
              } else if (state is BankPaymentFormError) {
                if (state.errors.tnxInfo.isNotEmpty) {
                  Utils.errorSnackBar(context, state.errors.tnxInfo.first);
                }
              }
            }
          },
        ),
      ],
      child: Scaffold(
        appBar: RoundedAppBar(
          titleText: Language.selectPaymentOption.capitalizeByWord(),
        ),
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: MarketplacePaymentMethods(
            checkoutData: checkoutData,
            baseBody: const {},
            guestBody: guestBody,
            isGuest: true,
          ),
        ),
      ),
    );
  }
}
