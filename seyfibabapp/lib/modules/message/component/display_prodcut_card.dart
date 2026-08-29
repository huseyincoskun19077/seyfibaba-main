import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shop_o/utils/constants.dart';
import '../../../core/remote_urls.dart';
import '../../../widgets/custom_text.dart';
import '../../product_details/model/product_details_model.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_image.dart';
import '../../../widgets/primary_button.dart';
import '../controller/bloc/bloc/chat_bloc.dart';
import '../controller/cubit/inbox_cubit.dart';
import '../models/send_message_model.dart';

class DisplayProductCard extends StatelessWidget {
  const DisplayProductCard(
      {super.key, required this.productDetailsModel, required this.onChange});

  final ProductDetailsModel productDetailsModel;
  final ValueChanged<bool> onChange;

  @override
  Widget build(BuildContext context) {
    final chatBloc = context.read<ChatBloc>();
    return Container(
      height: 120,
      width: double.infinity,
      padding: Utils.only(right: 10.0),
      decoration: BoxDecoration(
          color: whiteColor,
          border: Border.all(color: blackColor),
          borderRadius: Utils.borderRadius(r: 12.0)),
      margin: const EdgeInsets.symmetric(horizontal: 20),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
              height: 80.0,
              width: 80.0,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(4),
                color: Colors.white,
              ),
              child: CustomImage(
                path:
                    RemoteUrls.imageUrl(productDetailsModel.product.thumbImage),
                fit: BoxFit.contain,
              )),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  // mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Icon(
                      Icons.star,
                      size: 14.0,
                      color: yellowColor,
                    ),
                    const SizedBox(width: 4),
                    CustomText(
                      text:productDetailsModel.product.averageRating.toStringAsFixed(1),
                    ),
                  ],
                ),
                CustomText(text:
                  productDetailsModel.product.name,
                    fontWeight: FontWeight.w600,
                    fontSize: 1.06,
                    color: blackColor
                ),
                CustomText(text:
                  // Utils.formatPrice(Utils.productPrice(context, ), context),
                  Utils.formatPrice(
                      productDetailsModel.product.offerPrice != 0
                          ? productDetailsModel.product.offerPrice
                          : productDetailsModel.product.price,
                      context),
                    fontWeight: FontWeight.w600,
                    fontSize: 16.0,
                    color: redColor,
                  isTranslate: false,
                ),
              ],
            ),
          ),
          SizedBox(
            height: 40.0,
            width: 100.0,
            child: PrimaryButton(
                borderRadiusSize: 4,
                text: "Send",
                bgColor: blackColor,
                textColor: whiteColor,
                onPressed: () {
                  chatBloc.add(
                    SendMsgData(
                      SendMsgModel(
                        sellerId: productDetailsModel
                            .sellerProfile!.sellerUserId
                            .toString(),
                        message: "",
                        productId: productDetailsModel.product.id.toString(),
                      ),
                    ),
                  );
                  context.read<InboxCubit>().reset();
                  onChange(true);
                }),
          ),
        ],
      ),
    );
  }
}
