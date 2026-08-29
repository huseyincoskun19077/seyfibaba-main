import 'package:flutter/material.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';
import 'package:shop_o/utils/k_images.dart';
import 'package:shop_o/utils/language_string.dart';
import 'package:shop_o/widgets/capitalized_word.dart';

import '../../../core/remote_urls.dart';
import '../../../utils/constants.dart';
import '../../../widgets/custom_text.dart';
import '../model/product_details_model.dart';
import '../../category/component/product_card.dart';
import '../../home/model/product_model.dart';

class SellerInfo extends StatelessWidget {
  const SellerInfo({super.key, this.productDetailsModel});

  final ProductDetailsModel? productDetailsModel;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (productDetailsModel!.sellerProfile != null)
            SellerProfile(productDetailsModel: productDetailsModel!),

          SellerProducts(
            title: Language.products.capitalizeByWord(),
            value: productDetailsModel!.sellerTotalProducts.toString(),
          ),
          SellerProducts(
            title: Language.category.capitalizeByWord(),
            value: productDetailsModel!.product.category!.name,
          ),
          // SellerProducts(
          //   title: Language.tags.capitalizeByWord(),
          //   value: productDetailsModel!.tags,
          // ),

          productDetailsModel!.thisSellerProducts.isNotEmpty
              ? Builder(
                  builder: (context) {
                    final products = productDetailsModel!.thisSellerProducts;
                    final count = products.length > 6 ? 6 : products.length;
                    final delegate = ProductCard.listingDelegate(
                      context,
                      horizontalPadding: 20,
                      spacing: 8,
                      contentHeight: 96,
                    );
                    final rows = (count / 3).ceil();
                    final height = rows * delegate.mainAxisExtent +
                        (rows - 1) * delegate.mainAxisSpacing;
                    return SizedBox(
                      height: height,
                      child: GridView.builder(
                        physics: const NeverScrollableScrollPhysics(),
                        gridDelegate: delegate,
                        itemCount: count,
                        itemBuilder: (context, index) {
                          return ProductCard(
                            productModel: ProductModel.fromMap(
                              products[index].toMap(),
                            ),
                          );
                        },
                      ),
                    );
                  },
                )
              : const SizedBox(),
          // SliverGrid(
          //   gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          //     crossAxisCount: 2,
          //     crossAxisSpacing: 10,
          //     mainAxisSpacing: 10,
          //     mainAxisExtent: 230,
          //   ),
          //   delegate: SliverChildBuilderDelegate(
          //         (BuildContext context, int index) {
          //       return RelatedSingleProductCard(productModel:  productDetailsModel!.thisSellerProducts[index]);
          //     },
          //     childCount: productDetailsModel!.thisSellerProducts.length < 4 ? productDetailsModel!.thisSellerProducts.length : 4,
          //   ),
          // ),
        ],
      ),
    );
  }
}

class SellerProfile extends StatelessWidget {
  const SellerProfile({
    super.key,
    required this.productDetailsModel,
  });

  final ProductDetailsModel? productDetailsModel;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Row(
          children: [
            CircleAvatar(
              radius: 30,
              backgroundColor: grayColor,
              backgroundImage:
                  productDetailsModel!.sellerProfile!.user!.image != null
                      ? NetworkImage(RemoteUrls.imageUrl(
                          productDetailsModel!.sellerProfile!.user!.image!))
                      : const NetworkImage(Kimages.kDefaultUserImage),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  CustomText(
                    text: productDetailsModel?.sellerProfile?.shopName ??
                        'Demo name',
                    isTranslate: false,
                    fontSize: 18.0,
                    fontWeight: FontWeight.w500,
                    height: 1.5,
                    color: blackColor,
                  ),
                  CustomText(
                    text: productDetailsModel?.sellerProfile?.address ??
                        Language.noAddress,
                    fontSize: 14.0,
                    height: 1.5,
                    color: iconGreyColor,
                  ),
                  const SizedBox(height: 6),
                 if(productDetailsModel?.sellerReviewQty != 0)...[
                   Row(
                     children: [
                       RatingBar.builder(
                         initialRating:
                         productDetailsModel?.sellerReviewQty?.toDouble() ??
                             0.0,
                         minRating: 1,
                         direction: Axis.horizontal,
                         allowHalfRating: true,
                         ignoreGestures: true,
                         itemCount: 5,
                         itemSize: 15,
                         itemPadding:
                         const EdgeInsets.symmetric(horizontal: 2.0),
                         itemBuilder: (context, _) => const Icon(
                           Icons.star,
                           color: Colors.amber,
                         ),
                         onRatingUpdate: (rating) {},
                       ),
                       Container(
                           width: 1,
                           margin: const EdgeInsets.symmetric(horizontal: 6),
                           height: 24,
                           color: borderColor),
                       CustomText(
                           text: productDetailsModel?.sellerTotalReview
                               .toString() ??
                               '',
                           fontSize: 13,
                           fontWeight: FontWeight.w300)
                     ],
                   )
                 ]
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        Divider(color: grayBorderColor.withOpacity(0.8)),
        const SizedBox(height: 8),
      ],
    );
  }
}

class SellerProducts extends StatelessWidget {
  const SellerProducts({
    super.key,
    this.title,
    this.value,
  });

  final String? title;
  final String? value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          CustomText(
            text: "$title: ",
            fontSize: 15,
            fontWeight: FontWeight.w500,
            height: 1.2,
            color: blackColor,
          ),
          CustomText(
            text: "$value",
            fontSize: 15,
            fontWeight: FontWeight.w400,
            height: 1.2,
            color: Colors.black,
          )
        ],
      ),
    );
  }

}
