// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'dart:convert';

import 'package:equatable/equatable.dart';

class VendorLocation extends Equatable {
  final double latitude;
  final double longitude;
  final double pricePerKM;
  // cash_on_delivery_status
  // account_info

  const VendorLocation({
    required this.latitude,
    required this.longitude,
    required this.pricePerKM,
  });

  VendorLocation copyWith({
    double? latitude,
    double? longitude,
    double? pricePerKM,
  }) {
    return VendorLocation(
      latitude: latitude ?? this.latitude,
      longitude: longitude ?? this.longitude,
      pricePerKM: pricePerKM ?? this.pricePerKM,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'latitude': latitude,
      'longitude': longitude,
      'pricePerKM': pricePerKM,
    };
  }

  factory VendorLocation.fromMap(Map<String, dynamic> map) {
    return VendorLocation(
      latitude: map['latitude'] != null ? double.parse(map['latitude'].toString()) : 0.0,
      longitude: map['longitude'] != null ? double.parse(map['longitude'].toString()) : 0.0,
      pricePerKM: map['per_km_price_range'] != null ? double.parse(map['per_km_price_range'].toString()) : 0.0,
    );
  }

  String toJson() => json.encode(toMap());

  factory VendorLocation.fromJson(String source) =>
      VendorLocation.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object> get props => [latitude,longitude,pricePerKM];
}
