import 'dart:convert';

import 'package:equatable/equatable.dart';

class OrderProductCargoModel extends Equatable {
  final String carrierName;
  final String trackingNumber;
  final String? trackingUrl;
  final String? status;

  const OrderProductCargoModel({
    required this.carrierName,
    required this.trackingNumber,
    this.trackingUrl,
    this.status,
  });

  factory OrderProductCargoModel.fromMap(Map<String, dynamic>? map) {
    if (map == null) {
      return const OrderProductCargoModel(
        carrierName: '',
        trackingNumber: '',
      );
    }
    return OrderProductCargoModel(
      carrierName: map['carrier_name']?.toString() ?? '',
      trackingNumber: map['tracking_number']?.toString() ?? '',
      trackingUrl: map['tracking_url']?.toString(),
      status: map['status']?.toString(),
    );
  }

  bool get hasTracking => trackingNumber.isNotEmpty;

  @override
  List<Object?> get props =>
      [carrierName, trackingNumber, trackingUrl, status];
}

class OrderedProductModel extends Equatable {
  final int id;
  final int orderId;
  final int productId;
  final int sellerId;
  final String productName;
  final double unitPrice;
  final double vat;
  final int qty;
  final bool userHasReviewed;
  final String? deliveredAt;
  final String? customerConfirmedAt;
  final String? autoConfirmedAt;
  final OrderProductCargoModel? cargo;
  final String thumbImage;
  final String createdAt;
  final String updatedAt;

  const OrderedProductModel({
    required this.id,
    required this.orderId,
    required this.productId,
    required this.sellerId,
    required this.productName,
    required this.unitPrice,
    required this.vat,
    required this.qty,
    this.userHasReviewed = false,
    this.deliveredAt,
    this.customerConfirmedAt,
    this.autoConfirmedAt,
    this.cargo,
    this.thumbImage = '',
    required this.createdAt,
    required this.updatedAt,
  });

  bool get isDelivered => deliveredAt != null && deliveredAt!.isNotEmpty;

  bool get isCustomerConfirmed =>
      (customerConfirmedAt != null && customerConfirmedAt!.isNotEmpty) ||
      (autoConfirmedAt != null && autoConfirmedAt!.isNotEmpty);

  bool get canConfirmDelivery => isDelivered && !isCustomerConfirmed;

  bool get canWriteReview =>
      (isDelivered || isCustomerConfirmed) && !userHasReviewed;

  static int parseQty(Map<String, dynamic> map) {
    final raw = map['qty'] ?? map['quantity'] ?? map['product_qty'];
    if (raw == null) return 0;
    return int.tryParse(raw.toString()) ?? 0;
  }

  OrderedProductModel copyWith({
    int? id,
    int? orderId,
    int? productId,
    int? sellerId,
    String? productName,
    double? unitPrice,
    double? vat,
    int? qty,
    bool? userHasReviewed,
    String? deliveredAt,
    String? customerConfirmedAt,
    String? autoConfirmedAt,
    OrderProductCargoModel? cargo,
    String? thumbImage,
    String? createdAt,
    String? updatedAt,
  }) {
    return OrderedProductModel(
      id: id ?? this.id,
      orderId: orderId ?? this.orderId,
      productId: productId ?? this.productId,
      sellerId: sellerId ?? this.sellerId,
      productName: productName ?? this.productName,
      unitPrice: unitPrice ?? this.unitPrice,
      vat: vat ?? this.vat,
      qty: qty ?? this.qty,
      userHasReviewed: userHasReviewed ?? this.userHasReviewed,
      deliveredAt: deliveredAt ?? this.deliveredAt,
      customerConfirmedAt: customerConfirmedAt ?? this.customerConfirmedAt,
      autoConfirmedAt: autoConfirmedAt ?? this.autoConfirmedAt,
      cargo: cargo ?? this.cargo,
      thumbImage: thumbImage ?? this.thumbImage,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  Map<String, dynamic> toMap() {
    final result = <String, dynamic>{};

    result.addAll({'id': id});
    result.addAll({'order_id': orderId});
    result.addAll({'product_id': productId});
    result.addAll({'seller_id': sellerId});
    result.addAll({'product_name': productName});
    result.addAll({'unit_price': unitPrice});
    result.addAll({'vat': vat});
    result.addAll({'qty': qty});
    result.addAll({'user_has_reviewed': userHasReviewed});
    result.addAll({'delivered_at': deliveredAt});
    result.addAll({'customer_confirmed_at': customerConfirmedAt});
    result.addAll({'auto_confirmed_at': autoConfirmedAt});
    if (cargo != null) result.addAll({'cargo': cargo});
    result.addAll({'thumb_image': thumbImage});
    result.addAll({'created_at': createdAt});
    result.addAll({'updated_at': updatedAt});

    return result;
  }

  factory OrderedProductModel.fromMap(Map<String, dynamic> map) {
    final cargoRaw = map['cargo'];
    return OrderedProductModel(
      id: map['id']?.toInt() ?? 0,
      orderId:
          map['order_id'] != null ? int.parse(map['order_id'].toString()) : 0,
      productId: map['product_id'] != null
          ? int.parse(map['product_id'].toString())
          : 0,
      sellerId:
          map['seller_id'] != null ? int.parse(map['seller_id'].toString()) : 0,
      productName: map['product_name'] ?? '',
      unitPrice: map['unit_price'] != null
          ? double.parse(map['unit_price'].toString())
          : 0,
      vat: map['vat'] != null ? double.parse(map['vat'].toString()) : 0,
      qty: parseQty(map),
      userHasReviewed: map['user_has_reviewed'] == true ||
          map['user_has_reviewed'] == 1 ||
          map['user_has_reviewed']?.toString() == '1',
      deliveredAt: map['delivered_at']?.toString(),
      customerConfirmedAt: map['customer_confirmed_at']?.toString(),
      autoConfirmedAt: map['auto_confirmed_at']?.toString(),
      cargo: cargoRaw is Map<String, dynamic>
          ? OrderProductCargoModel.fromMap(cargoRaw)
          : null,
      thumbImage: map['thumb_image']?.toString() ?? '',
      createdAt: map['created_at'] ?? '',
      updatedAt: map['updated_at'] ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory OrderedProductModel.fromJson(String source) =>
      OrderedProductModel.fromMap(json.decode(source));

  @override
  String toString() {
    return 'OrderedProductModel(id: $id, order_id: $orderId, product_id: $productId, seller_id: $sellerId, product_name: $productName, unit_price: $unitPrice, vat: $vat, qty: $qty, user_has_reviewed: $userHasReviewed, delivered_at: $deliveredAt, customer_confirmed_at: $customerConfirmedAt, auto_confirmed_at: $autoConfirmedAt, created_at: $createdAt, updated_at: $updatedAt)';
  }

  @override
  List<Object?> get props => [
        id,
        orderId,
        productId,
        sellerId,
        productName,
        unitPrice,
        vat,
        qty,
        userHasReviewed,
        deliveredAt,
        customerConfirmedAt,
        autoConfirmedAt,
        cargo,
        thumbImage,
        createdAt,
        updatedAt,
      ];
}
