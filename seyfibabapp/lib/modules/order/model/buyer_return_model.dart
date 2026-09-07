class BuyerReturnableItem {
  const BuyerReturnableItem({
    required this.orderProductId,
    required this.isReturnable,
    required this.maxReturnableQty,
    required this.unitPrice,
    required this.paidUnitPrice,
    required this.suggestedRefund,
    this.couponShare = 0,
    this.bankDiscountShare = 0,
    this.isBankPayment = false,
    this.message,
    this.existingReturnRequestId,
    this.returnStatus,
    this.returnStatusLabel,
    this.isRejected = false,
    this.rejectionNote,
    this.adminNote,
    this.rejectedReason,
    this.returnAddress,
    this.returnShippingPayerLabel,
    this.returnCarrierName,
    this.returnCargoCode,
    this.returnShippingInstructions,
    this.buyerReturnTrackingNumber,
    this.canSubmitTracking = false,
    this.refundInfo,
  });

  final int orderProductId;
  final bool isReturnable;
  final int maxReturnableQty;
  final double unitPrice;
  final double paidUnitPrice;
  final double suggestedRefund;
  final double couponShare;
  final double bankDiscountShare;
  final bool isBankPayment;
  final String? message;
  final int? existingReturnRequestId;
  final int? returnStatus;
  final String? returnStatusLabel;
  final bool isRejected;
  final String? rejectionNote;
  final String? adminNote;
  final String? rejectedReason;
  final String? returnAddress;
  final String? returnShippingPayerLabel;
  final String? returnCarrierName;
  final String? returnCargoCode;
  final String? returnShippingInstructions;
  final String? buyerReturnTrackingNumber;
  final bool canSubmitTracking;
  final String? refundInfo;

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
      couponShare: double.tryParse('${map['coupon_share'] ?? 0}') ?? 0,
      bankDiscountShare:
          double.tryParse('${map['bank_discount_share'] ?? 0}') ?? 0,
      isBankPayment: map['is_bank_payment'] == true ||
          map['is_bank_payment'] == 1 ||
          '${map['is_bank_payment']}' == '1',
      message: map['message']?.toString(),
      existingReturnRequestId: map['existing_return_request_id'] == null
          ? null
          : int.tryParse('${map['existing_return_request_id']}'),
      returnStatus: map['return_status'] == null
          ? null
          : int.tryParse('${map['return_status']}'),
      returnStatusLabel: map['return_status_label']?.toString(),
      isRejected: map['is_rejected'] == true ||
          map['is_rejected'] == 1 ||
          '${map['is_rejected']}' == '1',
      rejectionNote: map['rejection_note']?.toString(),
      adminNote: map['admin_note']?.toString(),
      rejectedReason: map['rejected_reason']?.toString(),
      returnAddress: map['return_address']?.toString(),
      returnShippingPayerLabel: map['return_shipping_payer_label']?.toString(),
      returnCarrierName: map['return_carrier_name']?.toString(),
      returnCargoCode: map['return_cargo_code']?.toString(),
      returnShippingInstructions:
          map['return_shipping_instructions']?.toString(),
      buyerReturnTrackingNumber:
          map['buyer_return_tracking_number']?.toString(),
      canSubmitTracking: map['can_submit_tracking'] == true ||
          map['can_submit_tracking'] == 1 ||
          '${map['can_submit_tracking']}' == '1',
      refundInfo: map['refund_info']?.toString(),
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
    this.adminNote,
    this.rejectedReason,
    this.sellerNote,
    this.adminResponse,
    this.vendorResponse,
    this.returnAddress,
    this.returnShippingPayer,
    this.returnCarrierName,
    this.returnCargoCode,
    this.returnShippingInstructions,
    this.buyerReturnCarrier,
    this.buyerReturnTrackingNumber,
    this.buyerReturnTrackingUrl,
    this.refundInfo,
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
  final String? adminNote;
  final String? rejectedReason;
  final String? sellerNote;
  final String? adminResponse;
  final String? vendorResponse;
  final String? returnAddress;
  final String? returnShippingPayer;
  final String? returnCarrierName;
  final String? returnCargoCode;
  final String? returnShippingInstructions;
  final String? buyerReturnCarrier;
  final String? buyerReturnTrackingNumber;
  final String? buyerReturnTrackingUrl;
  final String? refundInfo;

  bool get isPending => status == 0;

  bool get isRejected => status == 5 || status == 6;

  bool get canSubmitTracking => false;

  String get shippingPayerLabel => switch (returnShippingPayer) {
        'seller' => 'Satıcı karşılar',
        'buyer' => 'Alıcı karşılar',
        'platform' => 'Platform karşılar',
        _ => 'Henüz belirlenmedi',
      };

  String get statusLabel {
    return switch (status) {
      0 => 'İade talebi alındı — satıcı/yönetici inceliyor',
      1 => 'Satıcı onayladı — ürünü iade adresine kargolayın',
      2 => 'İade onaylandı — kargo talimatını uygulayın',
      3 => 'İade ürünü satıcıya / depoya ulaştı',
      4 => 'İade tamamlandı — para iadesi yapıldı',
      5 => 'İade talebi reddedildi',
      6 => 'İade talebi reddedildi',
      7 => 'İade talebi iptal edildi',
      _ => 'Durum $status',
    };
  }

  String? get rejectionNote {
    for (final candidate in [
      adminNote,
      adminResponse,
      rejectedReason,
      sellerNote,
      vendorResponse,
    ]) {
      final text = (candidate ?? '').trim();
      if (text.isNotEmpty && text != 'Cancelled by customer') {
        return text;
      }
    }
    return null;
  }

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
      adminNote: map['admin_note']?.toString(),
      rejectedReason: map['rejected_reason']?.toString(),
      sellerNote: map['seller_note']?.toString(),
      adminResponse: map['admin_response']?.toString(),
      vendorResponse: map['vendor_response']?.toString(),
      returnAddress: map['return_address']?.toString(),
      returnShippingPayer: map['return_shipping_payer']?.toString(),
      returnCarrierName: map['return_carrier_name']?.toString(),
      returnCargoCode: map['return_cargo_code']?.toString(),
      returnShippingInstructions:
          map['return_shipping_instructions']?.toString(),
      buyerReturnCarrier: map['buyer_return_carrier']?.toString(),
      buyerReturnTrackingNumber:
          map['buyer_return_tracking_number']?.toString(),
      buyerReturnTrackingUrl: map['buyer_return_tracking_url']?.toString(),
      refundInfo: map['refund_info']?.toString(),
    );
  }
}
