import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shop_o/utils/constants.dart';

import '../../../widgets/custom_text.dart';
import '../../category/controller/cubit/category_cubit.dart';
import '../../category/model/category_navigation_args.dart';
import '/utils/language_string.dart';
import '/widgets/capitalized_word.dart';
import '/widgets/custom_image.dart';
import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../model/banner_model.dart';

class HotDealBanner extends StatelessWidget {
  const HotDealBanner({
    super.key,
    required this.banner,
    this.height,
  });

  final BannerModel banner;
  final double? height;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: height,
      margin: const EdgeInsets.symmetric(horizontal: 8),
      width: MediaQuery.of(context).size.width,
      child: Stack(
        fit: StackFit.expand,
        children: [
          ClipRRect(
              borderRadius: BorderRadius.circular(4.0),
              child: CustomImage(
                  path: RemoteUrls.imageUrl(banner.image), fit: BoxFit.fill)),
          Positioned.fill(
            child: DecoratedBox(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(4.0),
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    Colors.black.withValues(alpha: 0.62),
                    Colors.black.withValues(alpha: 0.18),
                    Colors.black.withValues(alpha: 0.55),
                  ],
                ),
              ),
            ),
          ),
          Positioned(
            top: 10.0,
            left: 10.0,
            right: 10.0,
            child: Text(
              banner.titleOne,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                color: whiteColor,
                fontWeight: FontWeight.w600,
                fontSize: 16,
                shadows: [
                  Shadow(
                    color: Colors.black87,
                    blurRadius: 8,
                    offset: Offset(0, 1),
                  ),
                ],
              ),
            ),
          ),
          Positioned(
            top: 30.0,
            left: 10.0,
            child: SizedBox(
              width: 180.0,
              height: 50.0,
              child: Text(
                banner.titleTwo,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: whiteColor,
                  fontWeight: FontWeight.w800,
                  fontSize: 18,
                  height: 1.2,
                  shadows: [
                    Shadow(
                      color: Colors.black87,
                      blurRadius: 8,
                      offset: Offset(0, 1),
                    ),
                  ],
                ),
              ),
            ),
          ),
          buildContainerContent(context),
        ],
      ),
    );
  }

  Container buildContainerContent(BuildContext context) {
    final cCubit = context.read<CategoryCubit>();
    return Container(
      padding: const EdgeInsets.only(left: 16, bottom: 16),
      color: Colors.transparent,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.end,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          InkWell(
            onTap: () {
              if (cCubit.state.initialPage > 1) {
                cCubit.initPage();
              }
              cCubit
                ..changeTitle(banner.titleOne)
                ..clearFilterData();

              Navigator.pushNamed(
                context,
                RouteNames.singleCategoryProductScreen,
                arguments: CategoryProductArgs(
                  slug: banner.slug,
                  name: banner.titleOne,
                ),
              );
            },
            child: Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: blackColor,
                borderRadius: BorderRadius.circular(0),
              ),
              child: CustomText(
                  text: Language.shopNow.capitalizeByWord(),
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: whiteColor),
            ),
          )
        ],
      ),
    );
  }
}
