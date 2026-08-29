import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../widgets/custom_text.dart';
import '/modules/animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_image.dart';
import '../../animated_splash_screen/controller/currency/currency_cubit.dart';
import '../../category/component/price_card_widget.dart';
import '../model/cart_product_model.dart';

class CheckoutSingleItem extends StatelessWidget {
  const CheckoutSingleItem(
      {super.key, required this.product, required this.appSetting});

  final CartProductModel product;
  final AppSettingCubit appSetting;

  @override
  Widget build(BuildContext context) {
    final c = context.read<CurrencyCubit>().state;
    final width = MediaQuery.of(context).size.width - 40;
    // if (product.variants.isNotEmpty) {
    //   print('text-variants ${product.variants.first.varientItem!.name}');
    // } else {
    //   print('text-variants-null ${product.variants}');
    // }
    const double height = 120;
    return Container(
      margin: Utils.only(bottom: 8.0),
      padding: Utils.symmetric(h: 0.0, v: 5.0),
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
        children: [
          SizedBox(
            height: height - 2,
            width: width / 2.7,
            child: ClipRRect(
              borderRadius: Utils.borderRadius(r: 6.0),
              child: InkWell(
                onTap: () {
                  Navigator.pushNamed(context, RouteNames.productDetailsScreen,
                      arguments: product.product.slug);
                },
                child: CustomImage(
                  path: RemoteUrls.imageUrl(product.product.thumbImage),
                  fit: BoxFit.contain,
                ),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: CustomText(
                      text: product.product.name,
                      maxLine: 2,
                      overflow: TextOverflow.ellipsis,
                      fontWeight: FontWeight.w600),
                ),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Expanded(
                      child: PriceCardWidget(
                        price: Utils.cartProductRegularPrice(context, product)
                            .toString(),
                        offerPrice: Utils.cartProductPrice(context, product)
                            .toString(),
                        textSize: 14,
                        saleUnitQty: product.product.saleUnitQty,
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.only(right: 8, bottom: 2),
                      child: CustomText(
                        text: 'x ${product.qty}',
                        isTranslate: false,
                        fontSize: 12.0,
                        color: textGreyColor,
                      ),
                    ),
                  ],
                ),
                CustomText(
                  text:
                      'Toplam ${Utils.formatPrice(Utils.cartLineTotal(context, product), context)}',
                  isTranslate: false,
                  fontSize: 13,
                  fontWeight: FontWeight.w800,
                  color: const Color(0xFF0F766E),
                ),
                // Wrap(
                //   children: product.variants
                //       .map(
                //         (e) => Text(
                //           '${e.varientItem!.productVariantName} : ${e.varientItem!.name ?? ''}, ',
                //           style: const TextStyle(fontSize: 10),
                //         ),
                //       )
                //       .toList(),
                // )
              ],
            ),
          ),
        ],
      ),
    );
  }
}
