// ignore_for_file: must_be_immutable

import 'dart:convert';

import 'package:equatable/equatable.dart';

import '../../../order/model/delivery_man_model.dart';
import 'map_cubit.dart';

class MapStateModel extends Equatable {
  final double latitude;
  final double longitude;
  final double dLatitude;
  final double dLongitude;
  final String location;
  final String updateLocation;
  final String deliveryId;

  bool isOpenSupport;
  final DeliveryManModel? delivery;
  final MapState status;

  MapStateModel({
    this.latitude = 0.0,
    this.longitude = 0.0,
    this.location = '',
    this.dLatitude = 0.0,
    this.dLongitude = 0.0,
    this.updateLocation = '',
    this.deliveryId = '',
    this.isOpenSupport = false,
    required this.delivery,
    this.status = const MapInitial(),
  });

  factory MapStateModel.init() {
    return MapStateModel(
      latitude: 0.0,
      longitude: 0.0,
      location: '',
      updateLocation: '',
      deliveryId: '',
      dLatitude: 0.0,
      dLongitude: 0.0,
      delivery: null,
      isOpenSupport: false,
      status: const MapInitial(),
    );
  }

  MapStateModel copyWith({
    double? latitude,
    double? longitude,
    String? location,
    double? dLatitude,
    double? dLongitude,
    String? updateLocation,
    String? deliveryId,
    DeliveryManModel? delivery,
    bool? isOpenSupport,
    MapState? status,
  }) {
    return MapStateModel(
      latitude: latitude ?? this.latitude,
      longitude: longitude ?? this.longitude,
      location: location ?? this.location,
      dLatitude: dLatitude ?? this.dLatitude,
      dLongitude: dLongitude ?? this.dLongitude,
      updateLocation: updateLocation ?? this.updateLocation,
      deliveryId: deliveryId ?? this.deliveryId,
      delivery: delivery ?? this.delivery,
      isOpenSupport: isOpenSupport ?? this.isOpenSupport,
      status: status ?? this.status,
    );
  }

  Map<String, dynamic> toMap() {
    final result = <String, dynamic>{};
    result.addAll({'current_password': latitude});
    result.addAll({'password': longitude});
    result.addAll({'password_confirmation': location});

    return result;
  }

  factory MapStateModel.fromMap(Map<String, dynamic> map) {
    return MapStateModel(
      delivery: map['deliveryman'] != null
          ? DeliveryManModel.fromMap(map['deliveryman'] as Map<String, dynamic>)
          : null,
      latitude: map['latitude'] != null
          ? double.parse(map['latitude'].toString())
          : 0.0,
      longitude: map['longitude'] != null
          ? double.parse(map['longitude'].toString())
          : 0.0,
    );
  }

  String toJson() => json.encode(toMap());

  factory MapStateModel.fromJson(String source) =>
      MapStateModel.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  String toString() {
    return 'MapStateModel(currentPassword: $latitude, password: $longitude, passwordConfirmation: $location, status: $status)';
  }

  @override
  List<Object?> get props => [
        latitude,
        longitude,
        location,
        dLatitude,
        dLongitude,
        deliveryId,
        updateLocation,
        status,
        isOpenSupport,
        delivery,
      ];
}
