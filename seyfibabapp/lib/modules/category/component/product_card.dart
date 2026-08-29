import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../widgets/custom_text.dart';
import '/modules/setting/model/website_setup_model.dart';
import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_image.dart';
import '../../../widgets/favorite_button.dart';
import '../../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../../home/model/product_model.dart';
import 'price_card_widget.dart';

class ProductCard extends StatelessWidget {
  final ProductModel productModel;
  final double? width;

  const ProductCard({super.key, required this.productModel, this.width});

  static const listingColumns = 3;

  static SliverGridDelegateWithFixedCrossAxisCount listingDelegate(
    BuildContext context, {
    double horizontalPadding = 12,
    double spacing = 8,
    double contentHeight = 96,
  }) {
    final width = MediaQuery.sizeOf(context).width;
    final usable =
        width - horizontalPadding * 2 - spacing * (listingColumns - 1);
    final tileW = usable / listingColumns;
    return SliverGridDelegateWithFixedCrossAxisCount(
      crossAxisCount: listingColumns,
      crossAxisSpacing: spacing,
      mainAxisSpacing: spacing,
      mainAxisExtent: tileW + contentHeight,
    );
  }

  @override
  Widget build(BuildContext context) {
    final appSetting = context.read<AppSettingCubit>();
    return ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: Container(
        width: width,
        decoration: BoxDecoration(
          color: whiteColor,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFFE8E8ED)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 10,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: InkWell(
          onTap: () {
            Navigator.pushNamed(context, RouteNames.productDetailsScreen,
                arguments: productModel.slug);
          },
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              AspectRatio(
                aspectRatio: 1,
                child: _buildImage(context),
              ),
              Expanded(
                child: _buildContent(context, appSetting),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildContent(BuildContext context, AppSettingCubit appSetting) {
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

    return Padding(
      padding: const EdgeInsets.fromLTRB(6, 4, 6, 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.start,
        mainAxisSize: MainAxisSize.max,
        children: [
          CustomText(
            text: productModel.name,
            maxLine: 2,
            overflow: TextOverflow.ellipsis,
            fontWeight: FontWeight.w600,
            height: 1.15,
            fontSize: 11,
          ),
          const SizedBox(height: 3),
          if (isFlashSale)
            PriceCardWidget(
              price: mainPrice.toString(),
              offerPrice: flashPrice.toString(),
              textSize: 13.0,
              saleUnitQty: productModel.saleUnitQty,
            )
          else
            PriceCardWidget(
              price: mainPrice.toString(),
              offerPrice: offerPrice.toString(),
              textSize: 13.0,
              saleUnitQty: productModel.saleUnitQty,
            ),
        ],
      ),
    );
  }

  Widget _buildImage(BuildContext context) {
    return Stack(
      fit: StackFit.expand,
      children: [
        ClipRRect(
          borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
          child: ColoredBox(
            color: const Color(0xFFF4F4F6),
            child: CustomImage(
              path: RemoteUrls.imageUrl(productModel.thumbImage),
              fit: BoxFit.cover,
            ),
          ),
        ),
        _buildOfferInPercentage(context),
        Positioned(
          left: 8,
          bottom: 8,
          child: ProductPackBadge(qty: productModel.saleUnitQty),
        ),
        Positioned(
          top: 5.0,
          right: 5.0,
          child: FavoriteButton(productId: productModel.id.toString()),
        ),
      ],
    );
  }

  Widget _buildOfferInPercentage(BuildContext context) {
    if (productModel.offerPrice == 0) {
      return const SizedBox.shrink();
    }

    final percentage =
        Utils.dorpPricePercentage(productModel.price, productModel.offerPrice);
    if (percentage.isEmpty) return const SizedBox.shrink();

    return Positioned(
      top: 8,
      left: 8,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
        decoration: BoxDecoration(
          color: redColor,
          borderRadius: BorderRadius.circular(4),
        ),
        child: Text(
          percentage,
          style: const TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w700,
            color: whiteColor,
          ),
        ),
      ),
    );
  }
}
