import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_font_awesome_web_names/flutter_font_awesome.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../widgets/custom_image.dart';

import '../../../widgets/custom_text.dart';
import '../../home/widgets/home_theme.dart';
import '../../home/model/home_category_model.dart';
import '../controller/cubit/category_cubit.dart';
import '../model/category_navigation_args.dart';

class CategoryCircleCard extends StatelessWidget {
  const CategoryCircleCard({super.key, required this.categoriesModel});

  // final CategoriesModel categoriesModel;
  final HomePageCategoriesModel categoriesModel;

  @override
  Widget build(BuildContext context) {
    final cCubit = context.read<CategoryCubit>();
    return InkWell(
      onTap: () {
        if (cCubit.state.initialPage > 1) {
          cCubit.initPage();
        }
        cCubit..changeTitle(categoriesModel.name)..clearFilterData();

        Navigator.pushNamed(
          context,
          RouteNames.subCategoryListScreen,
          arguments: CategoryNavigationArgs(
            id: categoriesModel.id,
            slug: categoriesModel.slug,
            name: categoriesModel.name,
          ),
        );
      },
      child: SizedBox(
        width: 72,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              height: 64,
              width: 64,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: HomeTheme.card,
                border: Border.all(color: HomeTheme.border),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.04),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Center(
                child: categoriesModel.image.isNotEmpty
                    ? CustomImage(
                        path: RemoteUrls.imageUrl(categoriesModel.image),
                        height: 34,
                        width: 34,
                      )
                    : FaIcon(categoriesModel.icon, color: blackColor, size: 22),
              ),
            ),
            const SizedBox(height: 6),
            SizedBox(
              height: 30,
              child: CustomText(
                text: categoriesModel.name,
                maxLine: 2,
                textAlign: TextAlign.center,
                overflow: TextOverflow.ellipsis,
                fontSize: 11,
                fontWeight: FontWeight.w500,
                height: 1.2,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
