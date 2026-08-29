import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shop_o/widgets/capitalized_word.dart';

import '../../../widgets/custom_text.dart';
import '/modules/product_details/model/product_details_product_model.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/primary_button.dart';
import '../../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../../cart/controllers/cart/add_to_cart/add_to_cart_cubit.dart';
import '../../cart/controllers/cart/cart_cubit.dart';
import '../../cart/model/add_to_cart_model.dart';
import '../../home/model/product_model.dart';
import '../../setting/model/website_setup_model.dart';
import '../model/active_variant_items_model.dart';
import '../model/active_variant_model.dart';
import 'bottom_sheet_product.dart';

class BottomSheetWidget extends StatefulWidget {
  const BottomSheetWidget({super.key, required this.product});

  final ProductDetailsProductModel product;

  @override
  State<BottomSheetWidget> createState() => _BottomSheetWidgetState();
}

class _BottomSheetWidgetState extends State<BottomSheetWidget> {
  Set<ActiveVariantModel> variantItems = {};

  int quantity = 1;

  @override
  void initState() {
    super.initState();
    _variantsInit();
  }

  void _variantsInit() {
    for (var element in widget.product.activeVariantModel) {
      if (element.activeVariantsItems.isNotEmpty) {
        final item = element.activeVariantsItems.first;
        variantItems.add(element.copyWith(activeVariantsItems: [item]));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isExist = context.read<CartCubit>();
    return Padding(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          BottomSheetProduct(
            product: ProductModel.fromMap(widget.product.toMap()),
            variantItem: variantItems,
          ),
          Container(
            color: const Color(0xffD9D9D9),
            height: 1,
            width: double.infinity,
            margin: const EdgeInsets.only(bottom: 15),
          ),

          _VariantItemsWidget(
            productVariants: widget.product.activeVariantModel,
            variantItems: variantItems,
            onChange: (item) {
              setState(() {
                for (var element in variantItems.toList()) {
                  if (element.id == item.id) {
                    variantItems.remove(element);
                  }
                }
                variantItems.add(item);
              });
            },
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Padding(
                padding: const EdgeInsets.only(right: 20),
                child: CustomText(
                    text: Language.quantity.capitalizeByWord(),
                    fontSize: 18.0,
                    color: redColor,
                    fontWeight: FontWeight.w600),
              ),
              InkWell(
                splashColor: transparent,
                splashFactory: NoSplash.splashFactory,
                onTap: () {
                  quantity++;
                  setState(() {});
                },
                child: CircleAvatar(
                  radius: 12.0,
                  backgroundColor: Utils.dynamicPrimaryColor(context),
                  child: const Icon(Icons.add, color: Colors.black),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 9),
                child: Text(
                  quantity.toString(),
                  style: const TextStyle(
                      fontSize: 18, fontWeight: FontWeight.w600),
                ),
              ),
              InkWell(
                splashColor: transparent,
                splashFactory: NoSplash.splashFactory,
                onTap: () {
                  if (quantity > 1) {
                    quantity--;
                    setState(() {});
                  }
                },
                child: CircleAvatar(
                  radius: 12.0,
                  backgroundColor: Utils.dynamicPrimaryColor(context),
                  child: const Icon(Icons.remove, color: Colors.black),
                ),
              ),
              const Spacer(),
              CustomText(
                text: lineTotalPrice(context),
                isTranslate: false,
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: redColor,
              ),
            ],
          ),
          const SizedBox(height: 10),
          if (isExist.isExistInCart(widget.product.id)) ...[
            ElevatedButton.icon(
                onPressed: () =>
                    Navigator.pushNamed(context, RouteNames.cartScreen),
                style: ButtonStyle(
                    shape: WidgetStateProperty.all(const RoundedRectangleBorder(
                        borderRadius: BorderRadius.zero)),
                    elevation: WidgetStateProperty.all(0.0),
                    shadowColor: WidgetStateProperty.all(Colors.transparent),
                    splashFactory: NoSplash.splashFactory,
                    minimumSize: WidgetStateProperty.all(
                        const Size(double.infinity, 50.0))),
                icon: const Icon(
                  Icons.download_done,
                  color: blackColor,
                ),
                label: CustomText(
                  text: Language.alreadyInCart,
                  color: blackColor,
                ))
          ] else ...[
            PrimaryButton(
              text: Language.addToCart.capitalizeByWord(),
              onPressed: () {
                Navigator.pop(context);
                final dataModel = AddToCartModel(
                  image: widget.product.thumbImage,
                  productId: widget.product.id,
                  slug: widget.product.slug,
                  quantity: quantity,
                  token: '',
                  variantItems: variantItems,
                );
                context.read<AddToCartCubit>().addToCart(dataModel);
              },
            ),
          ]
          // PrimaryButton(
          //   text: Language.addToCart.capitalizeByWord(),
          //   onPressed: () {
          //     Navigator.pop(context);
          //     final dataModel = AddToCartModel(
          //       image: widget.product.thumbImage,
          //       productId: widget.product.id,
          //       slug: widget.product.slug,
          //       quantity: quantity,
          //       token: '',
          //       variantItems: variantItems,
          //     );
          //     context.read<AddToCartCubit>().addToCart(dataModel);
          //   },
          // ),
        ],
      ),
    );
  }

  double _variantExtra() {
    double extra = 0.0;
    for (final variant in variantItems) {
      if (variant.activeVariantsItems.isNotEmpty) {
        extra += Utils.toDouble(
            variant.activeVariantsItems.first.price.toString());
      }
    }
    return extra;
  }

  double _effectiveUnitPrice() {
    final appSetting = context.read<AppSettingCubit>();
    final variantExtra = _variantExtra();
    final mainPrice = variantExtra + widget.product.price;
    final offerPrice = widget.product.offerPrice != 0
        ? variantExtra + widget.product.offerPrice
        : 0.0;

    final isFlashSale = appSetting.settingModel!.flashSaleProducts.contains(
      FlashSaleProductsModel(productId: widget.product.id),
    );
    final flashActive = appSetting.settingModel!.flashSale.status == 1;

    if (isFlashSale && flashActive) {
      final base = offerPrice != 0 ? offerPrice : mainPrice;
      final discount = appSetting.settingModel!.flashSale.offer / 100 * base;
      return base - discount;
    }

    if (offerPrice != 0) {
      return offerPrice;
    }
    return mainPrice;
  }

  String lineTotalPrice(BuildContext context) {
    return Utils.formatPrice(_effectiveUnitPrice() * quantity, context);
  }
}

