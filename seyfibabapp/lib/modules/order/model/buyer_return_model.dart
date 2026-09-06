class BuyerReturnableItem {
  const BuyerReturnableItem({
    required this.orderProductId,
    required this.isReturnable,
    required this.maxReturnableQty,
    required this.unitPrice,
    required this.paidUnitPrice,
    required this.suggestedRefund,
    this.message,
    this.existingReturnRequestId,
  });

  final int orderProductId;
  final bool isReturnable;
  final int maxReturnableQty;
  final double unitPrice;
  final double paidUnitPrice;
  final double suggestedRefund;
  final String? message;
  final int? existingReturnRequestId;

  factory BuyerReturnableItem.fromMap(Map<String, dynamic> map) {
    return BuyerReturnableItem(
      orderProductId: int.tryParse('${map['order_product_id']}') ?? 0,
      isReturnable: map['is_returnable'] == true ||
          map['is_returnable'] == 1 ||
          '${map['is_returnable']}' == '1',
      maxReturnableQty: int.tryParse('${map['max_returnable_qty'] ?? 0}') ?? 0,
      unitPrice: double.tryParse('${map['unit_price'] ?? 0}') ?? 0,
      paidUnitPrice: double.tryParse('${map['paid_unit_price'] ?? 0}') ?? 0,
      suggestedRefund: double.tryParse('${map['suggested_refund'] ?? 0}') ?? 0,
      message: map['message']?.toString(),
      existingReturnRequestId: map['existing_return_request_id'] == null
          ? null
          : int.tryParse('${map['existing_return_request_id']}'),
    );
  }
}

class BuyerReturnRequest {
  BuyerReturnRequest({
    required this.id,
    required this.status,
    required this.reason,
    required this.details,
    required this.qty,
    required this.refundAmount,
    required this.createdAt,
    required this.orderCode,
    required this.productName,
  });

  final int id;
  final int status;
  final String reason;
  final String details;
  final int qty;
  final double refundAmount;
  final String createdAt;
  final String orderCode;
  final String productName;

  bool get isPending => status == 0;

  String get statusLabel => switch (status) {
        0 => 'Bekliyor',
        1 => 'Satıcı onayladı',
        2 => 'Admin onayladı',
        3 => 'Ürün alındı',
        4 => 'İade edildi',
        5 => 'Satıcı reddetti',
        6 => 'Admin reddetti',
        7 => 'İptal edildi',
        _ => 'Durum $status',
      };

  factory BuyerReturnRequest.fromMap(Map<String, dynamic> map) {
    final order = map['order'];
    final orderProduct = map['order_product'] ?? map['orderProduct'];
    String orderCode = '${map['order_id'] ?? ''}';
    String productName = '';

    if (order is Map && '${order['order_id'] ?? ''}'.isNotEmpty) {
      orderCode = '${order['order_id']}';
    }
    if (orderProduct is Map) {
      productName = '${orderProduct['product_name'] ?? ''}'.trim();
      final product = orderProduct['product'];
      if (productName.isEmpty && product is Map) {
        productName = '${product['name'] ?? ''}'.trim();
      }
    }

    return BuyerReturnRequest(
      id: int.tryParse('${map['id']}') ?? 0,
      status: int.tryParse('${map['status'] ?? 0}') ?? 0,
      reason: '${map['reason'] ?? ''}',
      details: '${map['details'] ?? map['description'] ?? ''}',
      qty: int.tryParse('${map['qty'] ?? 1}') ?? 1,
      refundAmount: double.tryParse('${map['refund_amount'] ?? 0}') ?? 0,
      createdAt: '${map['created_at'] ?? ''}',
      orderCode: orderCode,
      productName: productName,
    );
  }
}
