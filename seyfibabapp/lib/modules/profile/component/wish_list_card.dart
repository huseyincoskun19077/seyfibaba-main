import 'package:flutter/material.dart';
import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_image.dart';
import '../../../widgets/custom_text.dart';
import '../profile_offer/model/wish_list_model.dart';

class WishListCard extends StatefulWidget {
  const WishListCard({super.key, required this.product});

  final WishListModel product;

  @override
  State<WishListCard> createState() => _WishListCardState();
}

class _WishListCardState extends State<WishListCard> {
  @override
  Widget build(BuildContext context) {
    const double height = 120;
    return InkWell(
      onTap: () {
        Navigator.pushNamed(context, RouteNames.productDetailsScreen,
            arguments: widget.product.slug);
      },
      child: Container(
        height: height,
        color: Colors.white,
        padding: const EdgeInsets.symmetric(horizontal: 4.0),
        child: Row(
          children: [
            Container(
              width: 125.0,
              height: height,
              padding: const EdgeInsets.symmetric(vertical: 5.0)
                  .copyWith(right: 6.0),
              // color: Colors.red,
              child: CustomImage(
                path: RemoteUrls.imageUrl(widget.product.thumbImage),
                fit: BoxFit.contain,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: ListTile(
                contentPadding: EdgeInsets.zero,
                title: CustomText(
                    text: widget.product.name,
                    maxLine: 2,
                    overflow: TextOverflow.ellipsis,
                    fontSize: 14.0,
                    fontWeight: FontWeight.w600),
                subtitle: CustomText(
                    text: Utils.formatPrice(widget.product.price, context),
                    isTranslate: false,
                    color: redColor,
                    fontSize: 16,
                    fontWeight: FontWeight.w600),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
