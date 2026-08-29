// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'dart:convert';

import 'package:equatable/equatable.dart';

import '../controllers/delivery_charges/delivery_charges_cubit.dart';

class ShippingResponseModel extends Equatable {
  final int id;
  final int cityId;
  final String shippingRule;
  final String type;
  final double shippingFee;
  final int conditionFrom;
  final int conditionTo;
  final String createdAt;
  final String updatedAt;
  final double initialPrice;
  final double distancePrice;
  final DeliveryChargesState dState;

  const ShippingResponseModel({
    required this.id,
    required this.cityId,
    required this.shippingRule,
    required this.type,
    required this.shippingFee,
    required this.conditionFrom,
    required this.conditionTo,
    required this.createdAt,
    required this.updatedAt,
    this.initialPrice = 0.0,
    this.distancePrice = 0.0,
    this.dState = const DeliveryChargesInitial(),
  });

  ShippingResponseModel copyWith({
    int? id,
    int? cityId,
    String? shippingRule,
    String? type,
    double? shippingFee,
    int? conditionFrom,
    int? conditionTo,
    String? createdAt,
    String? updatedAt,
    double? initialPrice,
    double? distancePrice,
    DeliveryChargesState? dState,
  }) {
    return ShippingResponseModel(
      id: id ?? this.id,
      cityId: cityId ?? this.cityId,
      shippingRule: shippingRule ?? this.shippingRule,
      type: type ?? this.type,
      shippingFee: shippingFee ?? this.shippingFee,
      conditionFrom: conditionFrom ?? this.conditionFrom,
      conditionTo: conditionTo ?? this.conditionTo,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      initialPrice: initialPrice ?? this.initialPrice,
      distancePrice: distancePrice ?? this.distancePrice,
      dState: dState ?? this.dState,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'id': id,
      'city_id': cityId,
      'shipping_rule': shippingRule,
      'type': type,
      'shipping_fee': shippingFee,
      'condition_from': conditionFrom,
      'condition_to': conditionTo,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }

  factory ShippingResponseModel.fromMap(Map<String, dynamic> map) {
    return ShippingResponseModel(
      id: map['id'] ?? 0,
      cityId: map['city_id'] != null ? int.parse(map['city_id'].toString()) : 0,
      shippingRule: map['shipping_rule'] ?? '',
      type: map['type'] ?? '',
      shippingFee: map['shipping_fee'] != null
          ? double.parse(map['shipping_fee'].toString())
          : 0.0,
      conditionFrom: map['condition_from'] != null
          ? int.parse(map['condition_from'].toString())
          : 0,
      conditionTo: map['condition_to'] != null
          ? int.parse(map['condition_to'].toString())
          : 0,
      createdAt: map['created_at'] ?? '',
      updatedAt: map['updated_at'] ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory ShippingResponseModel.fromJson(String source) =>
      ShippingResponseModel.fromMap(
          json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  static ShippingResponseModel init() {
    return const ShippingResponseModel(
      id: 0,
      cityId: 0,
      shippingRule: '',
      type: '',
      shippingFee: 0.0,
      conditionFrom: 0,
      conditionTo: 0,
      createdAt: '',
      updatedAt: '',
      initialPrice: 0.0,
      distancePrice: 0.0,
      dState: DeliveryChargesInitial(),
    );
  }

  @override
  List<Object> get props {
    return [
      id,
      cityId,
      shippingRule,
      type,
      shippingFee,
      conditionFrom,
      conditionTo,
      createdAt,
      updatedAt,
      initialPrice,
      distancePrice,
      dState,
    ];
  }
}