class _VariantItemsWidget extends StatelessWidget {
  const _VariantItemsWidget({
    required this.productVariants,
    required this.variantItems,
    required this.onChange,
  });

  final List<ActiveVariantModel> productVariants;
  final Set<ActiveVariantModel> variantItems;

  final ValueChanged<ActiveVariantModel> onChange;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: productVariants.map(_buildSingleVariant).toList(),
    );
  }

  Widget _buildSingleVariant(ActiveVariantModel singleVariant) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          if (singleVariant.activeVariantsItems.isNotEmpty) ...[
            Flexible(
              flex: 1,
              fit: FlexFit.tight,
              child: CustomText(
                text: "${singleVariant.name} : ",
                fontWeight: FontWeight.w600,
                fontSize: 16.0,
              ),
            ),
            Flexible(
              flex: 4,
              fit: FlexFit.tight,
              child: Wrap(
                children: singleVariant.activeVariantsItems.map(
                  (itemModel) {
                    return _buildVariantItemBox(singleVariant, itemModel);
                  },
                ).toList(),
              ),
            )
          ]
        ],
      ),
    );
  }

  Widget _buildVariantItemBox(
    ActiveVariantModel singleVariant,
    ActiveVariantItemModel itemModel,
  ) {
    final variant = singleVariant.copyWith(activeVariantsItems: [itemModel]);
    return InkWell(
      onTap: () => onChange(variant),
      child: Container(
        margin: const EdgeInsets.all(2),
        padding: const EdgeInsets.all(4),
        decoration: BoxDecoration(
          color: variantItems.contains(variant) ? redColor : null,
          borderRadius: BorderRadius.circular(3),
          border: Border.all(color: borderColor),
        ),
        child: CustomText(
          text: itemModel.name,
          color: variantItems.contains(variant) ? Colors.white : paragraphColor,
        ),
      ),
    );
  }
}
