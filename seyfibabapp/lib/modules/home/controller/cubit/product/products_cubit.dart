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

  void nameChange(String name) {
    emit(state.copyWith(name: name));
  }

  Future<void> getHighlightedProduct(String keyword) async {
    print('new-arrival-loaded $keyword');
    emit(state.copyWith(productState: ProductsStateLoading()));

    final result = await _homeRepository.getHighlightProducts(
        state.initialPage.toString(), keyword);
    result.fold(
      (failure) {
        final errors = ProductsStateError(failure.message, failure.statusCode);
        emit(state.copyWith(productState: errors));
      },
      (data) {
        if (state.initialPage == 1) {
          highLightedProducts = data;
          final loaded =
              ProductsStateLoaded(highlightedProducts: highLightedProducts);
          emit(state.copyWith(productState: loaded));
        } else {
          highLightedProducts.addAll(data);
          final loaded =
              MoreProductsStateLoaded(highlightedProducts: highLightedProducts);
          emit(state.copyWith(productState: loaded));
        }
        state.initialPage++;
        if (data.isEmpty && state.initialPage != 1) {
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
