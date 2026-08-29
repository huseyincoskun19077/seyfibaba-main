import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '/core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_text.dart';
import '../../category/controller/cubit/category_cubit.dart';
import '../../category/model/category_navigation_args.dart';
import '../model/banner_model.dart';
import '../widgets/home_theme.dart';

class SingleOfferBanner extends StatelessWidget {
  const SingleOfferBanner({super.key, this.slider});

  final BannerModel? slider;

  @override
  Widget build(BuildContext context) {
    if (slider == null) return const SizedBox.shrink();
    final cCubit = context.read<CategoryCubit>();

    return GestureDetector(
      onTap: () {
        if (cCubit.state.initialPage > 1) cCubit.initPage();
        cCubit
          ..changeTitle(slider?.titleOne ?? 'Category')
          ..clearFilterData();
        Navigator.pushNamed(
          context,
          RouteNames.singleCategoryProductScreen,
          arguments: CategoryProductArgs(
            slug: slider?.slug ?? '',
            name: slider?.titleOne ?? '',
          ),
        );
      },
      child: Container(
        width: double.infinity,
        decoration: HomeTheme.cardDecoration().copyWith(
          image: DecorationImage(
            image: NetworkImage(RemoteUrls.imageUrl(slider!.image)),
            fit: BoxFit.cover,
          ),
        ),
        clipBehavior: Clip.antiAlias,
        child: Container(
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.centerLeft,
              end: Alignment.centerRight,
              colors: [
                Colors.black.withValues(alpha: 0.78),
                Colors.black.withValues(alpha: 0.42),
                Colors.black.withValues(alpha: 0.18),
              ],
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if ((slider?.titleOne ?? '').isNotEmpty)
                Text(
                  slider!.titleOne!,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 18,
                    height: 1.25,
                    fontWeight: FontWeight.w800,
                    color: whiteColor,
                    shadows: const [
                      Shadow(
                        color: Colors.black87,
                        blurRadius: 10,
                        offset: Offset(0, 1),
                      ),
                    ],
                  ),
                ),
              if ((slider?.titleTwo ?? '').isNotEmpty) ...[
                const SizedBox(height: 4),
                Text(
                  slider!.titleTwo!,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 13,
                    height: 1.3,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                    shadows: const [
                      Shadow(
                        color: Colors.black87,
                        blurRadius: 8,
                        offset: Offset(0, 1),
                      ),
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 7,
                ),
                decoration: BoxDecoration(
                  color: HomeTheme.brandYellow,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    CustomText(
                      text: Language.shopNow,
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: blackColor,
                    ),
                    const SizedBox(width: 4),
                    const Icon(
                      Icons.arrow_forward_rounded,
                      size: 16,
                      color: blackColor,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
