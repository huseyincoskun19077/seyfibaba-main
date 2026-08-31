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

/// Anasayfa kategorileri — satırda 4 kategori (yatay).
class CategoryGridView extends StatelessWidget {
  const CategoryGridView({super.key, required this.model});

  final HomeModel model;

  static const int _columnsPerRow = 4;

  @override
  Widget build(BuildContext context) {
    final categories = model.homePageCategory;
    if (categories.isEmpty) {
      return const SliverToBoxAdapter(child: SizedBox.shrink());
    }

    return SliverToBoxAdapter(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
        child: LayoutBuilder(
          builder: (context, constraints) {
            final gap = constraints.maxWidth < 340 ? 8.0 : 10.0;
            final rows = <Widget>[];

            for (var i = 0; i < categories.length; i += _columnsPerRow) {
              final rowItems =
                  categories.skip(i).take(_columnsPerRow).toList();

              rows.add(
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: List.generate(_columnsPerRow, (index) {
                    if (index >= rowItems.length) {
                      return Expanded(child: SizedBox(width: gap));
                    }
                    return Expanded(
                      child: Padding(
                        padding: EdgeInsets.only(
                          left: index == 0 ? 0 : gap / 2,
                          right: index == _columnsPerRow - 1 ? 0 : gap / 2,
                        ),
                        child: _CategorySquareCard(category: rowItems[index]),
                      ),
                    );
                  }),
                ),
              );

              if (i + _columnsPerRow < categories.length) {
                rows.add(SizedBox(height: gap));
              }
            }

            return Column(children: rows);
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
      borderRadius: BorderRadius.circular(18),
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
                borderRadius: BorderRadius.circular(18),
                color: HomeTheme.card,
                border: Border.all(color: const Color(0xFFE8E8E8)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.06),
                    blurRadius: 12,
                    offset: const Offset(0, 4),
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
                        size: 28,
                      ),
                    ),
            ),
          ),
          const SizedBox(height: 6),
          CustomText(
            text: category.name,
            maxLine: 2,
            textAlign: TextAlign.center,
            overflow: TextOverflow.ellipsis,
            fontSize: 11,
            fontWeight: FontWeight.w600,
            height: 1.15,
            color: HomeTheme.textDark,
          ),
        ],
      ),
    );
  }
}
