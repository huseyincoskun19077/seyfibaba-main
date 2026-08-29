import 'package:dartz/dartz.dart';

import '../../../../core/data/datasources/remote_data_source.dart';
import '../../../../core/error/exception.dart';
import '../../../../core/error/failure.dart';
import '../../model/delivery_man_model.dart';
import '../../model/order_model.dart';

abstract class OrderRepository {
  Future<Either<Failure, List<OrderModel>>> orderList(String page, String token);

  Future<Either<Failure, OrderModel>> showOrderTracking(String trackNumber, String token);

  Future<Either<Failure, OrderModel>> trackingOrderResponse(String trackNumber);

  Future<Either<Failure, DeliveryManModel>> deliveryLocation(String id,String token);

  Future<Either<Failure, String>> confirmOrderProductDelivery(
      String orderProductId, String token);
}

class OrderRepositoryImp extends OrderRepository {
  final RemoteDataSource _remoteDataSource;

  OrderRepositoryImp(this._remoteDataSource);

  @override
  Future<Either<Failure, List<OrderModel>>> orderList(
      String page, String token) async {
    try {
      final result = await _remoteDataSource.orderList(page, token);

      return Right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }

  @override
  Future<Either<Failure, OrderModel>> showOrderTracking(
      String trackNumber, String token) async {
    try {
      final result =
          await _remoteDataSource.showOrderTracking(trackNumber, token);

      return Right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }

  @override
  Future<Either<Failure, OrderModel>> trackingOrderResponse(
      String trackNumber) async {
    try {
      final result = await _remoteDataSource.trackingOrderResponse(trackNumber);

      return Right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }
  @override
  Future<Either<Failure, DeliveryManModel>> deliveryLocation(String id,String token) async {
    try {
      final result = await _remoteDataSource.deliveryLocation(id,token);
      final data = DeliveryManModel.fromMap(result['data']);
      return Right(data);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }

  @override
  Future<Either<Failure, String>> confirmOrderProductDelivery(
      String orderProductId, String token) async {
    try {
      final result = await _remoteDataSource.confirmOrderProductDelivery(
        orderProductId,
        token,
      );
      return Right(result);
    } on ServerException catch (e) {
      return Left(ServerFailure(e.message, e.statusCode));
    }
  }
}
