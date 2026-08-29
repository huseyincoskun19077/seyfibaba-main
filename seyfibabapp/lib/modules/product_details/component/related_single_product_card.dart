import 'package:auto_size_text/auto_size_text.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../widgets/custom_text.dart';
import '/modules/category/component/price_card_widget.dart';
import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_image.dart';
import '../../../widgets/favorite_button.dart';
import '../../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../../setting/model/website_setup_model.dart';
import '../model/product_details_product_model.dart';

class RelatedSingleProductCard extends StatelessWidget {
  final ProductDetailsProductModel productModel;
  final double? width;

  const RelatedSingleProductCard({
    super.key,
    required this.productModel,
    this.width,
  });

  @override
  Widget build(BuildContext context) {
    final appSetting = context.read<AppSettingCubit>();

    return Container(
      width: width,
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
      child: InkWell(
        onTap: () {
          Navigator.pushReplacementNamed(
              context, RouteNames.productDetailsScreen,
              arguments: productModel.slug);
        },
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildImage(context),
            const SizedBox(height: 2),
            _buildContent(appSetting),
          ],
        ),
      ),
    );
  }

  Widget _buildContent(AppSettingCubit appSetting) {
    double flashPrice = 0.0;
    double offerPrice = 0.0;
    double mainPrice = 0.0;
    final isFlashSale = appSetting.settingModel!.flashSaleProducts
        .contains(FlashSaleProductsModel(productId: productModel.id));
    int flashSaleActive = appSetting.settingModel!.flashSale.status;
    if (productModel.offerPrice != 0) {
      if (productModel.activeVariantModel.isNotEmpty) {
        double p = 0.0;
        for (var i in productModel.activeVariantModel) {
          if (i.activeVariantsItems.isNotEmpty) {
            p += Utils.toDouble(i.activeVariantsItems.first.price.toString());
          }
        }
        offerPrice = p + productModel.offerPrice;
      } else {
        offerPrice = productModel.offerPrice;
      }
    }
    if (productModel.activeVariantModel.isNotEmpty) {
      double p = 0.0;
      for (var i in productModel.activeVariantModel) {
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
    return Container(
      padding: const EdgeInsets.only(left: 10, right: 2),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(
                Icons.star,
                size: 14.0,
                color: Color(0xffF6D060),
              ),
              Utils.horizontalSpace(6.0),
              CustomText(
                  text: '${productModel.averageRating}', isTranslate: false),
            ],
          ),
          const SizedBox(height: 4.0),
          CustomText(
              text: productModel.name,
              textAlign: TextAlign.left,
              maxLine: 1,
              fontWeight: FontWeight.w600,
              height: 1.2,
              fontSize: 16),
          const SizedBox(height: 2.0),
          if (isFlashSale) ...[
            PriceCardWidget(
              price: mainPrice.toString(),
              offerPrice: flashPrice.toString(),
              textSize: 16,
              saleUnitQty: productModel.saleUnitQty,
            ),
          ] else ...[
            PriceCardWidget(
              price: mainPrice.toString(),
              offerPrice: offerPrice.toString(),
              textSize: 16,
              saleUnitQty: productModel.saleUnitQty,
            ),
          ],
          // PriceCardWidget(
          //     price: Utils.formatPrice(productModel.price),
          //     offerPrice: Utils.formatPrice(productModel.offerPrice))
          // Row(
          //   children: [
          //     Text(
          //       Utils.formatPrice(productModel.offerPrice),
          //       style: const TextStyle(
          //           color: redColor,
          //           height: 1.5,
          //           fontSize: 18,
          //           fontWeight: FontWeight.w600),
          //     ),
          //     const SizedBox(width: 10),
          //     Text(
          //       Utils.formatPrice(productModel.price),
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
    );
  }

  Widget _buildImage(BuildContext context) {
    return SizedBox(
      height: 118.0,
      child: Stack(
        fit: StackFit.expand,
        children: [
          ClipRRect(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(4)),
            child: CustomImage(
                path: RemoteUrls.imageUrl(productModel.thumbImage),
                fit: BoxFit.cover),
          ),
          // _buildOfferInPercentage(context),
          Positioned(
            left: 6,
            bottom: 6,
            child: ProductPackBadge(qty: productModel.saleUnitQty),
          ),
          Positioned(
            top: 5.0,
            right: 5.0,
            child: FavoriteButton(productId: productModel.id.toString()),
          ),
        ],
      ),
    );
  }

  Widget _buildOfferInPercentage(BuildContext context) {
    if (productModel.offerPrice != 0) {
      return const Positioned(
        top: 8,
        right: 8,
        child: SizedBox(),
      );
    }

    final percentage =
        Utils.dorpPricePercentage(productModel.price, productModel.offerPrice);

    return Positioned(
      top: 0,
      right: 0,
      child: Container(
        height: 22,
        padding: const EdgeInsets.symmetric(horizontal: 5),
        alignment: Alignment.center,
        decoration: BoxDecoration(
            color: Utils.dynamicPrimaryColor(context).withOpacity(0.6),
            borderRadius:
                const BorderRadius.only(topRight: Radius.circular(2))),
        child: Text(
          percentage,
          style: const TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w700,
            color: blackColor,
          ),
        ),
      ),
    );
  }
}
