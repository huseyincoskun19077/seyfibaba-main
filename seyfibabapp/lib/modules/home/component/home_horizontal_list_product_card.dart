import 'package:auto_size_text/auto_size_text.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_image.dart';
import '../../../widgets/custom_text.dart';
import '../../../widgets/favorite_button.dart';
import '../../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../../category/component/price_card_widget.dart';
import '../../setting/model/website_setup_model.dart';
import '../model/product_model.dart';

class HomeHorizontalListProductCard extends StatelessWidget {
  final ProductModel productModel;

  const HomeHorizontalListProductCard({
    super.key,
    this.height = 100,
    this.width = 215,
    required this.productModel,
  });
  final double height, width;

  @override
  Widget build(BuildContext context) {
    final appSetting = context.read<AppSettingCubit>();
    double flashPrice = 0.0;
    double offerPrice = 0.0;
    double mainPrice = 0.0;
    final isFlashSale = appSetting.settingModel!.flashSaleProducts
        .contains(FlashSaleProductsModel(productId: productModel.id));

    if (productModel.offerPrice != 0) {
      double p = 0.0;
      if (productModel.productVariants.isNotEmpty) {
        for (var i in productModel.productVariants) {
          if (i.activeVariantsItems.isNotEmpty) {
            p += Utils.toDouble(i.activeVariantsItems.first.price.toString());
          }
        }
        offerPrice = p + productModel.offerPrice;
      } else {
        offerPrice = productModel.offerPrice;
      }
    }
    if (productModel.productVariants.isNotEmpty) {
      double p = 0.0;
      for (var i in productModel.productVariants) {
        if (i.activeVariantsItems.isNotEmpty) {
          p += Utils.toDouble(i.activeVariantsItems.first.price.toString());
        }
      }
      mainPrice = p + productModel.price;
    } else {
      mainPrice = productModel.price;
    }
    if (isFlashSale) {
      if (productModel.offerPrice != 0) {
        final discount =
            appSetting.settingModel!.flashSale.offer / 100 * offerPrice;

        flashPrice = offerPrice - discount;
      } else {
        final discount =
            appSetting.settingModel!.flashSale.offer / 100 * mainPrice;

        flashPrice = mainPrice - discount;
      }
    }
    return ClipRRect(
      borderRadius: Utils.borderRadius(r: 12.0),
      child: Container(
        height: height,
        width: width,
        padding: const EdgeInsets.only(right: 8),
        decoration: BoxDecoration(
            color: whiteColor,
            borderRadius: Utils.borderRadius(r: 12.0),
            boxShadow: [
              BoxShadow(
                  offset: const Offset(0.0, 15.0),
                  spreadRadius: 0.0,
                  blurRadius: 0.0,
                  // color: whiteColor
                  color: const Color(0xFF000000).withOpacity(0.4)),
            ]),
        child: InkWell(
          onTap: () {
            Navigator.pushNamed(context, RouteNames.productDetailsScreen,
                arguments: productModel.slug);
          },
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SizedBox(
                height: height - 2,
                width: width / 2.7,
                child: ClipRRect(
                  borderRadius:
                      const BorderRadius.horizontal(left: Radius.circular(4)),
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      CustomImage(
                        path: RemoteUrls.imageUrl(productModel.thumbImage),
                        fit: BoxFit.cover,
                      ),
                      Positioned(
                          left: 5.0,
                          top: 5.0,
                          child: FavoriteButton(productId: productModel.id.toString())),
                      Positioned(
                        left: 5.0,
                        bottom: 5.0,
                        child: ProductPackBadge(qty: productModel.saleUnitQty),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Flexible(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    if (productModel.rating > 0) ...[
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          const Icon(
                            Icons.star,
                            size: 14.0,
                            color: yellowColor,
                          ),
                          const SizedBox(width: 4),
                          CustomText(
                              text:productModel.rating.toStringAsFixed(1),
                              fontSize: 14.0, height: 1.25
                          ),
                        ],
                      ),
                      const SizedBox(height: 2.0),
                    ],

                    Align(
                      alignment: Alignment.centerLeft,
                      child: CustomText(text: productModel.name,
                          textAlign: TextAlign.left,
                          maxLine: 2,
                          overflow: TextOverflow.ellipsis,
                          fontWeight: FontWeight.w600, fontSize: 16.0
                    )),

                    if (isFlashSale) ...[
                      PriceCardWidget(
                        price: mainPrice.toString(),
                        offerPrice: flashPrice.toString(),
                        textSize: 16.0,
                        saleUnitQty: productModel.saleUnitQty,
                      ),
                    ] else ...[
                      PriceCardWidget(
                        price: mainPrice.toString(),
                        offerPrice: offerPrice.toString(),
                        textSize: 16.0,
                        saleUnitQty: productModel.saleUnitQty,
                      ),
                    ],
                  ],
                ),
              )
            ],
          ),
        ),
      ),
    );
  }
}
