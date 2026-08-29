import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_font_awesome_web_names/flutter_font_awesome.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../widgets/custom_image.dart';
import '../../../widgets/custom_text.dart';
import '../../category/controller/cubit/category_cubit.dart';
import '../../category/model/category_navigation_args.dart';
import '../model/home_category_model.dart';
import '../model/home_model.dart';
import '../widgets/home_theme.dart';

/// Anasayfa kategorileri — web ile aynı: 2 üst + 2 alt, kare kartlar.
class CategoryGridView extends StatelessWidget {
  const CategoryGridView({super.key, required this.model});

  final HomeModel model;

  @override
  Widget build(BuildContext context) {
    final categories = model.homePageCategory.take(4).toList();
    if (categories.isEmpty) {
      return const SliverToBoxAdapter(child: SizedBox.shrink());
    }

    return SliverToBoxAdapter(
      child: Padding(
        padding: EdgeInsets.fromLTRB(
          16,
          8,
          16,
          12,
        ),
        child: LayoutBuilder(
          builder: (context, constraints) {
            final gap = constraints.maxWidth < 340 ? 8.0 : 12.0;
            return Column(
              children: [
                Row(
                  children: [
                    Expanded(
                      child: categories.isNotEmpty
                          ? _CategorySquareCard(category: categories[0])
                          : const SizedBox.shrink(),
                    ),
                    SizedBox(width: gap),
                    Expanded(
                      child: categories.length > 1
                          ? _CategorySquareCard(category: categories[1])
                          : const SizedBox.shrink(),
                    ),
                  ],
                ),
                if (categories.length > 2) ...[
                  SizedBox(height: gap),
                  Row(
                    children: [
                      Expanded(
                        child: _CategorySquareCard(category: categories[2]),
                      ),
                      SizedBox(width: gap),
                      Expanded(
                        child: categories.length > 3
                            ? _CategorySquareCard(category: categories[3])
                            : const SizedBox.shrink(),
                      ),
                    ],
                  ),
                ],
              ],
            );
          },
        ),
      ),
    );
  }
}

class _CategorySquareCard extends StatelessWidget {
  const _CategorySquareCard({required this.category});

  final HomePageCategoriesModel category;

  @override
  Widget build(BuildContext context) {
    final cCubit = context.read<CategoryCubit>();

    return InkWell(
      borderRadius: BorderRadius.circular(28),
      onTap: () {
        if (cCubit.state.initialPage > 1) {
          cCubit.initPage();
        }
        cCubit
          ..changeTitle(category.name)
          ..clearFilterData();

        Navigator.pushNamed(
          context,
          RouteNames.subCategoryListScreen,
          arguments: CategoryNavigationArgs(
            id: category.id,
            slug: category.slug,
            name: category.name,
          ),
        );
      },
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          AspectRatio(
            aspectRatio: 1,
            child: Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(28),
                color: HomeTheme.card,
                border: Border.all(color: const Color(0xFFE8E8E8)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.06),
                    blurRadius: 18,
                    offset: const Offset(0, 5),
                  ),
                ],
              ),
              clipBehavior: Clip.antiAlias,
              child: category.image.isNotEmpty
                  ? SizedBox.expand(
                      child: CustomImage(
                        path: RemoteUrls.imageUrl(category.image),
                        fit: BoxFit.cover,
                      ),
                    )
                  : Center(
                      child: FaIcon(
                        category.icon,
                        color: blackColor,
                        size: 42,
                      ),
                    ),
            ),
          ),
          const SizedBox(height: 8),
          CustomText(
            text: category.name,
            maxLine: 2,
            textAlign: TextAlign.center,
            overflow: TextOverflow.ellipsis,
            fontSize: 13,
            fontWeight: FontWeight.w600,
            height: 1.2,
            color: HomeTheme.textDark,
          ),
        ],
      ),
    );
  }
}
