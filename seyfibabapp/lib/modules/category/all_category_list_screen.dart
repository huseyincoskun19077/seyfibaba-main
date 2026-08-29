import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shop_o/modules/home/model/home_category_model.dart';
import 'package:shop_o/utils/constants.dart';

import '/widgets/capitalized_word.dart';
import '../../utils/language_string.dart';
import '../../widgets/fetch_error_text.dart';
import '../../widgets/rounded_app_bar.dart';
import '../home/controller/cubit/product/product_state_model.dart';
import 'component/single_circuler_card.dart';
import 'controller/cubit/category_cubit.dart';

class AllCategoryListScreen extends StatelessWidget {
  const AllCategoryListScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final catCubit = context.read<CategoryCubit>();
    catCubit.getCategoryList();
    return Scaffold(
      backgroundColor: whiteColor,
      appBar: RoundedAppBar(
        titleText: Language.allCategories.capitalizeByWord(),
        onTap: () {
          Navigator.pop(context);
        },
      ),
      body: BlocConsumer<CategoryCubit, ProductStateModel>(
        listener: (context, states) {
          final state = states.catState;
          if (state is CategoryErrorState) {
            if (state.statusCode == 503) {
              catCubit.getCategoryList();
            }
          }
        },
        builder: (context, states) {
          final state = states.catState;
          if (state is CategoryLoadingState) {
            return const Center(child: CircularProgressIndicator());
          } else if (state is CategoryListLoadedState) {
            return LoadedCatView(categories: state.categoryListModel);
          } else if (state is CategoryErrorState) {
            if (state.statusCode == 503) {
              return LoadedCatView(categories: catCubit.categoryList);
            } else {
              return FetchErrorText(text: state.message);
            }
          }
          if (catCubit.categoryList.isNotEmpty) {
            return LoadedCatView(categories: catCubit.categoryList);
          } else {
            return FetchErrorText(text: Language.somethingWentWrong);
          }
        },
      ),
    );
  }
}

class LoadedCatView extends StatelessWidget {
  const LoadedCatView({super.key, required this.categories});

  final List<HomePageCategoriesModel> categories;

  @override
  Widget build(BuildContext context) {
    return GridView.builder(
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 4,
        crossAxisSpacing: 0,
        mainAxisSpacing: 0,
        mainAxisExtent: 130,
      ),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      itemCount: categories.length,
      itemBuilder: (context, index) {
        return CategoryCircleCard(
          categoriesModel: categories[index],
        );
      },
    );
  }
}
