import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../core/router_name.dart';
import '../../utils/constants.dart';
import '../../utils/language_string.dart';
import '../../widgets/capitalized_word.dart';
import '../../widgets/custom_text.dart';
import '../../widgets/fetch_error_text.dart';
import '../../widgets/rounded_app_bar.dart';
import 'component/category_level_tile.dart';
import 'controller/cubit/cubit/child_cubit.dart';
import 'model/category_navigation_args.dart';

class ChildCategoryListScreen extends StatelessWidget {
  const ChildCategoryListScreen({super.key, required this.args});

  final CategoryNavigationArgs args;

  void _openAllProducts(BuildContext context) {
    Navigator.pushNamed(
      context,
      RouteNames.subCategoryProductScreen,
      arguments: CategoryProductArgs(slug: args.slug, name: args.name),
    );
  }

  @override
  Widget build(BuildContext context) {
    context.read<ChildCubit>().getChildCategoryList(args.id.toString());

    return Scaffold(
      backgroundColor: whiteColor,
      appBar: RoundedAppBar(
        titleText: args.name.capitalizeByWord(),
        onTap: () => Navigator.pop(context),
        options: [
          TextButton(
            onPressed: () => _openAllProducts(context),
            child: CustomText(
              text: Language.allProducts,
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: greenColor,
            ),
          ),
        ],
      ),
      body: BlocBuilder<ChildCubit, ChildCategoryState>(
        builder: (context, state) {
          if (state is ChildStateLoding) {
            return const Center(child: CircularProgressIndicator());
          }
          if (state is ChildCategoryErrorState) {
            return FetchErrorText(text: state.errorMessage);
          }
          if (state is ChildCategoryListLoadedState) {
            final childCategories = state.childCategoryList;
            final itemCount = childCategories.length + 1;

            return CategoryLevelGrid(
              itemCount: itemCount,
              itemBuilder: (context, index) {
                if (index == childCategories.length) {
                  return CategoryLevelTile(
                    name: Language.allProducts,
                    highlight: true,
                    onTap: () => _openAllProducts(context),
                  );
                }

                final childCategory = childCategories[index];
                return CategoryLevelTile(
                  name: childCategory.name,
                  onTap: () {
                    Navigator.pushNamed(
                      context,
                      RouteNames.childCategoryProductScreen,
                      arguments: CategoryProductArgs(
                        slug: childCategory.slug,
                        name: childCategory.name,
                      ),
                    );
                  },
                );
              },
            );
          }

          return FetchErrorText(text: Language.somethingWentWrong);
        },
      ),
    );
  }
}
