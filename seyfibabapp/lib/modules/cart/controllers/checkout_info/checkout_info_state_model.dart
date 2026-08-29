import 'dart:convert';

import 'package:equatable/equatable.dart';
import 'package:shop_o/modules/cart/controllers/checkout_info/checkout_info_cubit.dart';

class CheckoutInfoStateModel extends Equatable {
  final int id;
  final int cityId;
  final String shippingId;
  final String billingId;
  final int addressId;
  final String type;
  final int conditionFrom;
  final int conditionTo;
  final String createdAt;
  final String updatedAt;
  final CheckoutInfoState infoState;

  const CheckoutInfoStateModel({
    this.id = 0,
    this.cityId = 0,
    this.shippingId = '',
    this.type = '',
    this.billingId = '',
    this.conditionFrom = 0,
    this.addressId = 0,
    this.conditionTo = 0,
    this.createdAt = '',
    this.updatedAt = '',
    this.infoState = const CheckoutInfoInitial(),
  });

  CheckoutInfoStateModel copyWith({
    int? id,
    int? cityId,
    String? shippingId,
    String? billingId,
    String? type,
    int? conditionFrom,
    int? conditionTo,
    int? addressId,
    String? createdAt,
    String? updatedAt,
  }) {
    return CheckoutInfoStateModel(
      id: id ?? this.id,
      cityId: cityId ?? this.cityId,
      shippingId: shippingId ?? this.shippingId,
      type: type ?? this.type,
      billingId: billingId ?? this.billingId,
      addressId: addressId ?? this.addressId,
      conditionFrom: conditionFrom ?? this.conditionFrom,
      conditionTo: conditionTo ?? this.conditionTo,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'id': id,
      'city_id': cityId,
      'shipping_rule': shippingId,
      'type': type,
      'shipping_fee': billingId,
      'condition_from': conditionFrom,
      'condition_to': conditionTo,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }

  factory CheckoutInfoStateModel.fromMap(Map<String, dynamic> map) {
    return CheckoutInfoStateModel(
      id: map['id'] as int,
      cityId: map['city_id'] != null ? int.parse(map['city_id'].toString()) : 0,
      shippingId: map['shipping_rule'] ?? '',
      type: map['type'] as String,
      conditionTo: map['condition_to'] != null
          ? int.parse(map['condition_to'].toString())
          : 0,
      createdAt: map['created_at'] ?? '',
      updatedAt: map['updated_at'] ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory CheckoutInfoStateModel.fromJson(String source) =>
      CheckoutInfoStateModel.fromMap(
          json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object> get props {
    return [
      id,
      cityId,
      shippingId,
      billingId,
      addressId,
      type,
      conditionFrom,
      conditionTo,
      createdAt,
      updatedAt,
    ];
  }
}
