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
import '../../home/model/product_model.dart';
import '../../setting/model/website_setup_model.dart';
import 'price_card_widget.dart';

class PopularProductCard extends StatelessWidget {
  final ProductModel productModel;

  const PopularProductCard({super.key, required this.productModel});

  @override
  Widget build(BuildContext context) {
    const double height = 125;
    return InkWell(
      onTap: () {
        Navigator.pushNamed(context, RouteNames.productDetailsScreen,
            arguments: productModel.slug);
      },
      child: ClipRRect(
        borderRadius: Utils.borderRadius(r: 12.0),
        child: Container(
          height: height,
          margin: const EdgeInsets.symmetric(vertical: 5.0),
          decoration: BoxDecoration(
              color: whiteColor,
              borderRadius: Utils.borderRadius(r: 12.0),
              boxShadow: [
                BoxShadow(
                    offset: const Offset(0.0, 0.0),
                    spreadRadius: 0.0,
                    blurRadius: 0.0,
                    // color: whiteColor
                    color: const Color(0xFF000000).withOpacity(0.4)),
              ]),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildImage(height),
              const SizedBox(width: 14),
              _buildContent(context),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildImage(double height) {
    return Container(
      height: height,
      width: height,
      padding: Utils.all(value: 10.0),
      child: Stack(
        fit: StackFit.expand,
        children: [
          ClipRRect(
            borderRadius: Utils.borderRadius(r: 5.0),
            child: CustomImage(
              path: RemoteUrls.imageUrl(productModel.thumbImage),
              fit: BoxFit.cover,
            ),
          ),
          Positioned(
            left: 8,
            bottom: 8,
            child: ProductPackBadge(qty: productModel.saleUnitQty),
          ),
          Positioned(
            top: 8,
            left: 8,
            child: FavoriteButton(productId: productModel.id.toString()),
          ),
        ],
      ),
    );
  }

  Widget _buildContent(BuildContext context) {
    final appSetting = context.read<AppSettingCubit>();
    double flashPrice = 0.0;
    double offerPrice = 0.0;
    double mainPrice = 0.0;
    final isFlashSale = appSetting.settingModel!.flashSaleProducts
        .contains(FlashSaleProductsModel(productId: productModel.id));
    int flashSaleActive = appSetting.settingModel!.flashSale.status;

    if (productModel.offerPrice != 0) {
      if (productModel.productVariants.isNotEmpty) {
        double p = 0.0;
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

    if (isFlashSale && flashSaleActive == 1) {
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
    return Expanded(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            if (productModel.rating > 0) ...[
              Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  const Icon(Icons.star, size: 14.0, color: yellowColor),
                  const SizedBox(width: 4),
                  CustomText(
                    text: productModel.rating.toStringAsFixed(1),
                    fontSize: 14.0,
                    height: 1.2,
                  ),
                ],
              ),
              const SizedBox(height: 10),
            ],
           CustomText(text:
              productModel.name,
              maxLine: 1,
              overflow: TextOverflow.ellipsis,
               fontWeight: FontWeight.w600, fontSize: 16, height: 1.5
            ),
            const SizedBox(height: 8),
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
            // Row(
            //   children: [
            //     Text(
            //       Utils.formatPrice(productModel.price, context),
            //       style: const TextStyle(
            //           color: redColor,
            //           height: 1.5,
            //           fontSize: 16,
            //           fontWeight: FontWeight.w600),
            //     ),
            //     const SizedBox(width: 6),
            //     Text(
            //       Utils.formatPrice(productModel.offerPrice, context),
            //       style: const TextStyle(
            //         decoration: TextDecoration.lineThrough,
            //         color: Color(0xff85959E),
            //         height: 1.5,
            //         fontSize: 14,
            //       ),
            //     )
            //   ],
            // ),
          ],
        ),
      ),
    );
  }
}
