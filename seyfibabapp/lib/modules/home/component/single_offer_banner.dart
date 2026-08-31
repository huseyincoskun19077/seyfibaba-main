import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:url_launcher/url_launcher.dart';

import '/core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../widgets/custom_text.dart';
import '../../category/controller/cubit/category_cubit.dart';
import '../../category/model/category_navigation_args.dart';
import '../model/banner_model.dart';
import '../widgets/home_theme.dart';

class SingleOfferBanner extends StatelessWidget {
  const SingleOfferBanner({super.key, this.slider});

  final BannerModel? slider;

  bool get _hasLink {
    final link = slider?.link.trim() ?? '';
    final slug = slider?.slug.trim() ?? '';
    return link.isNotEmpty || slug.isNotEmpty;
  }

  bool get _hasTitleOverlay {
    final title = slider?.titleOne.trim() ?? '';
    final subtitle = slider?.titleTwo.trim() ?? '';
    return title.isNotEmpty || subtitle.isNotEmpty;
  }

  Future<void> _handleTap(BuildContext context) async {
    if (!_hasLink || slider == null) return;

    final link = slider!.link.trim();
    final slug = slider!.slug.trim();

    if (link.startsWith('http://') || link.startsWith('https://')) {
      final uri = Uri.tryParse(link);
      if (uri != null && await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
      return;
    }

    if (slug.isNotEmpty) {
      final cCubit = context.read<CategoryCubit>();
      if (cCubit.state.initialPage > 1) cCubit.initPage();
      cCubit
        ..changeTitle(slider?.titleOne ?? 'Category')
        ..clearFilterData();
      if (!context.mounted) return;
      Navigator.pushNamed(
        context,
        RouteNames.singleCategoryProductScreen,
        arguments: CategoryProductArgs(
          slug: slug,
          name: slider?.titleOne ?? '',
        ),
      );
      return;
    }

    if (link.isNotEmpty) {
      final normalized = link.startsWith('/') ? link : '/$link';
      final uri = Uri.tryParse('${RemoteUrls.rootUrl}$normalized');
      if (uri != null && await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (slider == null) return const SizedBox.shrink();

    final content = Container(
      width: double.infinity,
      decoration: HomeTheme.cardDecoration().copyWith(
        image: DecorationImage(
          image: NetworkImage(RemoteUrls.imageUrl(slider!.image)),
          fit: BoxFit.cover,
        ),
      ),
      clipBehavior: Clip.antiAlias,
      child: _hasTitleOverlay || _hasLink
          ? Container(
              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.bottomLeft,
                  end: Alignment.topRight,
                  colors: [
                    Colors.black.withValues(alpha: 0.72),
                    Colors.black.withValues(alpha: 0.28),
                    Colors.transparent,
                  ],
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  if ((slider?.titleOne ?? '').trim().isNotEmpty)
                    Text(
                      slider!.titleOne,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 17,
                        height: 1.25,
                        fontWeight: FontWeight.w800,
                        color: whiteColor,
                      ),
                    ),
                  if ((slider?.titleTwo ?? '').trim().isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      slider!.titleTwo,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 13,
                        height: 1.3,
                        fontWeight: FontWeight.w600,
                        color: Colors.white.withValues(alpha: 0.95),
                      ),
                    ),
                  ],
                  if (_hasLink) ...[
                    const SizedBox(height: 10),
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
                ],
              ),
            )
          : const SizedBox.expand(),
    );

    if (!_hasLink) return content;

    return GestureDetector(
      onTap: () => _handleTap(context),
      child: content,
    );
  }
}
