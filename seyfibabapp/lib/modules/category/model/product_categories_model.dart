// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'dart:convert';

import 'package:equatable/equatable.dart';
import 'package:shop_o/modules/category/model/category_model.dart';

import '../../home/model/brand_model.dart';
import '../../home/model/product_model.dart';
import '../../product_details/model/active_variant_model.dart';

class ProductCategoriesModel extends Equatable {
  final List<CategoriesModel> categories;
  final List<ActiveVariantModel> activeVariants;
  final List<BrandModel> brands;
  final List<ProductModel> products;
  final int totalProducts;

  const ProductCategoriesModel({
    required this.categories,
    required this.activeVariants,
    required this.brands,
    required this.products,
    this.totalProducts = 0,
  });

  ProductCategoriesModel copyWith({
    List<CategoriesModel>? categories,
    List<ActiveVariantModel>? activeVariants,
    List<BrandModel>? brands,
    List<ProductModel>? products,
    int? totalProducts,
  }) {
    return ProductCategoriesModel(
      categories: categories ?? this.categories,
      activeVariants: activeVariants ?? this.activeVariants,
      brands: brands ?? this.brands,
      products: products ?? this.products,
      totalProducts: totalProducts ?? this.totalProducts,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'categories': categories.map((x) => x.toMap()).toList(),
      'activeVariants': activeVariants.map((x) => x.toMap()).toList(),
      'brands': brands.map((x) => x.toMap()).toList(),
      'products': products.map((x) => x.toMap()).toList(),
      'total_products': totalProducts,
    };
  }

  factory ProductCategoriesModel.fromMap(Map<String, dynamic> map) {
    final productsPayload = map['products'];
    final productsMap = productsPayload is Map
        ? Map<String, dynamic>.from(productsPayload)
        : <String, dynamic>{};
    final productsList = productsMap['data'] is List
        ? productsMap['data'] as List
        : (productsPayload is List ? productsPayload : const []);

    final total = int.tryParse('${productsMap['total'] ?? ''}') ??
        productsList.length;

    return ProductCategoriesModel(
      categories: List<CategoriesModel>.from(
        (map['categories'] as List<dynamic>? ?? const []).map<CategoriesModel>(
          (x) => CategoriesModel.fromMap(x as Map<String, dynamic>),
        ),
      ),
      activeVariants: List<ActiveVariantModel>.from(
        (map['activeVariants'] as List<dynamic>? ?? const [])
            .map<ActiveVariantModel>(
          (x) => ActiveVariantModel.fromMap(x as Map<String, dynamic>),
        ),
      ),
      brands: List<BrandModel>.from(
        (map['brands'] as List<dynamic>? ?? const []).map<BrandModel>(
          (x) => BrandModel.fromMap(x as Map<String, dynamic>),
        ),
      ),
      products: List<ProductModel>.from(
        productsList.map<ProductModel>(
          (x) => ProductModel.fromMap(x as Map<String, dynamic>),
        ),
      ),
      totalProducts: total,
    );
  }

  String toJson() => json.encode(toMap());

  factory ProductCategoriesModel.fromJson(String source) =>
      ProductCategoriesModel.fromMap(
          json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object> get props =>
      [categories, activeVariants, brands, products, totalProducts];
}
