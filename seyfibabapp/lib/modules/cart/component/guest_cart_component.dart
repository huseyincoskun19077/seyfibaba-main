import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_image.dart';
import '../../../widgets/custom_text.dart';
import '../../product_details/controller/cubit/product_details_cubit.dart';
import '../../category/component/price_card_widget.dart';
import '../model/guest_cart_product.dart';



class GuestCartComponent extends StatelessWidget {
  const GuestCartComponent({super.key, required this.product,this.isVisible,this.visibleQty});

  final GustCartProduct? product;
  final bool ? isVisible;
  final bool ? visibleQty;

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.of(context).size.width - 40;
    final detailCubit = context.read<ProductDetailsCubit>();
    const double height = 168;
    return Container(
      key: ValueKey(product?.productId??0),
      margin: Utils.symmetric(v: 6.0, h: 15.0),
      padding: Utils.symmetric(h: 0.0,v: 6.0),
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
                      arguments: product?.product?.slug??'');
                },
                child: CustomImage(
                  path: RemoteUrls.imageUrl(product?.product?.thumbImage??''),
                  fit: BoxFit.contain,
                ),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Flexible(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Flexible(
                      child: CustomText(
                        text: product?.product?.name??'',
                        textAlign: TextAlign.left,
                        maxLine: 2,
                        fontWeight: FontWeight.w600,
                        height: 1.4,
                      ),
                    ),

                    if(isVisible??true)...[
                      InkWell(
                        onTap: () async {
                          detailCubit.deleteGuestProduct(context,product?.product?.id??0);
                          // debugPrint('product-id ${product?.product?.id??0}');
                        },
                        child: Padding(
                          padding: Utils.only(right: 10.0),
                          child: const Icon(Icons.clear_sharp,
                              size: 20.0, color: redColor),
                        ),
                      ),
                    ],
                  ],
                ),
                if (product?.variants?.isNotEmpty??false) ...[
                  Utils.verticalSpace(6.0),
                  Wrap(children: List.generate(
                      product?.variants?.length??0,
                          (index) => _variantBoxItem(product?.variants?[index].variantItem))),
                ],
                Row(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Flexible(
                      child: PriceCardWidget(
                        price: Utils.guestCartRegularPrice(context, product)
                            .toString(),
                        offerPrice:
                            Utils.guestCart(context, product).toString(),
                        textSize: 14,
                        saleUnitQty: 1,
                      ),
                    ),
                    if ((product?.qty ?? 1) > 1)
                      Padding(
                        padding: const EdgeInsets.only(left: 6, bottom: 2),
                        child: CustomText(
                          text: 'x${product?.qty}',
                          isTranslate: false,
                          color: textGreyColor,
                          fontSize: 12,
                        ),
                      ),
                  ],
                ),
                CustomText(
                  text:
                      'Toplam ${Utils.formatPrice(Utils.guestCartLineTotal(context, product), context)}',
                  isTranslate: false,
                  fontSize: 13,
                  fontWeight: FontWeight.w800,
                  color: const Color(0xFF0F766E),
                ),
                if(isVisible??true)...[
                  Row(
                    // mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      InkWell(
                        onTap: (){
                          ///quantity decrease by 1
                          if((product?.qty??0) > 1){
                            detailCubit.updateQty(product?.product?.id??0,false);
                          }
                        },
                        child: CircleAvatar(
                          radius: 12,
                          backgroundColor: Utils.dynamicPrimaryColor(context),
                          child: const Icon(Icons.remove, color: blackColor),
                        ),
                        // child: Icon(Icons.remove_circle, color: Utils.dynamicPrimaryColor(context)),
                      ),
                      Padding(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 9, vertical: 5),
                        child: Text(
                          '${product?.qty}',
                          style: GoogleFonts.inter(
                              fontSize: 16.0,
                              fontWeight: FontWeight.w600
                          ),
                        ),
                      ),
                      InkWell(
                        splashColor: transparent,
                        splashFactory: NoSplash.splashFactory,
                        onTap: () async {
                          ///quantity increase by 1
                          context.read<ProductDetailsCubit>().updateQty(product?.product?.id??0,true);
                        },
                        child: CircleAvatar(
                          radius: 12,
                          backgroundColor: Utils.dynamicPrimaryColor(context),
                          child: const Icon(Icons.add, color: blackColor),
                        ),
                      ),
                    ],
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _variantBoxItem(GuestVariantItem? variant) {
    return Container(
      padding: Utils.all(value: 6.0),
      margin: Utils.symmetric(h: 0.0, v: 6.0).copyWith(top: 0.0, right: 4.0),
      decoration: BoxDecoration(
        //color: grayColor,
        borderRadius: Utils.borderRadius(r: 4.0),
        border: Border.all(color: borderColor),
      ),
      child: CustomText(
        text: variant?.name??'',
        // text: variant.name,
        color: blackColor,
      ),
    );
  }
}
