import 'dart:convert';

import 'package:equatable/equatable.dart';

class CheckoutBodyModel extends Equatable {
  final String shippingAddress;
  final String billingAddress;
  final String shippingMethod;
  final String coupon;

  const CheckoutBodyModel({
    required this.shippingAddress,
    required this.billingAddress,
    required this.shippingMethod,
    required this.coupon,
  });

  CheckoutBodyModel copyWith({
    String? shippingAddress,
    String? billingAddress,
    String? shippingMethod,
    String? coupon,
  }) {
    return CheckoutBodyModel(
      shippingAddress: shippingAddress ?? this.shippingAddress,
      billingAddress: billingAddress ?? this.billingAddress,
      shippingMethod: shippingMethod ?? this.shippingMethod,
      coupon: coupon ?? this.coupon,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'agree_terms_condition': '1',
      'shipping_address_id': shippingAddress,
      'billing_address_id': billingAddress,
      'shipping_method_id': shippingMethod,
      'coupon': coupon,
    };
  }

  factory CheckoutBodyModel.fromMap(Map<String, dynamic> map) {
    return CheckoutBodyModel(
      shippingAddress: map['shipping_address_id'] ?? '',
      billingAddress: map['billing_address_id'] ?? '',
      shippingMethod: map['shipping_method_id'] ?? '',
      coupon: map['coupon'] ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory CheckoutBodyModel.fromJson(String source) =>
      CheckoutBodyModel.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  @override
  List<Object> get props => [shippingAddress, billingAddress, shippingMethod,coupon];
}
