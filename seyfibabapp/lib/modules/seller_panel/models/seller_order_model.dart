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
    required this.sellerStatus,
    required this.payoutState,
    required this.payoutLabel,
  });

  final int id;
  final String orderId;
  final double totalAmount;
  final int orderStatus;
  final int paymentStatus;
  final String createdAt;
  final String customerName;
  final int sellerStatus;
  final String payoutState;
  final String payoutLabel;

  factory SellerOrderModel.fromMap(Map<String, dynamic> map) {
    final user = map['user'];
    String customerName = '';
    if (user is Map) {
      customerName = '${user['name'] ?? ''}'.trim();
    }

    final products = map['order_products'] ?? map['orderProducts'];
    final flowOrder = Map<String, dynamic>.from(map);
    if (products is List && products.isNotEmpty) {
      flowOrder['order_products'] = products;
    }

    // Lazy import avoided: duplicate minimal payout parse for list cards
    final sellerStatus = _sellerStatusFromProducts(products);
    final payout = _payoutFromOrder(flowOrder, sellerStatus);
    final sellerSubtotal = _sellerSubtotalFromProducts(products);
    final apiSubtotal = double.tryParse('${map['seller_lines_subtotal'] ?? ''}');

    return SellerOrderModel(
      id: int.tryParse('${map['id']}') ?? 0,
      orderId: '${map['order_id'] ?? map['id'] ?? ''}',
      totalAmount: apiSubtotal ??
          (sellerSubtotal > 0
              ? sellerSubtotal
              : double.tryParse('${map['total_amount'] ?? map['amount'] ?? 0}') ?? 0),
      orderStatus: int.tryParse('${map['order_status'] ?? 0}') ?? 0,
      paymentStatus: int.tryParse('${map['payment_status'] ?? 0}') ?? 0,
      createdAt: '${map['created_at'] ?? ''}',
      customerName: customerName,
      sellerStatus: sellerStatus,
      payoutState: payout.$1,
      payoutLabel: payout.$2,
    );
  }

  static int _sellerStatusFromProducts(dynamic products) {
    if (products is! List || products.isEmpty) return 0;
    if (products.any((p) => p is Map && int.tryParse('${p['seller_status']}') == 4)) {
      return 4;
    }
    var min = 99;
    for (final item in products) {
      if (item is! Map) continue;
      final s = int.tryParse('${item['seller_status'] ?? 0}') ?? 0;
      if (s < min) min = s;
    }
    return min == 99 ? 0 : min;
  }

  static double _sellerSubtotalFromProducts(dynamic products) {
    if (products is! List) return 0;
    var sum = 0.0;
    for (final item in products) {
      if (item is! Map) continue;
      final qty = int.tryParse('${item['qty'] ?? 1}') ?? 1;
      final unit =
          double.tryParse('${item['unit_price'] ?? item['price'] ?? 0}') ?? 0;
      var line = unit * qty;
      final variants =
          item['order_product_variants'] ?? item['orderProductVariants'];
      if (variants is List) {
        for (final variant in variants) {
          if (variant is! Map) continue;
          line += (double.tryParse('${variant['variant_price'] ?? 0}') ?? 0) *
              qty;
        }
      }
      sum += line;
    }
    return sum;
  }

  static (String, String) _payoutFromOrder(Map<String, dynamic> order, int sellerStatus) {
    if (sellerStatus < 3) return ('waiting', 'Bekliyor');
    final payoutStatus = '${order['payout_status'] ?? 'pending'}';
    final paid = '${order['payout_processed_at'] ?? ''}'.trim().isNotEmpty ||
        payoutStatus == 'completed' ||
        payoutStatus == 'paid';
    if (paid) return ('paid', 'Ödendi');
    if ('${order['payout_blocked_at'] ?? ''}'.trim().isNotEmpty) {
      return ('blocked', 'Bekletiliyor');
    }
    return ('pending', 'Beklemede');
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
