import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:lazy_load_scrollview/lazy_load_scrollview.dart';

import '/utils/language_string.dart';
import '/modules/category/controller/cubit/category_cubit.dart';
import '../category/component/product_card.dart';
import '../home/controller/cubit/product/product_state_model.dart';

class BannerProductScreen extends StatelessWidget {
  const BannerProductScreen({super.key, this.slug});

  final String? slug;

  @override
  Widget build(BuildContext context) {
    context.read<CategoryCubit>().getCategoryProduct(slug!);
    int page = 1;
    return Scaffold(
      appBar: AppBar(
        title: Text(Language.bannerProducts),
        backgroundColor: Colors.white.withOpacity(0.5),
      ),
      body: BlocBuilder<CategoryCubit, ProductStateModel>(
          builder: (context, states) {
        final state = states.catState;
        if (state is CategoryLoadingState) {
          return const Center(child: CircularProgressIndicator());
        } else if (state is CategoryErrorState) {
          return Center(child: Text(state.message));
        } else if (state is CategoryLoadedState) {
          return LazyLoadScrollView(
            onEndOfPage: () {
              context.read<CategoryCubit>().loadCategoryProducts(slug!, page++);
            },
            scrollOffset: 300,
            child: Column(
              children: [
                Expanded(
                  child: GridView.builder(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 12, vertical: 16),
                      gridDelegate: ProductCard.listingDelegate(
                        context,
                        horizontalPadding: 12,
                        spacing: 8,
                        contentHeight: 96,
                      ),
                      itemCount: state.categoryProducts.length,
                      itemBuilder: (context, index) {
                        return ProductCard(
                            productModel: state.categoryProducts[index]);
                      }),
                )
              ],
            ),
          );
        } else {
          return const SizedBox();
        }
      }),
      // bottomNavigationBar: BlocBuilder<ProductsCubit, ProductsState>(
      //   builder: (context, state) {
      //     if (state is ProductsStateMoreDataLoading) {
      //       return Container(
      //         height: 60,
      //         padding: const EdgeInsets.symmetric(vertical: 10),
      //         child: const Center(
      //           child: CircularProgressIndicator(),
      //         ),
      //       );
      //     }
      //     return const SizedBox();
      //   },
      // ),
    );
  }
}
