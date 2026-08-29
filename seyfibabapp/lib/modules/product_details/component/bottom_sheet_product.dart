import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/remote_urls.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_image.dart';
import '../../../widgets/custom_text.dart';
import '../../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../../category/component/price_card_widget.dart';
import '../../home/model/product_model.dart';
import '../../setting/model/website_setup_model.dart';
import '../model/active_variant_model.dart';

class BottomSheetProduct extends StatelessWidget {
  const BottomSheetProduct(
      {super.key, required this.product, required this.variantItem});

  final ProductModel product;
  final Set<ActiveVariantModel> variantItem;

  @override
  Widget build(BuildContext context) {
    final appSetting = context.read<AppSettingCubit>();
    double flashPrice = 0.0;
    double offerPrice = 0.0;
    double mainPrice = 0.0;
    final isFlashSale = appSetting.settingModel!.flashSaleProducts
        .contains(FlashSaleProductsModel(productId: product.id));

    if (product.offerPrice != 0) {
      if (variantItem.isNotEmpty) {
        double p = 0.0;
        for (var i in variantItem) {
          if (i.activeVariantsItems.isNotEmpty) {
            p += Utils.toDouble(i.activeVariantsItems.first.price.toString());
          }
        }
        offerPrice = p + product.offerPrice;
      } else {
        offerPrice = product.offerPrice;
      }
    }

    if (variantItem.isNotEmpty) {
      double p = 0.0;
      for (var i in variantItem) {
        if (i.activeVariantsItems.isNotEmpty) {
          p += Utils.toDouble(i.activeVariantsItems.first.price.toString());
        }
      }
      mainPrice = p + product.price;
    } else {
      mainPrice = product.price;
    }

    if (isFlashSale) {
      if (product.offerPrice != 0) {
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
      padding: const EdgeInsets.all(15),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: CustomImage(
              path: RemoteUrls.imageUrl(product.thumbImage),
              fit: BoxFit.cover,
              height: 80,
              width: 80,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CustomText(
                  text: product.name,
                  maxLine: 2,
                  overflow: TextOverflow.ellipsis,
                  height: 1.3,
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                ),
                const SizedBox(height: 4),
                if (isFlashSale) ...[
                  PriceCardWidget(
                    price: mainPrice.toString(),
                    offerPrice: flashPrice.toString(),
                    textSize: 14,
                    saleUnitQty: product.saleUnitQty,
                  ),
                ] else ...[
                  PriceCardWidget(
                    price: mainPrice.toString(),
                    offerPrice: offerPrice.toString(),
                    textSize: 14,
                    saleUnitQty: product.saleUnitQty,
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}
