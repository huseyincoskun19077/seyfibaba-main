import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';
import '/widgets/capitalized_word.dart';
import 'package:sliding_up_panel/sliding_up_panel.dart';

import '../../core/router_name.dart';
import '../../utils/constants.dart';
import '../../utils/language_string.dart';
import '../../utils/language_string.dart';
import '../../utils/utils.dart';
import '../../widgets/app_empty_state.dart';
import '../../widgets/fetch_error_text.dart';
import '../../widgets/page_refresh.dart';
import '../../widgets/rounded_app_bar.dart';
import '../product_details/controller/cubit/details_state_model.dart';
import '../product_details/controller/cubit/product_details_cubit.dart';
import 'component/guest_cart_component.dart';
import 'component/guest_panel_widget.dart';
import 'controllers/checkout/checkout_cubit.dart';
import 'model/guest_cart_product.dart';

class GuestCartScreen extends StatefulWidget {
  const GuestCartScreen({super.key});

  @override
  State<GuestCartScreen> createState() => _GuestCartScreenState();
}

class _GuestCartScreenState extends State<GuestCartScreen> {
  late ProductDetailsCubit detailCubit;
  late CheckoutCubit checkCubit;
  late String vendorId;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      if (Utils.isLoggedIn(context)) {
        Navigator.pushReplacementNamed(context, RouteNames.cartScreen);
        return;
      }
      _init();
    });
  }

  _init() {
    detailCubit = context.read<ProductDetailsCubit>();
    checkCubit = context.read<CheckoutCubit>();
    if (detailCubit.state.vendorIds.isNotEmpty) {
      vendorId = detailCubit.state.vendorIds.first.toString();
    } else {
      vendorId = '';
    }

    if (detailCubit.savedProduct.isNotEmpty) {
      Future.microtask(() => detailCubit.getGuestSavedProduct());
      detailCubit.cartCalculation(context);
      if (!Utils.isLoggedIn(context)) {
        Future.microtask(() => checkCubit.loadCheckoutContext(vendorId: vendorId));
      } else {
        Future.microtask(() {
          final coupon = detailCubit.couponResponse?.code ?? '';
          checkCubit.loadCheckoutContext(vendorId: vendorId, coupon: coupon);
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: RoundedAppBar(titleText: Language.cart.capitalizeByWord()),
      body: PageRefresh(
        onRefresh: () async {
          detailCubit.getGuestSavedProduct();
        },
        child: BlocConsumer<ProductDetailsCubit, DetailsStateModel>(
          listener: (context, states) {
            final state = states.detailsState;
            if (state is GuestUpdateProduct ||
                state is GuestSaveProductDeleted) {
              //   debugPrint('update-recall');
              // detailCubit..initState()..getGuestSavedProduct()..cartCalculation(context);
              detailCubit
                ..getGuestSavedProduct()
                ..cartCalculation(context);
              if (detailCubit.couponResponse != null) {
                detailCubit.couponCalculate();
              }
            }
          },
          builder: (context, states) {
            final state = states.detailsState;
            // if (state is GuestProductLoading) {
            //   return const LoadingWidget();
            // }
            if (state is GuestProductError) {
              return FetchErrorText(text: state.message);
            }
            if (state is GuestAllSavedProduct) {
              // return LoadedGuestProduct(product: detailCubit.savedProduct);
              return LoadedGuestProduct(product: state.products);
            }
            if (detailCubit.savedProduct.isNotEmpty) {
              return LoadedGuestProduct(product: detailCubit.savedProduct);
            } else {
              return AppEmptyState(
                icon: Icons.shopping_cart_outlined,
                title: Language.emptyCartTitle,
                subtitle: Language.loginRequiredForCheckout,
              );
            }
          },
        ),
      ),
    );
  }
}

class LoadedGuestProduct extends StatefulWidget {
  const LoadedGuestProduct({super.key, required this.product});
  final List<GustCartProduct> product;

  @override
  State<LoadedGuestProduct> createState() => _LoadedGuestProductState();
}

class _LoadedGuestProductState extends State<LoadedGuestProduct> {
  final panelController = PanelController();
  late ProductDetailsCubit detailCubit;
  final double height = 120;

  double subTotal = 0.0;

  @override
  void initState() {
    _init();
    super.initState();
  }

  _init() {
    detailCubit = context.read<ProductDetailsCubit>();
    widget.product.map((e) {
      subTotal += e.product?.weight ?? 0.0;
    }).toList();
    //debugPrint('total-weight $subTotal');
  }

  @override
  Widget build(BuildContext context) {
    if (widget.product.isNotEmpty) {
      return SlidingUpPanel(
        controller: panelController,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
        panelBuilder: (sc) => const GuestPanelComponent(),
        minHeight: height,
        maxHeight: Utils.isLoggedIn(context) ? 350.0 : 240.0,
        backdropEnabled: true,
        backdropTapClosesPanel: true,
        parallaxEnabled: true,
        backdropOpacity: 0.0,
        collapsed: GuestPanelCollapseComponent(height: height),
        body: GuestCartBody(product: widget.product),
      );
    } else {
      return AppEmptyState(
        icon: Icons.shopping_cart_outlined,
        title: Language.emptyCartTitle,
        subtitle: Language.emptyCartHint,
      );
    }
  }
}

class GuestCartBody extends StatelessWidget {
  const GuestCartBody({super.key, required this.product});
  final List<GustCartProduct> product;

  @override
  Widget build(BuildContext context) {
    return CustomScrollView(
      slivers: [
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 20, 20, 14),
            child: Row(
              children: [
                const Icon(Icons.shopping_cart_rounded, color: redColor),
                const SizedBox(width: 10),
                Text(
                  _getText(),
                  style: GoogleFonts.inter(
                      fontSize: 16.0, fontWeight: FontWeight.w600),
                ),
              ],
            ),
          ),
        ),
        if (product.isNotEmpty) ...[
          SliverPadding(
            padding: Utils.symmetric(h: 12.0),
            sliver: SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) {
                  final item = product[index];
                  return GuestCartComponent(product: item);
                },
                childCount: product.length,
                addAutomaticKeepAlives: true,
              ),
            ),
          ),
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
    final length = product.length;
    if (length > 1) {
      return '$length ${Language.products.capitalizeByWord()}';
    } else {
      return '$length ${Language.product.capitalizeByWord()}';
    }
  }
}
