import 'package:equatable/equatable.dart';

import '../../../model/product_model.dart';

abstract class ProductsState extends Equatable {
  const ProductsState();

  @override
  List<Object> get props => [];
}

class ProductsInitial extends ProductsState {
  const ProductsInitial();
}

class ProductsStateLoading extends ProductsState {}

class ProductsStateMoreDataLoading extends ProductsState {}

class ProductsStateError extends ProductsState {
  final String errorMessage;
  final int statusCode;

  const ProductsStateError(this.errorMessage, this.statusCode);

  @override
  List<Object> get props => [errorMessage];
}

class ProductsStateLoaded extends ProductsState {
  final List<ProductModel> highlightedProducts;

  const ProductsStateLoaded({required this.highlightedProducts});

  @override
  List<Object> get props => [highlightedProducts];
}

class MoreProductsStateLoaded extends ProductsState {
  final List<ProductModel> highlightedProducts;

  const MoreProductsStateLoaded({required this.highlightedProducts});

  @override
  List<Object> get props => [highlightedProducts];
}
