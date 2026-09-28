import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:http/http.dart' as http;
import 'package:shop_o/widgets/fetch_error_text.dart';
import 'package:shop_o/widgets/loading_widget.dart';
import '../../widgets/custom_text.dart';
import 'package:sliding_up_panel/sliding_up_panel.dart';

import '../../widgets/app_empty_state.dart';
import '../../widgets/page_refresh.dart';
import '/modules/animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '/modules/cart/model/cart_calculation_model.dart';
import '/widgets/capitalized_word.dart';
import '../../core/remote_urls.dart';
import '../../core/router_name.dart';
import '../../utils/constants.dart';
import '../../utils/k_images.dart';
import '../../utils/language_string.dart';
import '../../utils/utils.dart';
import '../../widgets/confirm_dialog.dart';
import '../../widgets/please_signin_widget.dart';
import '../../widgets/rounded_app_bar.dart';
import '../category/controller/cubit/category_cubit.dart';
import 'component/add_to_cart_component.dart';
import 'component/cart_installment_warning.dart';
import 'component/panel_widget.dart';
import 'controllers/cart/cart_cubit.dart';
import 'model/cart_response_model.dart';

class CartScreen extends StatefulWidget {
  const CartScreen({super.key});

  @override
  State<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  @override
  void initState() {
    super.initState();
    loadCart();
  }

  loadCart() {
    Future.microtask(() => context.read<CartCubit>().getCartProducts());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: RoundedAppBar(titleText: Language.cart.capitalizeByWord()),
      body: PageRefresh(
        onRefresh: () async {
          context.read<CartCubit>().getCartProducts();
        },
        child: BlocConsumer<CartCubit, CartState>(
          listenWhen: (previous, current) =>
              current is CartStateDecIncrementLoading ||
              previous is CartStateDecIncrementLoading ||
              current is CartStateDecIncError ||
              current is CartStateRemove ||
              current is CartDecIncState,
          listener: (_, state) {
            if (state is CartStateDecIncrementLoading) {
              Utils.loadingDialog(context);
            } else {
              Utils.closeDialog(context);
              if (state is CartStateDecIncError) {
                if (!state.message.contains("cached")) {
                  Utils.errorSnackBar(context, state.message);
                }
              }
              if (state is CartStateRemove) {
                Utils.showSnackBar(context, state.message);
              }
              if (state is CartDecIncState) {
                Utils.showSnackBar(context, state.message);
              }
            }
          },
          builder: (context, state) {
            if (state is CartStateLoading) {
              return const LoadingWidget();
            } else if (state is CartStateError) {
              if (state.statusCode == 401) {
                return const PleaseSigninWidget();
              }
              if (state.statusCode == 503) {
                return const _LoadedWidget();
              } else {
                return FetchErrorText(text: state.message);
              }
            }
            return const _LoadedWidget();
          },
        ),
      ),
    );
  }
}

class _LoadedWidget extends StatefulWidget {
  const _LoadedWidget();

  @override
  State<_LoadedWidget> createState() => _LoadedWidgetState();
}

class _LoadedWidgetState extends State<_LoadedWidget> {
  final panelController = PanelController();
  Map<String, dynamic> map = <String, dynamic>{};

  final double height = 120;
  late double subTotal;
  late double total;
  late double variantPrice;
  late String coupon;

  CartResponseModel? cartResponseModel;
  CartCalculation? cartCalculation;
  Map<int, Map<String, dynamic>> _sellerShipping = {};
  double _shippingTotal = 0;
  int _shippingFetchToken = 0;

  @override
  void initState() {
    super.initState();
    cartResponseModel = context.read<CartCubit>().cartResponseModel;
    calculate();
    _fetchSellerShipping();
  }

  calculate() {
    subTotal = 0.0;
    total = 0.0;
    variantPrice = 0.0;
    coupon = "";
    if (cartResponseModel!.cartProducts.isEmpty) {
      cartCalculation = const CartCalculation(
        subTotal: 0.0,
        coupon: "",
        total: 0.0,
      );
      _sellerShipping = {};
      _shippingTotal = 0;
    } else {
      cartResponseModel!.cartProducts.map((e) {
        subTotal += Utils.cartProductPrice(context, e) * e.qty.toDouble();
      }).toList();
      total = subTotal;
      context.read<CartCubit>().getCoupon();

      if (context.read<CartCubit>().couponResponseModel != null) {
        coupon =
            context.read<CartCubit>().couponResponseModel!.discount.toString();
        total = total - double.parse(coupon);
      }

      cartCalculation = CartCalculation(
        subTotal: subTotal,
        coupon: coupon,
        total: total,
      );

      context.read<CartCubit>().saveCartCalculation(cartCalculation!);
    }
  }

