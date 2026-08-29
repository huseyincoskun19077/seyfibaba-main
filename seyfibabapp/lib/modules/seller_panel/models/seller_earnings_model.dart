class SellerEarningsSummary {
  SellerEarningsSummary({
    required this.totalGross,
    required this.totalCommission,
    required this.totalNet,
    required this.commissionRate,
    required this.withdrawableBalance,
    required this.totalWithdrawn,
    required this.pendingWithdrawals,
  });

  final double totalGross;
  final double totalCommission;
  final double totalNet;
  final double commissionRate;
  final double withdrawableBalance;
  final double totalWithdrawn;
  final double pendingWithdrawals;

  factory SellerEarningsSummary.fromMap(Map<String, dynamic> map) {
    double d(dynamic v) => double.tryParse('$v') ?? 0;
    return SellerEarningsSummary(
      totalGross: d(map['total_gross']),
      totalCommission: d(map['total_commission']),
      totalNet: d(map['total_net']),
      commissionRate: d(map['commission_rate']),
      withdrawableBalance: d(map['withdrawable_balance']),
      totalWithdrawn: d(map['total_withdrawn']),
      pendingWithdrawals: d(map['pending_withdrawals']),
    );
  }
}

class SellerEarningOrderItem {
  SellerEarningOrderItem({
    required this.id,
    required this.orderId,
    required this.productName,
    required this.qty,
    required this.grossAmount,
    required this.commissionAmount,
    required this.sellerNetAmount,
    required this.createdAt,
  });

  final int id;
  final String orderId;
  final String productName;
  final int qty;
  final double grossAmount;
  final double commissionAmount;
  final double sellerNetAmount;
  final String createdAt;

  factory SellerEarningOrderItem.fromMap(Map<String, dynamic> map) {
    final order = map['order'];
    String createdAt = '';
    String orderCode = '${map['order_id'] ?? ''}';
    if (order is Map) {
      createdAt = '${order['created_at'] ?? ''}';
      if ('${order['order_id'] ?? ''}'.isNotEmpty) {
        orderCode = '${order['order_id']}';
      }
    }
    return SellerEarningOrderItem(
      id: int.tryParse('${map['id']}') ?? 0,
      orderId: orderCode,
      productName: '${map['product_name'] ?? ''}',
      qty: int.tryParse('${map['qty'] ?? 0}') ?? 0,
      grossAmount: double.tryParse('${map['gross_amount'] ?? 0}') ?? 0,
      commissionAmount:
          double.tryParse('${map['commission_amount'] ?? 0}') ?? 0,
      sellerNetAmount:
          double.tryParse('${map['seller_net_amount'] ?? 0}') ?? 0,
      createdAt: createdAt,
    );
  }
}

class SellerWithdrawBundle {
  SellerWithdrawBundle({
    required this.withdraws,
    required this.balanceHint,
  });

  final List<SellerWithdrawItem> withdraws;
  final double balanceHint;

  factory SellerWithdrawBundle.fromMap(Map<String, dynamic> map) {
    final list = map['withdraws'];
    final earnings = map['earnings'];
    double balance = 0;
    if (earnings is Map) {
      balance = double.tryParse(
            '${earnings['withdrawable_balance'] ?? earnings['current_balance'] ?? earnings['balance'] ?? 0}',
          ) ??
          0;
    }
    return SellerWithdrawBundle(
      withdraws: list is List
          ? list
              .whereType<Map>()
              .map(
                (e) => SellerWithdrawItem.fromMap(Map<String, dynamic>.from(e)),
              )
              .toList()
          : const [],
      balanceHint: balance,
    );
  }
}

class SellerWithdrawItem {
  SellerWithdrawItem({
    required this.id,
    required this.method,
    required this.totalAmount,
    required this.withdrawAmount,
    required this.status,
    required this.createdAt,
  });

  final int id;
  final String method;
  final double totalAmount;
  final double withdrawAmount;
  final int status;
  final String createdAt;

  String get statusLabel => switch (status) {
        0 => 'Bekliyor',
        1 => 'Onaylandı',
        2 => 'Reddedildi',
        _ => 'Durum $status',
      };

  factory SellerWithdrawItem.fromMap(Map<String, dynamic> map) {
    return SellerWithdrawItem(
      id: int.tryParse('${map['id']}') ?? 0,
      method: '${map['method'] ?? ''}',
      totalAmount: double.tryParse('${map['total_amount'] ?? 0}') ?? 0,
      withdrawAmount: double.tryParse('${map['withdraw_amount'] ?? 0}') ?? 0,
      status: int.tryParse('${map['status'] ?? 0}') ?? 0,
      createdAt: '${map['created_at'] ?? ''}',
    );
  }
}

class SellerWithdrawMethod {
  SellerWithdrawMethod({
    required this.id,
    required this.name,
    required this.minAmount,
    required this.maxAmount,
    required this.withdrawCharge,
    required this.description,
  });

  final int id;
  final String name;
  final double minAmount;
  final double maxAmount;
  final double withdrawCharge;
  final String description;

  factory SellerWithdrawMethod.fromMap(Map<String, dynamic> map) {
    return SellerWithdrawMethod(
      id: int.tryParse('${map['id']}') ?? 0,
      name: '${map['name'] ?? ''}',
      minAmount: double.tryParse('${map['min_amount'] ?? 0}') ?? 0,
      maxAmount: double.tryParse('${map['max_amount'] ?? 0}') ?? 0,
      withdrawCharge: double.tryParse('${map['withdraw_charge'] ?? 0}') ?? 0,
      description: '${map['description'] ?? map['account_info'] ?? ''}',
    );
  }
}
