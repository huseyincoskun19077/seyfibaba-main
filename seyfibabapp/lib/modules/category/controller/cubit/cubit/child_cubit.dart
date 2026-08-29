import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '/modules/category/controller/repository/category_repository.dart';

import '../../../../home/model/product_model.dart';
import '../../../model/child_category_model.dart';

part 'child_state.dart';

class ChildCubit extends Cubit<ChildCategoryState> {
  ChildCubit(CategoryRepository categoryRepository)
      : _categoryRepository = categoryRepository,
        super(ChildStateLoding());

  final CategoryRepository _categoryRepository;
  late List<ChildCategoryModel> childCategoryList;
  late List<ProductModel> childCategoryProductsList;

  Future<void> getChildCategoryList(String id) async {
    emit(ChildStateLoding());

    final result = await _categoryRepository.getChildCategoryList(id);
    result.fold(
      (failuer) {
        emit(ChildCategoryErrorState(errorMessage: failuer.message));
      },
      (data) {
        childCategoryList = data;

        emit(ChildCategoryListLoadedState(childCategoryList: data));
      },
    );
  }

  Future<void> getChildCategoryProduct(String slug) async {
    emit(ChildStateLoding());

    final result = await _categoryRepository.getChildCategoryProductsLegacy(slug);
    result.fold(
      (failuer) {
        emit(ChildCategoryErrorState(errorMessage: failuer.message));
      },
      (data) {
        childCategoryProductsList = data;
        emit(ChildCategoryProductsLoadedState(childCategoryProducts: data));
      },
    );
  }
}
