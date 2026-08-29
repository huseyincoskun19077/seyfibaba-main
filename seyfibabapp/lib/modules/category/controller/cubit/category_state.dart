part of 'category_cubit.dart';

abstract class CategoryState extends Equatable {
  const CategoryState();

  @override
  List<Object> get props => [];
}

class CategoryInitialState extends CategoryState {
  const CategoryInitialState();
}

class CategoryLoadingState extends CategoryState {}

class CategoryErrorState extends CategoryState {
  final String message;
  final int statusCode;

  const CategoryErrorState({required this.message, required this.statusCode});

  @override
  List<Object> get props => [message, statusCode];
}

class CategoryLoadedState extends CategoryState {
  final List<ProductModel> categoryProducts;

  const CategoryLoadedState({required this.categoryProducts});

  @override
  List<Object> get props => [categoryProducts];
}

class CategoryMoreLoadedState extends CategoryState {
  final List<ProductModel> categoryProducts;

  const CategoryMoreLoadedState({required this.categoryProducts});

  @override
  List<Object> get props => [categoryProducts];
}

class CategoryListLoadedState extends CategoryState {
  final List<HomePageCategoriesModel> categoryListModel;

  // final List<CategoriesModel> categoryListModel;
  const CategoryListLoadedState({required this.categoryListModel});

  @override
  List<Object> get props => [categoryListModel];
}

class SellerProductState extends CategoryState {
  final SellerProductModel sellerModel;

  const SellerProductState({required this.sellerModel});
}