  Future<void> _fetchSellerShipping() async {
    final products = cartResponseModel?.cartProducts ?? [];
    if (products.isEmpty) {
      if (!mounted) return;
      setState(() {
        _sellerShipping = {};
        _shippingTotal = 0;
      });
      return;
    }

    final token = ++_shippingFetchToken;
    final items = products
        .map((e) => {
              'product_id': e.product.id,
              'qty': e.qty,
            })
        .toList();

    try {
      final res = await http.post(
        Uri.parse(RemoteUrls.cartShippingPreview),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({'items': items}),
      );
      if (!mounted || token != _shippingFetchToken) return;
      if (res.statusCode < 200 || res.statusCode >= 300) return;
      final data = jsonDecode(res.body);
      if (data is! Map) return;
      final groups = data['groups'];
      final map = <int, Map<String, dynamic>>{};
      if (groups is List) {
        for (final g in groups.whereType<Map>()) {
          final m = Map<String, dynamic>.from(g);
          final vid = int.tryParse('${m['vendor_id'] ?? 0}') ?? 0;
          map[vid] = m;
        }
      }
      setState(() {
        _sellerShipping = map;
        _shippingTotal =
            double.tryParse('${data['total_shipping_fee'] ?? 0}') ?? 0;
      });
    } catch (_) {
      /* sessiz — başlıklar fallback gösterir */
    }
  }

  @override
  Widget build(BuildContext context) {
    if (cartResponseModel != null &&
        cartResponseModel!.cartProducts.isNotEmpty) {
      return BlocBuilder<CartCubit, CartState>(
        builder: (context, state) {
          return SlidingUpPanel(
            controller: panelController,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
            panelBuilder: (sc) => PanelComponent(
              controller: sc,
              cartResponseModel: cartResponseModel!,
              cartCalculation: cartCalculation!,
            ),
            minHeight: height,
            maxHeight: 350,
            backdropEnabled: true,
            backdropTapClosesPanel: true,
            parallaxEnabled: true,
            backdropOpacity: .0,
            collapsed: PanelCollapseComponent(
              height: height,
              cartResponseModel: cartResponseModel!,
              totalPrice: cartCalculation!.total,
            ),
            body: _buildBody(),
          );
        },
      );
    } else {
      return AppEmptyState(
        icon: Icons.shopping_cart_outlined,
        title: Language.emptyCartTitle,
        subtitle: Language.emptyCartHint,
      );
    }
  }

