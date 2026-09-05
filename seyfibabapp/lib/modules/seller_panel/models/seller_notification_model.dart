class SellerNotificationsPage {
  SellerNotificationsPage({
    required this.items,
    required this.unreadCount,
  });

  final List<SellerNotificationItem> items;
  final int unreadCount;

  factory SellerNotificationsPage.fromMap(Map<String, dynamic> map) {
    final notifications = map['notifications'];
    final data = notifications is Map ? notifications['data'] : notifications;
    return SellerNotificationsPage(
      items: data is List
          ? data
              .whereType<Map>()
              .map(
                (e) =>
                    SellerNotificationItem.fromMap(Map<String, dynamic>.from(e)),
              )
              .toList()
          : const [],
      unreadCount: int.tryParse('${map['unread_count'] ?? 0}') ?? 0,
    );
  }
}

class SellerNotificationItem {
  SellerNotificationItem({
    required this.id,
    required this.title,
    required this.body,
    required this.createdAt,
    required this.readAt,
    this.type = '',
    this.productId,
    this.status = '',
  });

  final String id;
  final String title;
  final String body;
  final String createdAt;
  final String? readAt;
  final String type;
  final int? productId;
  final String status;

  bool get isUnread => readAt == null || readAt!.isEmpty;

  factory SellerNotificationItem.fromMap(Map<String, dynamic> map) {
    final data = map['data'];
    final payload =
        data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{};
    final productIdRaw = payload['product_id'];
    final type = '${payload['type'] ?? ''}';
    final message =
        '${payload['message'] ?? payload['body'] ?? payload['text'] ?? ''}';
    final titleFromPayload =
        '${payload['title'] ?? payload['subject'] ?? ''}'.trim();
    final title = titleFromPayload.isNotEmpty
        ? titleFromPayload
        : switch (type) {
            'seller_new_order' => 'Yeni sipariş',
            'stock_alert' => 'Stok uyarısı',
            'kyc_status' => 'KYC durumu',
            'kyc_reminder' => 'KYC hatırlatma',
            'seller_withdraw_approved' => 'Para transferi',
            _ => 'Bildirim',
          };
    final initialQty = int.tryParse('${payload['initial_qty'] ?? ''}');
    final currentStock = int.tryParse('${payload['current_stock'] ?? ''}');
    var body = message;
    if (type == 'stock_alert' &&
        initialQty != null &&
        currentStock != null &&
        initialQty > 0) {
      body =
          '$message (Başlangıç: $initialQty, Kalan: $currentStock)';
    }
    return SellerNotificationItem(
      id: '${map['id'] ?? ''}',
      title: title,
      body: body,
      createdAt: '${map['created_at'] ?? ''}',
      readAt: map['read_at']?.toString(),
      type: type,
      productId: productIdRaw == null
          ? null
          : int.tryParse('$productIdRaw'),
      status: '${payload['status'] ?? ''}',
    );
  }
}
