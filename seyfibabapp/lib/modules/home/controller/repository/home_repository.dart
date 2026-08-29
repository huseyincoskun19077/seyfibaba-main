import 'package:dartz/dartz.dart';

import '/modules/home/model/brand_model.dart';
import '/modules/home/model/product_model.dart';
import '../../../../core/data/datasources/remote_data_source.dart';
import '../../../../core/error/exception.dart';
import '../../../../core/error/failure.dart';
import '../../model/home_model.dart';

abstract class HomeRepository {
  Future<Either<Failure, HomeModel>> getHomeData();
  Future<Either<Failure, List<BrandModel>>> getBrandList();
  Future<Either<Failure, List<ProductModel>>> getHighlightProducts(
      String page, String keyword);

  Future<Either<Failure, List<ProductModel>>> loadMoreProducts(
      String keyword, int page, int perPage);
}

class HomeRepositoryImp extends HomeRepository {
  final RemoteDataSource remoteDataSource;
  HomeRepositoryImp({required this.remoteDataSource});

  @override
  Future<Either<Failure, HomeModel>> getHomeData() async {
    try {
      final result = await remoteDataSource.getHomeData();
      return right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }

  @override
  Future<Either<Failure, List<BrandModel>>> getBrandList() async {
    try {
      final result = await remoteDataSource.getBrandList();
      return right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }

  @override
  Future<Either<Failure, List<ProductModel>>> getHighlightProducts(
      String page, String keyword) async {
    try {
      final result = await remoteDataSource.getHighlightProducts(page, keyword);
      return right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }

  @override
  Future<Either<Failure, List<ProductModel>>> loadMoreProducts(
      String keyword, int page, int perPage) async {
    try {
      final result =
          await remoteDataSource.loadMoreProducts(keyword, page, perPage);
      return right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }
}
