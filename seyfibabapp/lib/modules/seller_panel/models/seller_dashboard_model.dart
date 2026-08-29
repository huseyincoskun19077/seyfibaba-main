class SellerDashboardModel {
  SellerDashboardModel({
    required this.todayTotalOrder,
    required this.todayEarning,
    required this.todayPendingEarning,
    required this.todayProductSale,
    required this.monthlyTotalOrder,
    required this.thisMonthEarning,
    required this.thisMonthProductSale,
    required this.totalOrder,
    required this.totalPendingOrder,
    required this.totalCompleteOrder,
    required this.totalDeclinedOrder,
    required this.totalEarning,
    required this.totalProductSale,
    required this.totalProduct,
    required this.reviews,
    required this.reports,
    required this.totalWithdraw,
    required this.totalPendingWithdraw,
    required this.shopName,
  });

  final int todayTotalOrder;
  final double todayEarning;
  final double todayPendingEarning;
  final int todayProductSale;
  final int monthlyTotalOrder;
  final double thisMonthEarning;
  final int thisMonthProductSale;
  final int totalOrder;
  final int totalPendingOrder;
  final int totalCompleteOrder;
  final int totalDeclinedOrder;
  final double totalEarning;
  final int totalProductSale;
  final int totalProduct;
  final int reviews;
  final int reports;
  final double totalWithdraw;
  final double totalPendingWithdraw;
  final String shopName;

  factory SellerDashboardModel.fromMap(Map<String, dynamic> map) {
    final seller = map['seller'];
    String shopName = '';
    if (seller is Map) {
      shopName = '${seller['shop_name'] ?? seller['name'] ?? ''}'.trim();
    }
    return SellerDashboardModel(
      todayTotalOrder: _asInt(map['todayTotalOrder']),
      todayEarning: _asDouble(map['todayEarning']),
      todayPendingEarning: _asDouble(map['todayPendingEarning']),
      todayProductSale: _asInt(map['todayProductSale']),
      monthlyTotalOrder: _asInt(map['monthlyTotalOrder']),
      thisMonthEarning: _asDouble(map['thisMonthEarning']),
      thisMonthProductSale: _asInt(map['thisMonthProductSale']),
      totalOrder: _asInt(map['totalOrder']),
      totalPendingOrder: _asInt(map['totalPendingOrder']),
      totalCompleteOrder: _asInt(map['totalCompleteOrder']),
      totalDeclinedOrder: _asInt(map['totalDeclinedOrder']),
      totalEarning: _asDouble(map['totalEarning']),
      totalProductSale: _asInt(map['totalProductSale']),
      totalProduct: _asInt(map['total_product']),
      reviews: _asInt(map['reviews']),
      reports: _asInt(map['reports']),
      totalWithdraw: _asDouble(map['totalWithdraw']),
      totalPendingWithdraw: _asDouble(map['totalPendingWithdraw']),
      shopName: shopName,
    );
  }

  static int _asInt(dynamic v) => int.tryParse('$v') ?? 0;

  static double _asDouble(dynamic v) => double.tryParse('$v') ?? 0;
}
