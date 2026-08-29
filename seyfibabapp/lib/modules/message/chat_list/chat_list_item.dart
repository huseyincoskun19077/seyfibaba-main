import 'package:flutter/material.dart';
import 'package:shop_o/widgets/circle_image.dart';

import '/core/remote_urls.dart';
import '/modules/message/models/seller_messages_dto.dart';
import '/utils/constants.dart';
import '../../../utils/formatting.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_text.dart';

class ChatListItem extends StatelessWidget {
  const ChatListItem({super.key, required this.item, required this.onPressed});

  final SellerDto item;
  final void Function(SellerDto) onPressed;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () => onPressed(item),
      child: Container(
        margin: Utils.symmetric(h: 16.0,v: 4.0),
        padding: Utils.symmetric(v: 14.0,h: 12.0),
        decoration: BoxDecoration(
            color: whiteColor,
            borderRadius: Utils.borderRadius(r: 4.0),
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
            Center(child: CircleImage(image: RemoteUrls.imageUrl(item.shopLogo), size: 50.0)),
            Utils.horizontalSpace(8.0),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      CustomText(
                        text: item.shopName,
                        fontWeight: FontWeight.w600,
                        color: blackColor,
                        fontSize: 16.0,
                        maxLine: 1,
                      ),
                      CustomText(
                        text: utcToLocal(item.messages.last.createdAt),
                        fontWeight: FontWeight.w400,
                        color: grayColor,
                        fontSize: 14.0,
                        maxLine: 1,
                      ),
                    ],
                  ),
                  Utils.verticalSpace(4.0),
                  Flexible(
                    child: CustomText(
                      text: item.messages.last.message.isNotEmpty
                          ? item.messages.last.message
                          : 'Last Message found',
                      fontWeight: FontWeight.w400,
                      color: grayColor,
                      fontSize: 14.0,
                      maxLine: 1,
                    ),
                  )
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
