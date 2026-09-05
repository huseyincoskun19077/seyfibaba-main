import 'package:dartz/dartz.dart';

import '../../../core/data/datasources/local_data_source.dart';
import '../../../core/data/datasources/remote_data_source.dart';
import '../../../core/error/exception.dart';
import '../../../core/error/failure.dart';

abstract class PaymentRepository {
  Future<Either<Failure, String>> cashOnDelivery(
      Map<String, dynamic> body, Uri uri);

  Future<Either<Failure, Map<String, String>>> bankPay(
      Uri uri, Map<String, dynamic> body);

  Future<Either<Failure, Map<String, dynamic>>> payWithIyzico(
      Uri uri, Map<String, dynamic> body);
}

class PaymentRepositoryImp extends PaymentRepository {
  final RemoteDataSource remoteDataSource;
  final LocalDataSource localDataSource;

  PaymentRepositoryImp(this.remoteDataSource, this.localDataSource);

  @override
  Future<Either<Failure, String>> cashOnDelivery(
      Map<String, dynamic> body, Uri uri) async {
    try {
      final result = await remoteDataSource.cashOnDeliveryPayment(body, uri);
      localDataSource.clearCoupon();
      return Right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }

  @override
  Future<Either<Failure, Map<String, String>>> bankPay(
      Uri uri, Map<String, dynamic> body) async {
    try {
      final result = await remoteDataSource.bankPay(uri, body);
      localDataSource.clearCoupon();
      return Right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    } on InvalidAuthData catch (e) {
      return Left(InvalidAuthData(e.errors));
    }
  }

  @override
  Future<Either<Failure, Map<String, dynamic>>> payWithIyzico(
      Uri uri, Map<String, dynamic> body) async {
    try {
      final result = await remoteDataSource.payWithIyzico(uri, body);
      localDataSource.clearCoupon();
      return Right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }
}
