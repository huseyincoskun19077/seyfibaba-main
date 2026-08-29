// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'dart:convert';

import 'package:equatable/equatable.dart';

class BKashStatus extends Equatable {
  final int status;
  final int cashOnDeliveryStatus;
  final String image;
  // cash_on_delivery_status
  // account_info

  const BKashStatus({
    required this.status,
    required this.cashOnDeliveryStatus,
    required this.image,
  });

  BKashStatus copyWith({
    int? status,
    int? cashOnDeliveryStatus,
    String? image,
  }) {
    return BKashStatus(
      status: status ?? this.status,
      cashOnDeliveryStatus: cashOnDeliveryStatus ?? this.cashOnDeliveryStatus,
      image: image ?? this.image,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'status': status,
      'cash_on_delivery_status': cashOnDeliveryStatus,
      'account_info': image,
    };
  }

  factory BKashStatus.fromMap(Map<String, dynamic> map) {
    return BKashStatus(
      status: map['status'] != null ? int.parse(map['status'].toString()) : 0,
      cashOnDeliveryStatus: map['cash_on_delivery_status'] != null
          ? int.parse(map['cash_on_delivery_status'].toString())
          : 0,
      image: map['image'] ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory BKashStatus.fromJson(String source) =>
      BKashStatus.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object> get props => [status, cashOnDeliveryStatus, image];
}
