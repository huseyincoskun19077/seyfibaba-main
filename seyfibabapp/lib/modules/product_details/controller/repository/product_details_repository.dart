import 'package:dartz/dartz.dart';
import 'package:flutter/material.dart';
import 'package:shop_o/core/data/datasources/local_data_source.dart';
import '../../../../core/data/datasources/remote_data_source.dart';
import '../../../../core/error/exception.dart';
import '../../../../core/error/failure.dart';
import '../../../cart/model/guest_cart_product.dart';
import '../../model/product_details_model.dart';

abstract class ProductDetailsRepository {
  Future<Either<Failure, ProductDetailsModel>> getProductDetails(String slug);

  Either<Failure, List<GustCartProduct>> getSavedProduct();

  Future<Either<Failure,void>> addGustCartProduct(GustCartProduct product);

  Future<Either<Failure,void>> deleteGuestProduct(int product);

  Future<Either<Failure,void>> updateGustCartProductQty(int id,int qty);

  Future<Either<Failure,bool>> clearGuestProduct();

  Future<Either<Failure,bool>> clearCoupon();
}

class ProductDetailsRepositoryImp extends ProductDetailsRepository {
  final RemoteDataSource remoteDataSource;
  final LocalDataSource localDataSource;

  ProductDetailsRepositoryImp({required this.remoteDataSource,required this.localDataSource});

  @override
  Future<Either<Failure, ProductDetailsModel>> getProductDetails(String slug) async {
    try {
      final result = await remoteDataSource.getProductDetails(slug);
      return right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }

  @override
  Either<Failure, List<GustCartProduct>> getSavedProduct() {
    try {
      final result = localDataSource.guestSavedProduct();
      return Right(result);
    } on DatabaseException catch (e) {
      return Left(DatabaseFailure(e.message));
    }
  }

  @override
  Future<Either<Failure, void>> addGustCartProduct(GustCartProduct product) async{
    try {
      final result = localDataSource.addGustCartProduct(product);
      return Right(result);
    } on DatabaseException catch (e) {
      return Left(DatabaseFailure(e.message));
    }
  }

  @override
  Future<Either<Failure, void>> deleteGuestProduct(int product) async{
    try {
      final result = localDataSource.deleteGustCartProduct(product);
      return Right(result);
    } on DatabaseException catch (e) {
      return Left(DatabaseFailure(e.message));
    }
  }

  @override
  Future<Either<Failure, void>> updateGustCartProductQty(int id,int qty) async{
    try {
      final result = localDataSource.updateGustCartProductQty(id,qty);
      return Right(result);
    } on DatabaseException catch (e) {
      return Left(DatabaseFailure(e.message));
    }
  }

  @override
  Future<Either<Failure, bool>> clearGuestProduct() async{
    try {
      await localDataSource.clearGuestProduct();
      return const Right(true);
    } on DatabaseException catch (e) {
      return Left(DatabaseFailure(e.message));
    }
  }
  @override
  Future<Either<Failure, bool>> clearCoupon() async{
    try {
      await localDataSource.clearCoupon();
      // debugPrint('coupon-clear-from-repository');
      return const Right(true);
    } on DatabaseException catch (e) {
      return Left(DatabaseFailure(e.message));
    }
  }
}
