import 'dart:developer';

import 'package:equatable/equatable.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '/modules/category/model/category_model.dart';
import '/modules/category/model/product_listing_kind.dart';
import '/modules/home/model/brand_model.dart';
import '/modules/product_details/model/active_variant_items_model.dart';

import '/modules/category/controller/repository/category_repository.dart';
import '/modules/category/model/product_categories_model.dart';
import '/modules/home/model/product_model.dart';
import '../../../home/controller/cubit/product/product_state_model.dart';
import '../../../home/model/home_category_model.dart';
import '../../../seller/seller_model.dart';

part 'category_state.dart';

class CategoryCubit extends Cubit<ProductStateModel> {
  CategoryCubit(CategoryRepository categoryRepository)
      : _categoryRepository = categoryRepository,
        super(ProductStateModel.init());
  final CategoryRepository _categoryRepository;
  ProductCategoriesModel? productCategoriesModel;
   SellerProductModel? homeSellerModel;
  late List<ProductModel> categoryProducts;
  late List<ProductModel> brandProducts;

  // late List<CategoriesModel> categoryList;
  List<HomePageCategoriesModel> categoryList = [];
  List<ProductModel> catProducts = [];
  ProductListingKind listingKind = ProductListingKind.category;
  String listingSlug = '';
  int listingTotalProducts = 0;

  void changeTitle(String title) {
    emit(state.copyWith(title: title));
  }

  void sellerTitleSlug(String title,String slug) {
    emit(state.copyWith(gender: title,slug: slug));
  }

  void addBrand(BrandModel brands) {
    final updatedItem = List.of(state.brands);
    if (state.brands.contains(brands)) {
      // print('removed ${brands.name}');
      updatedItem.remove(brands);
    } else {
      // print('added ${brands.name}');
      updatedItem.add(brands);
    }
    emit(state.copyWith(brands: updatedItem));
  }

  void addVariantItem(ActiveVariantItemModel brands) {
    final updatedItem = List.of(state.variantItems);
    if (state.variantItems.contains(brands)) {
      updatedItem.remove(brands);
    } else {
      updatedItem.add(brands);
    }
    emit(state.copyWith(variantItems: updatedItem));
  }

  void addCategories(CategoriesModel brands) {
    final updatedItem = List.of(state.categories);
    if (state.categories.contains(brands)) {
      updatedItem.remove(brands);
    } else {
      updatedItem.add(brands);
    }
    emit(state.copyWith(categories: updatedItem));
  }

  void minPriceChange(double value) {
    emit(state.copyWith(
        minPrice: value, catState: const CategoryInitialState()));
  }

  void maxPriceChange(double value) {
    emit(state.copyWith(
        maxPrice: value, catState: const CategoryInitialState()));
  }

  Future<void> getCategoryList() async {
    emit(state.copyWith(catState: CategoryLoadingState()));
    final result = await _categoryRepository.getCategoryList();
    result.fold(
      (failure) {
        final error = CategoryErrorState(
            message: failure.message, statusCode: failure.statusCode);
        emit(state.copyWith(catState: error));
      },
      (data) {
        categoryList = data;
        emit(state.copyWith(
            catState: CategoryListLoadedState(categoryListModel: data)));
      },
    );
  }

  Future<void> getCategoryProduct(String slug) async {
    listingKind = ProductListingKind.category;
    listingSlug = slug;
    await _loadListingProducts();
  }

  Future<void> getProductListing(String slug, ProductListingKind kind) async {
    listingKind = kind;
    listingSlug = slug;
    await _loadListingProducts();
  }

  Future<void> reloadListingProducts() async {
    if (listingSlug.isEmpty) return;
    initPage();
    await _loadListingProducts();
  }

  Future<void> loadMoreListingProducts() async {
    if (listingSlug.isEmpty || state.isListEmpty) return;
    await _loadListingProducts();
  }

