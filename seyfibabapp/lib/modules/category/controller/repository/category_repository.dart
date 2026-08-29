import 'package:dartz/dartz.dart';
import '../../../home/controller/cubit/product/product_state_model.dart';
import '../../../home/model/home_category_model.dart';
import '/modules/category/model/sub_category_model.dart';
import '/modules/home/model/product_model.dart';

import '../../../../core/data/datasources/remote_data_source.dart';
import '../../../../core/error/exception.dart';
import '../../../../core/error/failure.dart';
import '../../../seller/seller_model.dart';
import '../../model/child_category_model.dart';
import '../../model/product_categories_model.dart';
import '../../model/product_listing_kind.dart';

abstract class CategoryRepository {
  Future<Either<Failure, ProductCategoriesModel>> getCategoryProducts(
      String slug, int page);

  Future<Either<Failure, ProductCategoriesModel>> getProductListing(
      String slug, ProductListingKind kind, int page);

  Future<Either<Failure, List<ProductModel>>> getFilterProducts(
      ProductStateModel body);

  Future<Either<Failure, List<HomePageCategoriesModel>>> getCategoryList();

  // Future<Either<Failure, List<CategoriesModel>>> getCategoryList();
  Future<Either<Failure, SellerProductModel>> getSellerList(String slug);

  Future<Either<Failure, List<SubCategoryModel>>> getSubCategoryList(String id);

  Future<Either<Failure, List<ProductModel>>> getSubCategoryProductsLegacy(
      String slug);

  Future<Either<Failure, List<ProductModel>>> getChildCategoryProductsLegacy(
      String slug);

  Future<Either<Failure, List<ChildCategoryModel>>> getChildCategoryList(
      String id);

  Future<Either<Failure, List<ProductModel>>> getBrandProducts(String slug);
}

class CategoryRepositoryImp extends CategoryRepository {
  final RemoteDataSource remoteDataSource;

  CategoryRepositoryImp({required this.remoteDataSource});

  @override
  Future<Either<Failure, ProductCategoriesModel>> getCategoryProducts(
      String slug, page) async {
    try {
      final result = await remoteDataSource.getCategoryProducts(slug, page);
      return right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }

  @override
  Future<Either<Failure, List<HomePageCategoriesModel>>>
      getCategoryList() async {
    try {
      final result = await remoteDataSource.getCategoryLists();
      return right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }

  @override
  Future<Either<Failure, SellerProductModel>> getSellerList(String slug) async {
    try {
      final result = await remoteDataSource.getSellerProductLists(slug);
      return right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }

  @override
  Future<Either<Failure, List<SubCategoryModel>>> getSubCategoryList(
      String id) async {
    try {
      final result = await remoteDataSource.getSubCategoryLists(id);
      return right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }

  @override
  Future<Either<Failure, List<ChildCategoryModel>>> getChildCategoryList(
      String id) async {
    try {
      final result = await remoteDataSource.getChildCategoryLists(id);
      return right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }

  @override
  Future<Either<Failure, ProductCategoriesModel>> getProductListing(
      String slug, ProductListingKind kind, int page) async {
    try {
      final ProductCategoriesModel result;
      switch (kind) {
        case ProductListingKind.category:
          result = await remoteDataSource.getCategoryProducts(slug, page);
          break;
        case ProductListingKind.subCategory:
          result = await remoteDataSource.getSubCategoryProducts(slug, page);
          break;
        case ProductListingKind.childCategory:
          result = await remoteDataSource.getChildCategoryProducts(slug, page);
          break;
      }
      return right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }

  @override
  Future<Either<Failure, List<ProductModel>>> getSubCategoryProductsLegacy(
      String slug) async {
    final result = await getProductListing(
        slug, ProductListingKind.subCategory, 1);
    return result.map((model) => model.products);
  }

  @override
  Future<Either<Failure, List<ProductModel>>> getChildCategoryProductsLegacy(
      String slug) async {
    final result =
        await getProductListing(slug, ProductListingKind.childCategory, 1);
    return result.map((model) => model.products);
  }

  @override
  Future<Either<Failure, List<ProductModel>>> getFilterProducts(
      ProductStateModel body) async {
    try {
      final result = await remoteDataSource.filterProducts(body);
      return right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }

  @override
  Future<Either<Failure, List<ProductModel>>> getBrandProducts(
      String slug) async {
    try {
      final result = await remoteDataSource.getBrandProducts(slug);
      return right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }
}
