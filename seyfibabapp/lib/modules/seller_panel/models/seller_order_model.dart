class PaginatedSellerOrders {
  PaginatedSellerOrders({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.title,
  });

  final List<SellerOrderModel> items;
  final int currentPage;
  final int lastPage;
  final String title;

  bool get hasMore => currentPage < lastPage;

  factory PaginatedSellerOrders.fromResponse(Map<String, dynamic> json) {
    final orders = json['orders'];
    if (orders is Map) {
      final data = orders['data'];
      final list = data is List
          ? data
              .whereType<Map>()
              .map((e) => SellerOrderModel.fromMap(Map<String, dynamic>.from(e)))
              .toList()
          : <SellerOrderModel>[];
      return PaginatedSellerOrders(
        items: list,
        currentPage: int.tryParse('${orders['current_page'] ?? 1}') ?? 1,
        lastPage: int.tryParse('${orders['last_page'] ?? 1}') ?? 1,
        title: '${json['title'] ?? 'Siparişler'}',
      );
    }
    if (orders is List) {
      return PaginatedSellerOrders(
        items: orders
            .whereType<Map>()
            .map((e) => SellerOrderModel.fromMap(Map<String, dynamic>.from(e)))
            .toList(),
        currentPage: 1,
        lastPage: 1,
        title: '${json['title'] ?? 'Siparişler'}',
      );
    }
    return PaginatedSellerOrders(
      items: const [],
      currentPage: 1,
      lastPage: 1,
      title: '${json['title'] ?? 'Siparişler'}',
    );
  }
}

class SellerOrderModel {
  SellerOrderModel({
    required this.id,
    required this.orderId,
    required this.totalAmount,
    required this.orderStatus,
    required this.paymentStatus,
    required this.createdAt,
    required this.customerName,
  });

  final int id;
  final String orderId;
  final double totalAmount;
  final int orderStatus;
  final int paymentStatus;
  final String createdAt;
  final String customerName;

  factory SellerOrderModel.fromMap(Map<String, dynamic> map) {
    final user = map['user'];
    String customerName = '';
    if (user is Map) {
      customerName = '${user['name'] ?? ''}'.trim();
    }
    return SellerOrderModel(
      id: int.tryParse('${map['id']}') ?? 0,
      orderId: '${map['order_id'] ?? map['id'] ?? ''}',
      totalAmount: double.tryParse('${map['total_amount'] ?? map['amount'] ?? 0}') ?? 0,
      orderStatus: int.tryParse('${map['order_status'] ?? 0}') ?? 0,
      paymentStatus: int.tryParse('${map['payment_status'] ?? 0}') ?? 0,
      createdAt: '${map['created_at'] ?? ''}',
      customerName: customerName,
    );
  }
}

class SellerOrderDetail {
  SellerOrderDetail({
    required this.order,
    required this.raw,
  });

  final SellerOrderModel order;
  final Map<String, dynamic> raw;

  factory SellerOrderDetail.fromMap(Map<String, dynamic> map) {
    final orderMap = map['order'] is Map
        ? Map<String, dynamic>.from(map['order'] as Map)
        : map;
    return SellerOrderDetail(
      order: SellerOrderModel.fromMap(orderMap),
      raw: map,
    );
  }
}
