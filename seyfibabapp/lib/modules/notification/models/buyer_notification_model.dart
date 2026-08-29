class BuyerNotificationsPage {
  BuyerNotificationsPage({
    required this.items,
    required this.unreadCount,
    this.totalCount = 0,
  });

  final List<BuyerNotificationItem> items;
  final int unreadCount;
  final int totalCount;

  factory BuyerNotificationsPage.fromMap(Map<String, dynamic> map) {
    final notifications = map['notifications'];
    final data = notifications is Map ? notifications['data'] : notifications;
    final items = data is List
        ? data
            .whereType<Map>()
            .map(
              (e) => BuyerNotificationItem.fromMap(
                Map<String, dynamic>.from(e),
              ),
            )
            .toList()
        : const <BuyerNotificationItem>[];
    final total = notifications is Map
        ? int.tryParse('${notifications['total'] ?? items.length}') ??
            items.length
        : items.length;
    return BuyerNotificationsPage(
      items: items,
      unreadCount: int.tryParse('${map['unread_count'] ?? 0}') ?? 0,
      totalCount: total,
    );
  }
}

class BuyerNotificationItem {
  BuyerNotificationItem({
    required this.id,
    required this.title,
    required this.body,
    required this.createdAt,
    required this.readAt,
    this.type = '',
    this.productId,
    this.productSlug = '',
    this.campaignSlug = '',
    this.couponCode = '',
    this.orderNumber = '',
  });

  final String id;
  final String title;
  final String body;
  final String createdAt;
  final String? readAt;
  final String type;
  final int? productId;
  final String productSlug;
  final String campaignSlug;
  final String couponCode;
  final String orderNumber;

  bool get isUnread => readAt == null || readAt!.isEmpty;

  factory BuyerNotificationItem.fromMap(Map<String, dynamic> map) {
    final data = map['data'];
    final payload =
        data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{};
    final type = '${payload['type'] ?? ''}';
    final message =
        '${payload['message'] ?? payload['body'] ?? payload['text'] ?? ''}';
    final titleFromPayload =
        '${payload['title'] ?? payload['subject'] ?? ''}'.trim();
    final title = titleFromPayload.isNotEmpty
        ? titleFromPayload
        : switch (type) {
            'order' => 'Sipariş bildirimi',
            'campaign' => 'Kampanya',
            'discount' => 'Indirim kuponu',
            'product_view_reminder' => 'Ilginizi ceken urun',
            'admin_broadcast' => 'Duyuru',
            _ => 'Bildirim',
          };
    return BuyerNotificationItem(
      id: '${map['id'] ?? ''}',
      title: title,
      body: message,
      createdAt: '${map['created_at'] ?? ''}',
      readAt: map['read_at']?.toString(),
      type: type,
      productId: int.tryParse('${payload['product_id'] ?? ''}'),
      productSlug: '${payload['product_slug'] ?? ''}',
      campaignSlug: '${payload['campaign_slug'] ?? ''}',
      couponCode: '${payload['coupon_code'] ?? ''}',
      orderNumber: '${payload['order_number'] ?? ''}',
    );
  }
}