  Widget _buildBody() {
    final appSetting = context.read<AppSettingCubit>();
    return CustomScrollView(
      slivers: [
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 20, 20, 14),
            child: Row(
              children: [
                const Icon(Icons.shopping_cart_rounded, color: redColor),
                const SizedBox(width: 10),
                Expanded(
                  child: CustomText(
                      text: _getText(),
                      fontSize: 16,
                      fontWeight: FontWeight.w600),
                ),
                if (cartResponseModel != null &&
                    cartResponseModel!.cartProducts.isNotEmpty)
                  TextButton(
                    onPressed: () => _confirmClearCart(context),
                    style: TextButton.styleFrom(
                      foregroundColor: redColor,
                      padding: const EdgeInsets.symmetric(horizontal: 8),
                    ),
                    child: CustomText(
                      text: Language.clearAllCart,
                      color: redColor,
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                  ),
              ],
            ),
          ),
        ),
        if (cartResponseModel != null &&
            cartResponseModel!.cartProducts.isNotEmpty) ...[
          ..._buildSellerGroupedCart(appSetting),
          const SliverToBoxAdapter(child: CartInstallmentWarning()),
        ] else ...[
          SliverFillRemaining(
            hasScrollBody: false,
            child: AppEmptyState(
              icon: Icons.shopping_cart_outlined,
              title: Language.emptyCartTitle,
              subtitle: Language.emptyCartHint,
            ),
          ),
        ],
        const SliverToBoxAdapter(child: SizedBox(height: 245)),
      ],
    );
  }

  String _getText() {
    final length = cartResponseModel!.cartProducts.length;
    if (length > 1) {
      return '$length ${Language.products.capitalizeByWord()}';
    } else {
      return '$length ${Language.product.capitalizeByWord()}';
    }
  }

  List<Widget> _buildSellerGroupedCart(AppSettingCubit appSetting) {
    final products = cartResponseModel!.cartProducts;
    final Map<int, List<dynamic>> grouped = {};
    for (final p in products) {
      final vid = p.product.vendorId;
      grouped.putIfAbsent(vid, () => []).add(p);
    }

    final widgets = <Widget>[];

    if (_shippingTotal > 0 ||
        _sellerShipping.values.any((g) => g['is_free_shipping'] == true)) {
      widgets.add(
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 8),
            child: _CartShippingHint(groups: _sellerShipping.values.toList()),
          ),
        ),
      );
    }

    grouped.forEach((vendorId, items) {
      final group = _sellerShipping[vendorId];
      final shopName = (group?['shop_name'] as String?)?.trim().isNotEmpty == true
          ? '${group!['shop_name']}'
          : (vendorId > 0 ? 'Satıcı #$vendorId' : 'Satıcı');
      final shopSlug = '${group?['slug'] ?? ''}'.trim();
      final isFree = group?['is_free_shipping'] == true ||
          (double.tryParse('${group?['shipping_fee'] ?? ''}') ?? -1) == 0;
      final fee = double.tryParse('${group?['shipping_fee'] ?? 0}') ?? 0;
      final untilFree =
          double.tryParse('${group?['amount_until_free'] ?? ''}');

      String feeLabel;
      if (group == null) {
        feeLabel = 'Kargo hesaplanıyor…';
      } else if (isFree) {
        feeLabel = 'Ücretsiz Kargo!';
      } else {
        feeLabel = 'Kargo: ${Utils.formatPrice(fee, context)}';
      }

      void openSellerShop() {
        if (shopSlug.isEmpty) return;
        context.read<CategoryCubit>().sellerTitleSlug(shopName, shopSlug);
        Navigator.pushNamed(context, RouteNames.sellerScreen);
      }

      widgets.add(
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 4),
            child: Material(
              color: Colors.transparent,
              child: InkWell(
                onTap: shopSlug.isNotEmpty ? openSellerShop : null,
                borderRadius: BorderRadius.circular(10),
                child: Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF4F6F7),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0x1A04334A)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.storefront_outlined,
                              size: 18, color: Color(0xFF04334A)),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              shopName,
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w700,
                                color: const Color(0xFF04334A),
                                decoration: shopSlug.isNotEmpty
                                    ? TextDecoration.underline
                                    : TextDecoration.none,
                                decorationColor:
                                    const Color(0xFF04334A).withValues(alpha: 0.35),
                              ),
                            ),
                          ),
                          if (shopSlug.isNotEmpty)
                            const Padding(
                              padding: EdgeInsets.only(right: 6),
                              child: Icon(
                                Icons.chevron_right,
                                size: 18,
                                color: Color(0x9904334A),
                              ),
                            ),
                          Text(
                            feeLabel,
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: isFree
                                  ? const Color(0xFF1B7A3D)
                                  : const Color(0x9904334A),
                            ),
                          ),
                        ],
                      ),
                      if (!isFree &&
                          untilFree != null &&
                          untilFree > 0) ...[
                        const SizedBox(height: 6),
                        Text(
                          'Ücretsiz kargo için ${Utils.formatPrice(untilFree, context)} kaldı',
                          style: const TextStyle(
                            fontSize: 11,
                            color: Color(0xFF1B7A3D),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      );
      widgets.add(
        SliverList(
          delegate: SliverChildBuilderDelegate(
            (context, index) {
              final product = items[index];
              return AddToCartComponent(
                product: product,
                onChange: (int id) {
                  cartResponseModel!.cartProducts
                      .removeWhere((element) => element.id == id);
                  setState(() {
                    calculate();
                  });
                  _fetchSellerShipping();
                },
                appSetting: appSetting,
              );
            },
            childCount: items.length,
            addAutomaticKeepAlives: true,
          ),
        ),
      );
    });
    return widgets;
  }

  Future<void> _confirmClearCart(BuildContext context) async {
    await showDialog(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => ConfirmDialog(
        icon: Kimages.deleteIcon2,
        message: Language.wishToClearCart,
        confirmText: Language.yesRemove,
        cancelText: Language.no,
        onTap: () async {
          Navigator.of(dialogContext).pop();
          final result = await context.read<CartCubit>().clearCart();
          result.fold(
            (failure) {},
            (success) {
              if (!mounted) return;
              setState(() {
                calculate();
              });
              _fetchSellerShipping();
            },
          );
        },
      ),
    );
  }
}

class _CartShippingHint extends StatelessWidget {
  const _CartShippingHint({required this.groups});

  final List<Map<String, dynamic>> groups;

  @override
  Widget build(BuildContext context) {
    final pending = groups.where((g) {
      final free = g['is_free_shipping'] == true;
      final until = double.tryParse('${g['amount_until_free'] ?? ''}');
      return !free && until != null && until > 0;
    }).toList();

    if (pending.isEmpty) {
      final allFree = groups.isNotEmpty &&
          groups.every((g) =>
              g['is_free_shipping'] == true ||
              (double.tryParse('${g['shipping_fee'] ?? -1}') ?? -1) == 0);
      if (!allFree) return const SizedBox.shrink();
      return Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: const Color(0xFFE8F8EE),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: const Color(0xFFB7E4C7)),
        ),
        child: const Text(
          'Sepetinizdeki satıcılar için kargo ücretsiz veya dahil!',
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: Color(0xFF1B7A3D),
          ),
        ),
      );
    }

    pending.sort((a, b) {
      final aa = double.tryParse('${a['amount_until_free']}') ?? 0;
      final bb = double.tryParse('${b['amount_until_free']}') ?? 0;
      return aa.compareTo(bb);
    });
    final nearest = pending.first;
    final shop = '${nearest['shop_name'] ?? 'Satıcı'}';
    final until =
        double.tryParse('${nearest['amount_until_free']}') ?? 0;

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFE8F8EE),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFB7E4C7)),
      ),
      child: Text(
        '$shop satıcısında ücretsiz kargo için ${Utils.formatPrice(until, context)} kaldı',
        style: const TextStyle(
          fontSize: 13,
          fontWeight: FontWeight.w600,
          color: Color(0xFF1B7A3D),
        ),
      ),
    );
  }
}
