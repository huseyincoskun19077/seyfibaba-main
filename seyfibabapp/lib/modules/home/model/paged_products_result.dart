import 'product_model.dart';

class PagedProductsResult {
  const PagedProductsResult({
    required this.products,
    required this.total,
  });

  final List<ProductModel> products;
  final int total;
}
