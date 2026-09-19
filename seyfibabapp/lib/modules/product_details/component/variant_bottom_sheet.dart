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
                                  () {
                                    final selectedColor = state.variantItem
                                        .where((e) => e.image.isNotEmpty)
                                        .toList();
                                    if (selectedColor.isNotEmpty) {
                                      return selectedColor.last.image;
                                    }
                                    return dCubit.details?.product.thumbImage ??
                                        Kimages.kNetworkImage;
                                  }()),
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
      if (item.id <= 0) continue;
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
    final requiredGroups = dCubit.state.variants.where((v) {
      return !RegExp(r'renk|color', caseSensitive: false).hasMatch(v.name);
    }).toList();
    final selectedRequired = variants.where((v) {
      return !RegExp(r'renk|color', caseSensitive: false).hasMatch(v.name);
    }).length;
    if (requiredGroups.isNotEmpty && selectedRequired < requiredGroups.length) {
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
    final isColor = RegExp(r'renk|color', caseSensitive: false).hasMatch(p.name);
    final selected = state.variantItem.length > pIndex
        ? state.variantItem[pIndex]
        : (p.activeVariantsItems.isNotEmpty ? p.activeVariantsItems.first : null);
    final productBase = (dCubit.details?.product.offerPrice ?? 0) != 0
        ? dCubit.details!.product.offerPrice
        : (dCubit.details?.product.price ?? 0);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CustomText(
          text: '${p.name}${isColor ? ' (${p.activeVariantsItems.length})' : ''}',
          color: blackColor,
          fontSize: 16.0,
          fontWeight: FontWeight.w500,
        ),
        Utils.verticalSpace(8.0),
        if (isColor)
          ConstrainedBox(
            constraints: BoxConstraints(
              maxHeight: p.activeVariantsItems.length > 12 ? 280 : double.infinity,
            ),
            child: SingleChildScrollView(
              child: Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _colorChip(
                    pIndex: pIndex,
                    selected: selected == null || selected.id <= 0,
                    label: 'Standart',
                    image: dCubit.details?.product.thumbImage ?? '',
                    priceLabel: productBase,
                    onTap: () {
                      dCubit.addIndex(pIndex.toString());
                      dCubit.updateVPItems(ActiveVariantItemModel(
                        productVariantId: p.id,
                        id: -1,
                        name: 'Standart',
                        image: '',
                        price: 0,
                      ));
                      setState(() {});
                    },
                  ),
                  ...p.activeVariantsItems.map((item) {
                    return _colorChip(
                      pIndex: pIndex,
                      selected: selected?.id == item.id,
                      label: item.name,
                      image: item.image,
                      priceLabel: item.price > 0 ? item.price : productBase,
                      onTap: () {
                        dCubit.addIndex(pIndex.toString());
                        dCubit.updateVPItems(item);
                        setState(() {});
                      },
                    );
                  }),
                ],
              ),
            ),
          )
        else
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: p.activeVariantsItems.map((item) {
              final active = selected?.id == item.id;
              final extra = item.price;
              return GestureDetector(
                onTap: () {
                  dCubit.addIndex(pIndex.toString());
                  dCubit.updateVPItems(item);
                  setState(() {});
                },
                child: Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: active
                          ? Utils.dynamicPrimaryColor(context)
                          : borderColor,
                      width: active ? 2 : 1,
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        item.name,
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight:
                              active ? FontWeight.w700 : FontWeight.w500,
                        ),
                      ),
                      if (extra > 0) ...[
                        const SizedBox(width: 6),
                        Text(
                          '+${Utils.formatPrice(extra, context)}',
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: redColor,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              );
            }).toList(),
          ),
        Utils.verticalSpace(16.0),
      ],
    );
  }

  Widget _colorChip({
    required int pIndex,
    required bool selected,
    required String label,
    required String image,
    required double priceLabel,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 76,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: selected ? Utils.dynamicPrimaryColor(context) : borderColor,
            width: selected ? 2 : 1,
          ),
        ),
        padding: const EdgeInsets.all(5),
        child: Column(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: SizedBox(
                height: 44,
                width: double.infinity,
                child: image.isNotEmpty
                    ? CustomImage(
                        path: RemoteUrls.imageUrl(image),
                        fit: BoxFit.cover,
                      )
                    : Container(color: borderColor.withOpacity(0.35)),
              ),
            ),
            const SizedBox(height: 4),
            Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600),
            ),
            Text(
              Utils.formatPrice(priceLabel, context),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 10, color: iconGreyColor),
            ),
          ],
        ),
      ),
    );
  }
}