  Future<void> _loadListingProducts() async {
    emit(state.copyWith(catState: CategoryLoadingState()));
    final result = await _categoryRepository.getProductListing(
      listingSlug,
      listingKind,
      state.initialPage,
    );
    result.fold(
      (failure) {
        final errors = CategoryErrorState(
            message: failure.message, statusCode: failure.statusCode);
        emit(state.copyWith(catState: errors));
      },
      (data) {
        if (state.initialPage == 1) {
          productCategoriesModel = data;
          catProducts = data.products;
          listingTotalProducts =
              data.totalProducts > 0 ? data.totalProducts : data.products.length;
          final loaded = CategoryLoadedState(categoryProducts: catProducts);
          emit(state.copyWith(catState: loaded));
        } else {
          productCategoriesModel = data;
          catProducts.addAll(data.products);
          if (data.totalProducts > 0) {
            listingTotalProducts = data.totalProducts;
          }
          final loaded = CategoryMoreLoadedState(categoryProducts: catProducts);
          emit(state.copyWith(catState: loaded));
        }
        state.initialPage++;
        if (data.products.isEmpty && state.initialPage != 1) {
          emit(state.copyWith(isListEmpty: true));
        }
      },
    );
  }

  Future<void> loadCategoryProducts(String slug, int page) async {
    emit(state.copyWith(catState: CategoryLoadingState()));

    final result = await _categoryRepository.getCategoryProducts(slug, page);
    result.fold(
      (failure) {
        emit(state.copyWith(
            catState: CategoryErrorState(
                message: failure.message, statusCode: failure.statusCode)));
      },
      (data) {
        productCategoriesModel = data;
        categoryProducts.addAll(data.products);
        //log(productCategoriesModel!.products.length.toString(), name: "CategoryCubit");
        emit(state.copyWith(
            catState: CategoryLoadedState(categoryProducts: categoryProducts)));
      },
    );
  }

  Future<void> getFilterProducts() async {
    //debugPrint('filter-body ${state.toFilterMap()}');
    emit(state.copyWith(catState: CategoryLoadingState()));

    final result = await _categoryRepository.getFilterProducts(state);
    result.fold(
      (failure) {
        final errors = CategoryErrorState(
            message: failure.message, statusCode: failure.statusCode);
        emit(state.copyWith(catState: errors));
      },
      (data) {
        catProducts = data;
        emit(state.copyWith(catState: CategoryLoadedState(categoryProducts: data)));
      },
    );
  }

  Future<void> getBrandProduct(String slug) async {
    emit(state.copyWith(catState: CategoryLoadingState()));

    final result = await _categoryRepository.getBrandProducts(slug);
    result.fold(
      (failure) {
        emit(state.copyWith(
            catState: CategoryErrorState(
                message: failure.message, statusCode: failure.statusCode)));
      },
      (data) {
        brandProducts = data;
        changeTitle(slug.replaceAll('-', ' '));
        emit(state.copyWith(
            catState: CategoryLoadedState(categoryProducts: data)));
      },
    );
  }

  Future<void> getSellerProduct() async {
    emit(state.copyWith(catState: CategoryLoadingState()));

    final result = await _categoryRepository.getSellerList(state.slug);

    result.fold((f) {
      final errors = CategoryErrorState(
          message: f.message, statusCode: f.statusCode);
      emit(state.copyWith(catState: errors));
    }, (sellerData) {
      homeSellerModel = sellerData;
      final errors = SellerProductState(sellerModel: sellerData);
      emit(state.copyWith(catState: errors));
    });
  }

  void initPage() {
    listingTotalProducts = 0;
    emit(state.copyWith(initialPage: 1, isListEmpty: false));
  }

  void clearFilterData() {
    emit(state.copyWith(
        categories: <CategoriesModel>[],
        brands: <BrandModel>[],
        variantItems: <ActiveVariantItemModel>[]));
  }
}
