import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../core/router_name.dart';
import '../../utils/language_string.dart';
import '../../utils/utils.dart';
import '../../widgets/capitalized_word.dart';
import '../../widgets/rounded_app_bar.dart';
import '../cart/model/checkout_response_model.dart';
import '../main_page/main_controller.dart';
import '../order/controllers/order/order_cubit.dart';
import 'controllers/bank/bank_cubit.dart';
import 'controllers/iyzico/iyzico_cubit.dart';
import 'model/iyzico_payment_args.dart';
import 'widgets/marketplace_payment_methods.dart';

class PlaceOrderScreen extends StatefulWidget {
  const PlaceOrderScreen({super.key});

  @override
  State<PlaceOrderScreen> createState() => _PlaceOrderScreenState();
}

class _PlaceOrderScreenState extends State<PlaceOrderScreen> {
  Map<String, dynamic>? _body;
  CheckoutResponseModel? _checkoutData;

  void _onBankSuccess(BankLoadedState state) {
    FocusManager.instance.primaryFocus?.unfocus();
    Utils.closeDialog(context);

    final orderCubit = context.read<OrderCubit>();
    final navigator = Navigator.of(context, rootNavigator: true);
    final orderId = state.orderId;

    Utils.showSnackBar(context, state.message);
    if (orderId.isNotEmpty) {
      orderCubit.tempTrackOrderId(orderId);
    }

    // Navigator kilitliyken pop/push yapma; sepeti MainPage yenilesin
    WidgetsBinding.instance.addPostFrameCallback((_) {
      navigator.pushNamedAndRemoveUntil(
        RouteNames.mainPage,
        (route) => false,
      );
      WidgetsBinding.instance.addPostFrameCallback((_) {
        MainController().naveListener.sink.add(2);
        if (orderId.isNotEmpty) {
          navigator.pushNamed(RouteNames.singleOrderScreen);
        }
      });
    });
  }

  @override
  Widget build(BuildContext context) {
    final route = ModalRoute.of(context)?.settings.arguments;
    if (route is Map<String, dynamic>) {
      _body = Map<String, dynamic>.from(route['body'] as Map<String, dynamic>);
      _checkoutData = route['payment_status'] as CheckoutResponseModel;
    }

    if (_body == null || _checkoutData == null) {
      return Scaffold(
        appBar: RoundedAppBar(
          titleText: Language.selectPaymentOption.capitalizeByWord(),
        ),
        body: Center(child: Text(Language.paymentLoadFailed)),
      );
    }

    final body = _body!;
    final checkoutData = _checkoutData!;
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
              _onBankSuccess(state);
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
            baseBody: body,
            isGuest: false,
          ),
        ),
      ),
    );
  }
}
