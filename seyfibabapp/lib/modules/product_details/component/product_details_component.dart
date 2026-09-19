import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';
import 'package:shop_o/core/data/datasources/remote_data_source_packages.dart';
import 'package:shop_o/widgets/capitalized_word.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_image.dart';
import '../../../widgets/custom_text.dart';
import '../../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../../category/component/price_card_widget.dart';
import '../../category/model/category_navigation_args.dart';
import '../controller/cubit/product_details_cubit.dart';
import '../model/product_details_product_model.dart';
import 'product_furniture_inquiry.dart';

class ProductDetailsComponent extends StatefulWidget {
  const ProductDetailsComponent(
      {required this.product, required this.detailsModel, super.key});

  final ProductDetailsProductModel product;
  final ProductDetailsModel detailsModel;

  @override
  State<ProductDetailsComponent> createState() =>
      _ProductDetailsComponentState();
}

class _ProductDetailsComponentState extends State<ProductDetailsComponent> {
  @override
  Widget build(BuildContext context) {
    final appSetting = context.read<AppSettingCubit>();
    double flashPrice = 0.0;
    double offerPrice = 0.0;
    double mainPrice = 0.0;
    final isFlashSale = appSetting.settingModel!.flashSaleProducts
        .contains(FlashSaleProductsModel(productId: widget.product.id));
    int flashSaleActive = appSetting.settingModel!.flashSale.status;

    // print('product_offer_price ${widget.product.offerPrice}');
    if (widget.product.offerPrice != 0.0) {
      offerPrice = widget.product.offerPrice;
    }
    mainPrice = widget.product.price;

    if (isFlashSale && flashSaleActive == 1) {
      if (widget.product.offerPrice != 0) {
        // print('FLASH_SALE_OFFER_NOT_NULL ${appSetting.settingModel!.flashSale.offer}');
        final discount =
            appSetting.settingModel!.flashSale.offer / 100 * offerPrice;

        flashPrice = offerPrice - discount;
      } else {
        // print('FLASH_SALE_OFFER_NULL ${appSetting.settingModel!.flashSale.offer}');
        final discount =
            appSetting.settingModel!.flashSale.offer / 100 * mainPrice;

        flashPrice = mainPrice - discount;
      }
    }

    widget.product.copyWith(
      offerPrice: isFlashSale ? flashPrice : offerPrice,
      price: mainPrice,
    );
    final qty = widget.product.qty;
    final String availability;
    final Color availabilityColor;
    if (qty <= 0) {
      availability = Language.stockOut;
      availabilityColor = redColor;
    } else if (qty <= 5) {
      availability = '${Language.lowStock} · $qty adet';
      availabilityColor = yellowColor;
    } else {
      availability = '${Language.inStock} · $qty adet';
      availabilityColor = greenColor;
    }
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (isFlashSale) ...[
            PriceCardWidget(
              price: mainPrice.toString(),
              offerPrice: flashPrice.toString(),
              textSize: 22,
              saleUnitQty: widget.product.saleUnitQty,
            ),
          ] else ...[
            PriceCardWidget(
              price: mainPrice.toString(),
              offerPrice: offerPrice.toString(),
              textSize: 22,
              saleUnitQty: widget.product.saleUnitQty,
            ),
          ],
          const SizedBox(height: 4),
          CustomText(
              text: widget.product.name,
              fontSize: 20,
              fontWeight: FontWeight.w600),
          if (widget.product.brand != null &&
              widget.product.brand!.name.isNotEmpty) ...[
            const SizedBox(height: 8),
            GestureDetector(
              onTap: () {
                Navigator.pushNamed(
                  context,
                  RouteNames.brandProductScreen,
                  arguments: widget.product.brand!.slug,
                );
              },
              child: Text(
                widget.product.brand!.name,
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: Utils.dynamicPrimaryColor(context),
                ),
              ),
            ),
          ],
          if (widget.product.category != null &&
              widget.product.category!.name.isNotEmpty) ...[
            const SizedBox(height: 4),
            GestureDetector(
              onTap: () {
                Navigator.pushNamed(
                  context,
                  RouteNames.singleCategoryProductScreen,
                  arguments: CategoryProductArgs(
                    slug: widget.product.category!.slug,
                    name: widget.product.category!.name,
                  ),
                );
              },
              child: Text(
                widget.product.category!.name,
                style: const TextStyle(
                  fontSize: 13,
                  color: iconGreyColor,
                  decoration: TextDecoration.underline,
                ),
              ),
            ),
          ],
          const SizedBox(height: 12),
          _builtRating(),
          const SizedBox(height: 12),
          Row(
            children: [
              CustomText(
                text: '${Language.availability.capitalizeByWord()}: ',
                color: blackColor,
                fontSize: 18.0,
                fontWeight: FontWeight.w600,
              ),
              Utils.horizontalSpace(6.0),
              CustomText(
                  text: availability,
                  color: availabilityColor,
                  fontSize: 16.0,
                  fontWeight: FontWeight.w600),
            ],
          ),
          const SizedBox(height: 10.0),
          _buildColorVariants(context),
          CustomText(
              text: widget.product.shortDescription,
              textAlign: TextAlign.justify,
              color: iconGreyColor,
            isTranslate: true,
          ),
          const SizedBox(height: 16),
          ProductFurnitureInquiry(product: widget.product),
          const SizedBox(height: 10),
        ],
      ),
    );
  }

  Widget _buildColorVariants(BuildContext context) {
    final hasColor = widget.product.activeVariantModel.any((v) =>
        RegExp(r'renk|color', caseSensitive: false).hasMatch(v.name) &&
        v.activeVariantsItems.isNotEmpty);
    if (!hasColor) return const SizedBox.shrink();

    final cubit = context.read<ProductDetailsCubit>();
    final state = cubit.state;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        for (var pIndex = 0; pIndex < widget.product.activeVariantModel.length; pIndex++) ...[
          if (RegExp(r'renk|color', caseSensitive: false)
                  .hasMatch(widget.product.activeVariantModel[pIndex].name) &&
              widget.product.activeVariantModel[pIndex].activeVariantsItems
                  .isNotEmpty) ...[
            CustomText(
              text: widget.product.activeVariantModel[pIndex].name,
              fontSize: 15,
              fontWeight: FontWeight.w600,
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: widget
                  .product.activeVariantModel[pIndex].activeVariantsItems
                  .map((item) {
                final selected = state.variantItem.length > pIndex &&
                    state.variantItem[pIndex].id == item.id;
                return GestureDetector(
                  onTap: () {
                    cubit.addIndex(pIndex.toString());
                    cubit.updateVPItems(item);
                    setState(() {});
                  },
                  child: Container(
                    width: 92,
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: selected
                            ? Utils.dynamicPrimaryColor(context)
                            : borderColor,
                        width: selected ? 2 : 1,
                      ),
                    ),
                    child: Column(
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: SizedBox(
                            height: 60,
                            width: double.infinity,
                            child: item.image.isNotEmpty
                                ? CustomImage(
                                    path: RemoteUrls.imageUrl(item.image),
                                    fit: BoxFit.cover,
                                  )
                                : Container(color: borderColor.withOpacity(0.3)),
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          item.name,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        Text(
                          Utils.formatPrice(item.price, context),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w500,
                            color: iconGreyColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              }).toList(),
            ),
            const SizedBox(height: 14),
          ],
        ],
      ],
    );
  }

  Widget _builtRating() {
    return Row(
      children: [
        RatingBar.builder(
          initialRating: widget.product.averageRating,
          minRating: 1,
          direction: Axis.horizontal,
          allowHalfRating: true,
          ignoreGestures: true,
          itemCount: 5,
          itemSize: 15,
          itemPadding: const EdgeInsets.symmetric(horizontal: 2.0),
          itemBuilder: (context, _) =>  Icon(
            Icons.star,
            color: Utils.dynamicPrimaryColor(context),
          ),
          onRatingUpdate: (rating) {},
        ),
        Container(
            width: 1,
            margin: const EdgeInsets.symmetric(horizontal: 6),
            height: 24,
            color: borderColor),
        CustomText(
            text: Utils.getRating(widget.detailsModel.productReviews)
                .toStringAsFixed(1),
            fontSize: 13,
            fontWeight: FontWeight.w300)
      ],
    );
  }
}
