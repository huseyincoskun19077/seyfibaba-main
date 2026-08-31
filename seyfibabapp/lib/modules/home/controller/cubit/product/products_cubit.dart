import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../model/product_model.dart';
import '../../repository/home_repository.dart';
import 'product_state_model.dart';
import 'products_state.dart';

class ProductsCubit extends Cubit<ProductStateModel> {
  ProductsCubit(HomeRepository homeRepository)
      : _homeRepository = homeRepository,
        super(ProductStateModel.init());

  final HomeRepository _homeRepository;
  List<ProductModel> highLightedProducts = [];
  int highlightTotalProducts = 0;
  String? filterCategorySlug;
  String? filterSubCategorySlug;

  void nameChange(String name) {
    emit(state.copyWith(name: name));
  }

  void resetCategoryFilters() {
    filterCategorySlug = null;
    filterSubCategorySlug = null;
  }

  void applyCategoryFilters({
    String? categorySlug,
    String? subCategorySlug,
  }) {
    filterCategorySlug = categorySlug;
    filterSubCategorySlug = subCategorySlug;
    initPage();
    highLightedProducts = [];
  }

  Future<void> getHighlightedProduct(String keyword) async {
    emit(state.copyWith(productState: ProductsStateLoading()));

    final result = await _homeRepository.getHighlightProducts(
      state.initialPage.toString(),
      keyword,
      categorySlug: filterCategorySlug,
      subCategorySlug: filterSubCategorySlug,
    );
    result.fold(
      (failure) {
        final errors = ProductsStateError(failure.message, failure.statusCode);
        emit(state.copyWith(productState: errors));
      },
      (data) {
        if (state.initialPage == 1) {
          highLightedProducts = data.products;
          highlightTotalProducts = data.total;
          final loaded =
              ProductsStateLoaded(highlightedProducts: highLightedProducts);
          emit(state.copyWith(productState: loaded));
        } else {
          highLightedProducts.addAll(data.products);
          highlightTotalProducts = data.total;
          final loaded =
              MoreProductsStateLoaded(highlightedProducts: highLightedProducts);
          emit(state.copyWith(productState: loaded));
        }
        state.initialPage++;
        if (data.products.isEmpty && state.initialPage != 1) {
          emit(state.copyWith(isListEmpty: true));
        }
      },
    );
  }

  // Future<void> loadMoreData(String keyword, int page, int perPage) async {
  //   emit(state.copyWith(productState: ProductsStateMoreDataLoading()));
  //
  //   final result =
  //       await _homeRepository.loadMoreProducts(keyword, page, perPage);
  //   result.fold(
  //     (failure) {
  //       final errors = ProductsStateError(
  //           errorMessage: failure.message, statusCode: failure.statusCode);
  //       emit(state.copyWith(productState: errors));
  //     },
  //     (data) {
  //       if (data.isNotEmpty) {
  //         hightlightedProducts.addAll(data);
  //       } else {
  //         hightlightedProducts = data;
  //       }
  //
  //       emit(state.copyWith(
  //           productState: ProductsStateLoaded(
  //               highlightedProducts: hightlightedProducts)));
  //     },
  //   );
  // }

  void initPage() {
    //print('reset-page');
    emit(state.copyWith(initialPage: 1, isListEmpty: false));
  }
}
