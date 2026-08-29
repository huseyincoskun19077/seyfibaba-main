import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';
import '/core/error/router_package_name.dart';
import '/utils/k_images.dart';
import '/widgets/custom_image.dart';

import '/widgets/capitalized_word.dart';
import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_text.dart';
import '../../../widgets/primary_button.dart';
import '../../home/component/home_app_bar.dart';
import '../controller/cubit/details_state_model.dart';
import '../controller/cubit/product_details_cubit.dart';
import '../../cart/controllers/cart/add_to_cart/add_to_cart_cubit.dart';
import '../../cart/controllers/cart/cart_cubit.dart';
import '../../cart/model/add_to_cart_model.dart';
import '../model/active_variant_items_model.dart';
import '../model/active_variant_model.dart';

class VariantBottomSheet extends StatefulWidget {
  const VariantBottomSheet({super.key});

  @override
  State<VariantBottomSheet> createState() => _VariantBottomSheetState();
}

class _VariantBottomSheetState extends State<VariantBottomSheet> {
  late ProductDetailsCubit dCubit;

  @override
  void initState() {
    super.initState();
    _init();
  }

  _init() {
    dCubit = context.read<ProductDetailsCubit>();
  }


  @override
  Widget build(BuildContext context) {
    return BlocConsumer<ProductDetailsCubit, DetailsStateModel>(
      listener: (context, detail) {
        final state = detail.detailsState;
        if (state is GuestSaveProduct || state is GuestAddProductError) {
          //debugPrint('error-pop-up-listener');
          Navigator.of(context).pop();
        }
      },
      builder: (context, state) {
        return Padding(
          padding: Utils.symmetric(v: 20.0).copyWith(top: 10.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                  width: double.infinity,
                  padding: Utils.symmetric(h: 0.0, v: 10.0),
                  child: Column(
                    children: [
                      Row(
                        children: [
                          Container(
                            height: Utils.vSize(70.0),
                            width: Utils.vSize(80.0),
                            margin: Utils.only(right: 12.0),
                            child: CustomImage(
                              path: RemoteUrls.imageUrl(
                                  dCubit.details?.product.thumbImage ??
                                      Kimages.kNetworkImage),
                              fit: BoxFit.fill,
                            ),
                          ),
                          Flexible(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  CustomText(
                                        text: dCubit.details?.product.name ?? '',
                                        fontSize: 16.0,
                                        fontWeight: FontWeight.w600,
                                        color: blackColor,
                                        maxLine: 2,
                                    ),
                                  Text(
                                    Utils.formatPrice(state.detailPrice, context),
                                    style: GoogleFonts.inter (fontSize: 16.0, fontWeight: FontWeight.w600),
                                  ),
                                ],
                              ),
                          ),
                        ],
                      ),
                      Row(
                        children: [
                          Padding(
                            padding: Utils.only(right: 20.0),
                            child: CustomText(
                              text: Language.quantity.capitalizeByWord(),
                              fontSize: 18.0,
                              color: redColor,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          _qtyButton('remove', Icons.remove),
                          Padding(
                            padding: Utils.symmetric(h: 10.0),
                            child: Text(
                              state.qty.toString(),
                              style: GoogleFonts.inter(
                                  fontSize: 20.0, fontWeight: FontWeight.w600),
                            ),
                          ),
                          _qtyButton('add', Icons.add),
                          const Spacer(),
                          Text(
                            Utils.formatPrice(
                                state.detailPrice * state.qty, context),
                            style: GoogleFonts.inter(
                              fontSize: 16.0,
                              fontWeight: FontWeight.w700,
                              color: redColor,
                            ),
                          ),
                        ],
                      )
                    ],
                  ),
              ),
              Expanded(
                child: SingleChildScrollView(
                    child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    if(state.variants.isNotEmpty)...[
                      ...List.generate(state.variants.length, (pIndex) {
                        final p = state.variants[pIndex];
                        return _productVariant(p, pIndex, state);
                      }),
                    ],
                  ],
                )),
              ),
              Row(
                children: [
                  Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Spacer(),
                      InkWell(
                        onTap: () {
                          if (Utils.isLoggedIn(context)) {
                            Navigator.pushNamed(context, RouteNames.cartScreen);
                          } else {
                            Utils.errorSnackBar(
                              context,
                              Language.loginRequiredForCheckout,
                            );
                            Navigator.pushNamed(
                              context,
                              RouteNames.authenticationScreen,
                            );
                          }
                        },
                        child: Container(
                          height: 50,
                          width: 50,
                          padding: const EdgeInsets.all(12),
                          child: Utils.isLoggedIn(context)
                              ? BlocBuilder<CartCubit, CartState>(
                                  builder: (context, _) {
                                    return CartBadge(
                                      count: context
                                          .read<CartCubit>()
                                          .cartCount
                                          .toString(),
                                      iconColor: grayColor,
                                    );
                                  },
                                )
                              : BlocBuilder<ProductDetailsCubit,
                                  DetailsStateModel>(
                                  builder: (context, state) {
                                    return CartBadge(
                                      count: state.count.toString(),
                                      iconColor: grayColor,
                                    );
                                  },
                                ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(width: 20),
                  Expanded(
                    child: Column(
                      children: [
                        PrimaryButton(
                          text: Language.addToCart.capitalizeByWord(),
                          buttonType: ButtonType.elevated,
                          onPressed: () => _addToCart(context),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  Set<ActiveVariantModel> _selectedVariants(DetailsStateModel state) {
    final selected = <ActiveVariantModel>{};
    for (final item in state.variantItem) {
      final parent = state.variants.cast<ActiveVariantModel?>().firstWhere(
            (variant) => variant?.id == item.productVariantId,
            orElse: () => null,
          );
      if (parent != null) {
        selected.add(parent.copyWith(activeVariantsItems: [item]));
      }
    }
    return selected;
  }

  void _addToCart(BuildContext context) {
    final product = dCubit.details?.product;
    if (product == null) return;

    if (!Utils.isLoggedIn(context)) {
      Navigator.of(context).pop();
      Utils.errorSnackBar(context, Language.loginRequiredForCheckout);
      Navigator.pushNamed(context, RouteNames.authenticationScreen);
      return;
    }

    final cartCubit = context.read<CartCubit>();
    if (cartCubit.cartResponseModel != null &&
        cartCubit.isExistInCart(product.id)) {
      Navigator.of(context).pop();
      Utils.errorSnackBar(context, Language.alreadyInCart, redColor, 3000);
      return;
    }

    final variants = _selectedVariants(dCubit.state);
    if (dCubit.state.variants.isNotEmpty && variants.isEmpty) {
      Utils.errorSnackBar(context, Language.fieldRequired);
      return;
    }

    Navigator.of(context).pop();
    context.read<AddToCartCubit>().addToCart(
          AddToCartModel(
            image: product.thumbImage,
            productId: product.id,
            slug: product.slug,
            quantity: dCubit.state.qty,
            token: '',
            variantItems: variants,
          ),
        );
  }

  Widget _qtyButton(String n, IconData icon) {
    return GestureDetector(
      onTap: () => dCubit.addQty(n),
      child: CircleAvatar(
        radius: 12.0,
        backgroundColor: Utils.dynamicPrimaryColor(context),
        child: Icon(icon, color: whiteColor),
      ),
    );
  }

  Widget _productVariant(ActiveVariantModel p, int pIndex, DetailsStateModel state) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CustomText(
          text: p.name,
          color: blackColor,
          fontSize: 16.0,
          fontWeight: FontWeight.w500,
        ),
        DropdownButtonFormField<ActiveVariantItemModel>(
          isDense: true,
          isExpanded: true,
          value: p.activeVariantsItems.first,
          padding: Utils.symmetric(h: 0.0, v: 6.0),
          hint: CustomText(
            text: Language.selectVariantItem,
            fontWeight: FontWeight.w400,
            fontSize: 16.0,
          ),
          onTap: () {
            dCubit.addIndex(pIndex.toString());
          },
          icon: const Icon(Icons.keyboard_arrow_down_sharp, color: blackColor),
          items: p.activeVariantsItems.isNotEmpty
              ? p.activeVariantsItems
                  .map<DropdownMenuItem<ActiveVariantItemModel>>(
                      (e) => DropdownMenuItem(
                            value: e,
                            child: CustomText(
                              text: e.name,
                              fontSize: 16.0,
                              color: blackColor,
                            ),
                          ))
                  .toList()
              : [],
          onChanged: (val) {
            if (val == null) return;
            // debugPrint('item-id-price ${val.id} | ${val.price} | ${val.otherOptions}');
            debugPrint('variant-id ${val.id}');

            dCubit.updateVPItems(val);
          },
        ),
        Utils.verticalSpace(16.0),
      ],
    );
  }

}
