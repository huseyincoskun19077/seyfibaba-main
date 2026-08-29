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
import 'controller/cubit/category_cubit.dart';
import 'controller/cubit/cubit/sub_category_cubit.dart';
import 'model/category_navigation_args.dart';

class SubCategoryListScreen extends StatelessWidget {
  const SubCategoryListScreen({super.key, required this.args});

  final CategoryNavigationArgs args;

  void _openAllProducts(BuildContext context) {
    final categoryCubit = context.read<CategoryCubit>();
    categoryCubit.changeTitle(args.name);
    categoryCubit.clearFilterData();
    if (categoryCubit.state.initialPage > 1) {
      categoryCubit.initPage();
    }
    Navigator.pushNamed(
      context,
      RouteNames.singleCategoryProductScreen,
      arguments: CategoryProductArgs(
        slug: args.slug,
        name: args.name,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    context.read<SubCategoryCubit>().getSubCategoryList(args.id.toString());

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
      body: BlocBuilder<SubCategoryCubit, SubCategoryState>(
        builder: (context, state) {
          if (state is SubCategoryLoadingState) {
            return const Center(child: CircularProgressIndicator());
          }
          if (state is SubCategoryErrorState) {
            return FetchErrorText(text: state.errorMessage);
          }
          if (state is SubCategoryListLoadedState) {
            final subCategories = state.subCategoryList;
            final itemCount = subCategories.length + 1;

            return CategoryLevelGrid(
              itemCount: itemCount,
              itemBuilder: (context, index) {
                if (index == subCategories.length) {
                  return CategoryLevelTile(
                    name: Language.allProducts,
                    highlight: true,
                    onTap: () => _openAllProducts(context),
                  );
                }

                final subCategory = subCategories[index];
                return CategoryLevelTile(
                  name: subCategory.name,
                  onTap: () {
                    Navigator.pushNamed(
                      context,
                      RouteNames.childCategoryListScreen,
                      arguments: CategoryNavigationArgs(
                        id: subCategory.id,
                        slug: subCategory.slug,
                        name: subCategory.name,
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
