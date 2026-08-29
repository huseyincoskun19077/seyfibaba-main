import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';
import 'package:shop_o/core/data/datasources/remote_data_source_packages.dart';
import 'package:shop_o/widgets/capitalized_word.dart';

import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_text.dart';
import '../../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../../category/component/price_card_widget.dart';
import '../../category/model/category_navigation_args.dart';
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
      // print('product_price_not_null');
      if (widget.product.activeVariantModel.isNotEmpty) {
        double p = 0.0;
        for (var i in widget.product.activeVariantModel) {
          if (i.activeVariantsItems.isNotEmpty) {
            p += Utils.toDouble(i.activeVariantsItems.first.price.toString());
          }
        }
        offerPrice = p + widget.product.offerPrice;
      } else {
        offerPrice = widget.product.offerPrice;
      }
    } else {
      // print('product_price_null');
      // print('product_price_null');
    }
    if (widget.product.activeVariantModel.isNotEmpty) {
      double p = 0.0;
      for (var i in widget.product.activeVariantModel) {
        if (i.activeVariantsItems.isNotEmpty) {
          p += Utils.toDouble(i.activeVariantsItems.first.price.toString());
        }
      }
      mainPrice = p + widget.product.price;
    } else {
      mainPrice = widget.product.price;
    }

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
