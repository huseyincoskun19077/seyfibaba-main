import 'package:chat_bubbles/bubbles/bubble_normal.dart';
import 'package:dotted_border/dotted_border.dart';
import 'package:flutter/material.dart';

import '../../../widgets/custom_text.dart';
import '/core/remote_urls.dart';
import '/widgets/custom_image.dart';
import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../models/seller_messages_dto.dart';

class MessageComponent extends StatelessWidget {
  const MessageComponent({super.key, required this.element});

  final MessagesDto element;

  @override
  Widget build(BuildContext context) {
    bool isMe = element.sendBy == "customer";
    return SizedBox(
      width: double.infinity,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: element.product == null
            ? BubbleNormal(
                text: element.message,
                isSender: isMe,
                sent: false,
                delivered: false,
                seen: false,
                color: isMe
                    ? Utils.dynamicPrimaryColor(context)
                    : const Color(0xffE8EEF2),
                tail: true,
                textStyle: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w500,
                  color: isMe ? Colors.black : blackColor,
                ),
              )
            : InboxProductContainer(isMe: isMe, element: element),
      ),
    );
  }
}

class InboxProductContainer extends StatelessWidget {
  const InboxProductContainer(
      {super.key, required this.isMe, required this.element});

  final bool isMe;
  final MessagesDto element;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: isMe ? Alignment.bottomRight : Alignment.bottomLeft,
      child: Padding(
        padding: EdgeInsets.only(right: isMe ? 20 : 0, bottom: 8, top: 8),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 20),
          constraints:
              BoxConstraints(maxWidth: MediaQuery.of(context).size.width * .65),
          decoration: BoxDecoration(
              border: Border.all(color: Utils.dynamicPrimaryColor(context)),
              color: Utils.dynamicPrimaryColor(context).withOpacity(0.08),
              borderRadius: Utils.borderRadius()),
          width: 500.0,
          child: Row(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SizedBox(
                height: 60,
                width: 60,
                child: CustomImage(
                  path: RemoteUrls.imageUrl(element.product!.thumbImage),
                  fit: BoxFit.contain,
                ),
              ),
              const SizedBox(width: 20),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      // mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Icon(
                          Icons.star,
                          size: 14,
                          color: Color(0xffF6D060),
                        ),
                        const SizedBox(width: 4),
                        CustomText(
                          text: double.parse(
                                  element.product!.averageRating.toString())
                              .toStringAsFixed(1),
                          fontSize: 14,
                          height: 1.25,
                          isTranslate: false,
                        ),
                      ],
                    ),
                    const SizedBox(height: 5),
                    CustomText(
                      text: element.product?.name ?? 'No name found',
                      maxLine: 2,
                      color: blackColor,
                      fontSize: 16.0,
                      fontWeight: FontWeight.w600,
                    ),
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
