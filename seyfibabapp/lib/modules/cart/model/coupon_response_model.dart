// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'dart:convert';

import 'package:equatable/equatable.dart';

import '../../authentication/models/checkout_body_model.dart';
import '../../profile/model/address_model.dart';
import '../controllers/checkout/checkout_cubit.dart';
import 'shipping_response_model.dart';

class CouponResponseModel extends Equatable {
  final int id;
  final String name;
  final String code;
  final int offerType;
  final double discount;
  final int maxQuantity;
  final int minPurchasePrice;
  final String expiredDate;
  final int applyQty;
  final double shippingFee;
  final double distancePrice;
  final double totalCheckoutPrice;
  final int status;
  final int currentIndex;
  final int shippingId;
  final String createdAt;//used as order_id
  final String updatedAt;
  final List<ShippingResponseModel> shippings;
  final CheckoutBodyModel? checkoutBody;
  final CheckoutState checkState;

  const CouponResponseModel({
    required this.id,
    required this.name,
    required this.code,
    required this.offerType,
    required this.discount,
    required this.maxQuantity,
    required this.minPurchasePrice,
    required this.expiredDate,
    required this.applyQty,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
     this.checkoutBody,
    this.shippings = const <ShippingResponseModel>[],
    this.shippingFee = 0.0,
    this.distancePrice = 0.0,
    this.totalCheckoutPrice = 0.0,
    this.currentIndex = 0,
    this.shippingId = 0,
    this.checkState = const CheckoutStateInitial(),
  });

  CouponResponseModel copyWith({
    int? id,
    String? name,
    String? code,
    int? offerType,
    double? discount,
    int? maxQuantity,
    int? minPurchasePrice,
    String? expiredDate,
    int? applyQty,
    double? shippingFee,
    double? distancePrice,
    double? totalCheckoutPrice,
    int? status,
    int? currentIndex,
    int? shippingId,
    String? createdAt,
    String? updatedAt,
    CheckoutBodyModel? checkoutBody,
    List<ShippingResponseModel>? shippings,
    CheckoutState? checkState,
  }) {
    return CouponResponseModel(
      id: id ?? this.id,
      name: name ?? this.name,
      code: code ?? this.code,
      offerType: offerType ?? this.offerType,
      discount: discount ?? this.discount,
      maxQuantity: maxQuantity ?? this.maxQuantity,
      minPurchasePrice: minPurchasePrice ?? this.minPurchasePrice,
      expiredDate: expiredDate ?? this.expiredDate,
      applyQty: applyQty ?? this.applyQty,
      shippingFee: shippingFee ?? this.shippingFee,
      distancePrice: distancePrice ?? this.distancePrice,
      totalCheckoutPrice: totalCheckoutPrice ?? this.totalCheckoutPrice,
      status: status ?? this.status,
      currentIndex: currentIndex ?? this.currentIndex,
      shippingId: shippingId ?? this.shippingId,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      checkoutBody: checkoutBody ?? this.checkoutBody,
      shippings: shippings ?? this.shippings,
      checkState: checkState ?? this.checkState,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'id': id,
      'name': name,
      'code': code,
      'offer_type': offerType,
      'discount': discount,
      'max_quantity': maxQuantity,
      'min_purchase_price': minPurchasePrice,
      'expired_date': expiredDate,
      'apply_qty': applyQty,
      'status': status,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }

  factory CouponResponseModel.fromMap(Map<String, dynamic> map) {
    return CouponResponseModel(
      id: map['id'] ?? 0,
      name: map['name'] ?? "",
      code: map['code'] ?? "",
      offerType: map['offer_type'] != null
          ? int.parse(map['offer_type'].toString())
          : 0,
      discount:
          map['discount'] != null ? double.parse(map['discount'].toString()) : 0.0,
      maxQuantity: map['max_quantity'] != null
          ? int.parse(map['max_quantity'].toString())
          : 0,
      minPurchasePrice: map['min_purchase_price'] != null
          ? int.parse(map['min_purchase_price'].toString())
          : 0,
      expiredDate: map['expired_date'] ?? "",
      applyQty:
          map['apply_qty'] != null ? int.parse(map['apply_qty'].toString()) : 0,
      status: map['status'] != null ? int.parse(map['status'].toString()) : 0,
      createdAt: map['created_at'] ?? "",
      updatedAt: map['updated_at'] ?? "",
    );
  }

  String toJson() => json.encode(toMap());

  factory CouponResponseModel.fromJson(String source) =>
      CouponResponseModel.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  bool get stringify => true;

  static CouponResponseModel init(){
    return const CouponResponseModel(
      id : 0,
      name : '',
      code : '',
      offerType : 0,
      discount : 0.0,
      maxQuantity : 0,
      minPurchasePrice : 0,
      expiredDate : '',
      applyQty : 0,
      shippingFee : 0.0,
      distancePrice : 0.0,
      totalCheckoutPrice : 0.0,
      status : 0,
      currentIndex : -1,
      shippingId : 0,
      createdAt : '',
      updatedAt : '',
      checkoutBody : null,
      shippings : <ShippingResponseModel>[],
      checkState : CheckoutStateInitial(),
    );
  }

  @override
  List<Object?> get props {
    return [
      id,
      name,
      code,
      offerType,
      discount,
      maxQuantity,
      minPurchasePrice,
      expiredDate,
      applyQty,
      status,
      currentIndex,
      shippingId,
      createdAt,
      updatedAt,
      shippings,
      shippingFee,
      distancePrice,
      totalCheckoutPrice,
      checkoutBody,
      checkState,
    ];
  }
}
