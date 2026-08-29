import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../../core/router_name.dart';
import '../../utils/constants.dart';
import '../../utils/utils.dart';
import '../../widgets/rounded_app_bar.dart';
import '../cart/controllers/cart/cart_cubit.dart';
import '../order/controllers/order/order_cubit.dart';
import '../product_details/controller/cubit/product_details_cubit.dart';
import 'model/iyzico_payment_args.dart';

class IyzicoPaymentScreen extends StatefulWidget {
  const IyzicoPaymentScreen({super.key, required this.args});

  final IyzicoPaymentArgs args;

  @override
  State<IyzicoPaymentScreen> createState() => _IyzicoPaymentScreenState();
}

class _IyzicoPaymentScreenState extends State<IyzicoPaymentScreen> {
  double _progress = 0;
  bool _isLoading = true;
  bool _canRedirect = true;
  late final WebViewController _controller;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (progress) {
            setState(() {
              _progress = progress / 100;
            });
          },
          onPageStarted: _handleRedirect,
          onPageFinished: (url) {
            setState(() => _isLoading = false);
            _handleRedirect(url);
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.args.checkoutUrl));
  }

  void _handleRedirect(String url) {
    if (!_canRedirect) return;

    final isSuccess = url.contains('payment_status=success');
    final isFailed = url.contains('payment-failed') ||
        url.contains('payment_status=failed') ||
        url.contains('payment_status=cancel');

    if (isSuccess) {
      _canRedirect = false;
      _onPaymentSuccess();
    } else if (isFailed) {
      _canRedirect = false;
      Utils.errorSnackBar(context, 'Ödeme tamamlanamadı.');
      Navigator.pop(context);
    }
  }

  Future<void> _onPaymentSuccess() async {
    if (!mounted) return;
    Utils.showSnackBar(context, 'Ödemeniz başarıyla alındı.');

    await context.read<CartCubit>().getCartProducts();

    final guestProducts = context.read<ProductDetailsCubit>().savedProduct;
    if (guestProducts.isNotEmpty) {
      context.read<ProductDetailsCubit>().clearGuestProduct();
    }

    final orderId = widget.args.orderId;
    if (orderId.isNotEmpty) {
      context.read<OrderCubit>().tempTrackOrderId(orderId);
      if (!mounted) return;
      Navigator.pushNamedAndRemoveUntil(
        context,
        RouteNames.singleOrderScreen,
        (route) => route.settings.name == RouteNames.mainPage,
      );
      return;
    }

    if (!mounted) return;
    Navigator.pushNamedAndRemoveUntil(
      context,
      RouteNames.orderScreen,
      (route) => route.settings.name == RouteNames.mainPage,
      arguments: true,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: whiteColor,
      appBar: RoundedAppBar(titleText: 'Kredi / Banka Kartı'),
      body: Column(
        children: [
          if (_isLoading)
            LinearProgressIndicator(
              value: _progress > 0 ? _progress : null,
              color: deepGreenColor,
              backgroundColor: borderColor,
            ),
          Expanded(child: WebViewWidget(controller: _controller)),
        ],
      ),
    );
  }
}
