import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../category/controller/cubit/category_cubit.dart';
import '/core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../widgets/custom_image.dart';
import '../model/home_seller_model.dart';

class SingleCircularSeller extends StatelessWidget {
  const SingleCircularSeller({
    super.key,
    required this.seller,
  });

  final HomeSellerModel seller;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () {
        context.read<CategoryCubit>().sellerTitleSlug(seller.shopName, seller.slug);

        Navigator.pushNamed(context, RouteNames.sellerScreen);
      },
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            height: 40.0,width: 40.0,
            decoration: const BoxDecoration(
              shape: BoxShape.circle,
            ),
            child: CustomImage(path: RemoteUrls.imageUrl(seller.logo),fit: BoxFit.fill,),
          ),
          const SizedBox(height: 8),
          Text(
            seller.shopName,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 14),
          ),
        ],
      ),
    );
  }
}
