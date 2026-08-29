class SellerProductModel {
  SellerProductModel({
    required this.id,
    required this.name,
    required this.slug,
    required this.thumbImage,
    required this.price,
    required this.offerPrice,
    required this.qty,
    required this.status,
    required this.approveByAdmin,
  });

  final int id;
  final String name;
  final String slug;
  final String thumbImage;
  final double price;
  final double offerPrice;
  final int qty;
  final int status;
  final int approveByAdmin;

  bool get isActive => status == 1;
  bool get isApproved => approveByAdmin == 1;

  double get displayPrice => offerPrice > 0 ? offerPrice : price;

  factory SellerProductModel.fromMap(Map<String, dynamic> map) {
    return SellerProductModel(
      id: int.tryParse('${map['id']}') ?? 0,
      name: '${map['name'] ?? ''}',
      slug: '${map['slug'] ?? ''}',
      thumbImage: '${map['thumb_image'] ?? map['image'] ?? ''}',
      price: double.tryParse('${map['price'] ?? 0}') ?? 0,
      offerPrice: double.tryParse('${map['offer_price'] ?? 0}') ?? 0,
      qty: int.tryParse('${map['qty'] ?? 0}') ?? 0,
      status: int.tryParse('${map['status'] ?? 0}') ?? 0,
      approveByAdmin: int.tryParse('${map['approve_by_admin'] ?? 0}') ?? 0,
    );
  }
}

class SellerProductsPage {
  const SellerProductsPage({
    required this.products,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  final List<SellerProductModel> products;
  final int currentPage;
  final int lastPage;
  final int total;

  bool get hasMore => currentPage < lastPage;
}
