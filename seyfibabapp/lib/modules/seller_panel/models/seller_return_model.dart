class SellerReturnRequest {
  SellerReturnRequest({
    required this.id,
    required this.status,
    required this.reason,
    required this.details,
    required this.qty,
    required this.refundAmount,
    required this.sellerNote,
    required this.rejectedReason,
    required this.createdAt,
    required this.customerName,
    required this.orderCode,
    required this.productName,
  });

  final int id;
  final int status;
  final String reason;
  final String details;
  final int qty;
  final double refundAmount;
  final String sellerNote;
  final String rejectedReason;
  final String createdAt;
  final String customerName;
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
        7 => 'Kullanıcı iptal',
        _ => 'Durum $status',
      };

  factory SellerReturnRequest.fromMap(Map<String, dynamic> map) {
    final user = map['user'];
    final order = map['order'];
    final orderProduct = map['order_product'] ?? map['orderProduct'];
    String customerName = '';
    String orderCode = '${map['order_id'] ?? ''}';
    String productName = '';

    if (user is Map) customerName = '${user['name'] ?? ''}'.trim();
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

    return SellerReturnRequest(
      id: int.tryParse('${map['id']}') ?? 0,
      status: int.tryParse('${map['status'] ?? 0}') ?? 0,
      reason: '${map['reason'] ?? ''}',
      details: '${map['details'] ?? map['description'] ?? ''}',
      qty: int.tryParse('${map['qty'] ?? 1}') ?? 1,
      refundAmount: double.tryParse('${map['refund_amount'] ?? 0}') ?? 0,
      sellerNote: '${map['seller_note'] ?? map['vendor_response'] ?? ''}',
      rejectedReason: '${map['rejected_reason'] ?? ''}',
      createdAt: '${map['created_at'] ?? ''}',
      customerName: customerName,
      orderCode: orderCode,
      productName: productName,
    );
  }
}
