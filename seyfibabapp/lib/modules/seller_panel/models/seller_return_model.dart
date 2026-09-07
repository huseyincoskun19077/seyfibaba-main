class SellerReturnRequest {
  SellerReturnRequest({
    required this.id,
    required this.status,
    required this.reason,
    required this.reasonLabel,
    required this.details,
    required this.qty,
    required this.refundAmount,
    required this.refundMethodLabel,
    required this.unitPrice,
    required this.sellerImpactNet,
    required this.commissionRate,
    required this.sellerNote,
    required this.adminNote,
    required this.rejectedReason,
    required this.createdAt,
    required this.customerName,
    required this.customerEmail,
    required this.customerPhone,
    required this.orderCode,
    required this.productName,
    required this.returnAddress,
    required this.returnShippingPayer,
    required this.returnShippingPayerLabel,
    required this.returnCarrierName,
    required this.returnCargoCode,
    required this.returnShippingInstructions,
    required this.defaultReturnAddress,
    required this.defaultShippingPayer,
    required this.imageUrls,
    this.canMarkReceivedFlag,
  });

  final int id;
  final int status;
  final String reason;
  final String reasonLabel;
  final String details;
  final int qty;
  final double refundAmount;
  final String refundMethodLabel;
  final double unitPrice;
  final double sellerImpactNet;
  final double commissionRate;
  final String sellerNote;
  final String adminNote;
  final String rejectedReason;
  final String createdAt;
  final String customerName;
  final String customerEmail;
  final String customerPhone;
  final String orderCode;
  final String productName;
  final String returnAddress;
  final String returnShippingPayer;
  final String returnShippingPayerLabel;
  final String returnCarrierName;
  final String returnCargoCode;
  final String returnShippingInstructions;
  final String defaultReturnAddress;
  final String defaultShippingPayer;
  final List<String> imageUrls;
  final bool? canMarkReceivedFlag;

  bool get isPending => status == 0;

  bool get canMarkReceived =>
      canMarkReceivedFlag ?? (status == 1 || status == 2);

  String get statusLabel => switch (status) {
        0 => 'Beklemede',
        1 => 'Satıcı onayladı — ürün bekleniyor',
        2 => 'Yönetici onayladı — ürün bekleniyor',
        3 => 'Ürün alındı — admin ödeme yapacak',
        4 => 'İade tamamlandı',
        5 => 'Satıcı reddetti',
        6 => 'Yönetici reddetti',
        7 => 'Müşteri iptal etti',
        _ => 'Durum $status',
      };

  String get processHint {
    switch (status) {
      case 0:
        return 'Bu talep sizin kararınızı bekliyor. Kanıtları inceleyin; onaylayın veya net bir gerekçe yazarak reddedin.';
      case 1:
        return 'Talebi onayladınız. Müşteri ürünü iade adresinize kargolayacak. Ürün elinize geçince “Ürünü aldım” deyin; ardından yönetici para iadesini tamamlar.';
      case 2:
        return 'Yönetici de onayladı. Müşteri ürünü size kargolayacak. Ürün elinize geçince “Ürünü aldım” deyin.';
      case 3:
        return 'Ürünü teslim aldığınız kaydedildi. Yönetici para iadesini tamamlayacak.';
      case 4:
        return 'İade tamamlandı. Müşteriye para iadesi işlenmiş kabul edilir.';
      case 5:
        return 'Talebi reddettiniz. Müşteri red gerekçesini görür.';
      case 6:
        return 'Yönetici talebi reddetti. Satıcı panelinden ek işlem yok.';
      default:
        return 'Bu talep kapanmış veya iptal edilmiş. Satıcı panelinden işlem yapılamaz.';
    }
  }

  factory SellerReturnRequest.fromMap(Map<String, dynamic> map) {
    final user = map['user'];
    final order = map['order'];
    final orderProduct = map['order_product'] ?? map['orderProduct'];
    String customerName = '';
    String customerEmail = '';
    String customerPhone = '';
    String orderCode = '${map['order_id'] ?? ''}';
    String productName = '';
    double unitPrice = 0;

    if (user is Map) {
      customerName = '${user['name'] ?? ''}'.trim();
      customerEmail = '${user['email'] ?? ''}'.trim();
      customerPhone = '${user['phone'] ?? ''}'.trim();
    }
    if (order is Map && '${order['order_id'] ?? ''}'.isNotEmpty) {
      orderCode = '${order['order_id']}';
    }
    if (orderProduct is Map) {
      productName = '${orderProduct['product_name'] ?? ''}'.trim();
      unitPrice = double.tryParse('${orderProduct['unit_price'] ?? 0}') ?? 0;
      final product = orderProduct['product'];
      if (productName.isEmpty && product is Map) {
        productName = '${product['name'] ?? ''}'.trim();
      }
    }

    final images = <String>[];
    final rawImages = map['images'];
    if (rawImages is List) {
      for (final raw in rawImages) {
        if (raw is! Map) continue;
        final url = '${raw['url'] ?? raw['image'] ?? ''}'.trim();
        if (url.isNotEmpty) images.add(url);
      }
    }

    final reason = '${map['reason'] ?? ''}';
    final reasonLabel = '${map['reason_label'] ?? ''}'.trim().isNotEmpty
        ? '${map['reason_label']}'
        : _fallbackReasonLabel(reason);

    final sellerImpactNet = double.tryParse(
          '${map['seller_impact_net'] ?? map['refund_amount'] ?? 0}',
        ) ??
        0;
    final commissionRate =
        double.tryParse('${map['seller_commission_rate'] ?? 0}') ?? 0;

    return SellerReturnRequest(
      id: int.tryParse('${map['id']}') ?? 0,
      status: int.tryParse('${map['status'] ?? 0}') ?? 0,
      reason: reason,
      reasonLabel: reasonLabel,
      details: '${map['details'] ?? map['description'] ?? ''}',
      qty: int.tryParse('${map['qty'] ?? 1}') ?? 1,
      refundAmount: sellerImpactNet,
      refundMethodLabel:
          '${map['refund_method_label'] ?? map['refund_method'] ?? 'Henüz belirlenmedi'}',
      unitPrice: unitPrice,
      sellerImpactNet: sellerImpactNet,
      commissionRate: commissionRate,
      sellerNote: '${map['seller_note'] ?? map['vendor_response'] ?? ''}',
      adminNote: '${map['admin_note'] ?? map['admin_response'] ?? ''}',
      rejectedReason: '${map['rejected_reason'] ?? ''}',
      createdAt: '${map['created_at'] ?? ''}',
      customerName: customerName,
      customerEmail: customerEmail,
      customerPhone: customerPhone,
      orderCode: orderCode,
      productName: productName,
      returnAddress: '${map['return_address'] ?? ''}',
      returnShippingPayer: '${map['return_shipping_payer'] ?? ''}',
      returnShippingPayerLabel:
          '${map['return_shipping_payer_label'] ?? ''}'.trim().isNotEmpty
              ? '${map['return_shipping_payer_label']}'
              : 'Henüz belirlenmedi',
      returnCarrierName: '${map['return_carrier_name'] ?? ''}',
      returnCargoCode: '${map['return_cargo_code'] ?? ''}',
      returnShippingInstructions:
          '${map['return_shipping_instructions'] ?? ''}',
      defaultReturnAddress: '${map['default_return_address'] ?? map['return_address'] ?? ''}',
      defaultShippingPayer:
          '${map['default_shipping_payer'] ?? map['return_shipping_payer'] ?? 'buyer'}',
      imageUrls: images,
      canMarkReceivedFlag: map.containsKey('can_mark_received')
          ? (map['can_mark_received'] == true ||
              map['can_mark_received'] == 1 ||
              '${map['can_mark_received']}' == '1')
          : null,
    );
  }

  static String _fallbackReasonLabel(String reason) {
    return switch (reason) {
      'defective' => 'Arızalı ürün',
      'wrong_item' => 'Yanlış ürün geldi',
      'not_as_described' => 'Açıklamadaki gibi değil',
      'changed_mind' => 'Karar değişikliği',
      'damaged_in_shipping' => 'Kargoda hasar gördü',
      'other' => 'Diğer',
      _ => reason.isEmpty ? '-' : reason.replaceAll('_', ' '),
    };
  }
}
