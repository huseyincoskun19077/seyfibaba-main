import '../../cart/model/cart_product_model.dart';

class PaymentPayloadHelper {
  static List<Map<String, dynamic>> cartProductsForApi(
      List<CartProductModel>? products) {
    if (products == null || products.isEmpty) return [];
    return products
        .map(
          (item) => {
            'product_id': item.productId,
            'qty': item.qty,
            'variants': item.variants
                .map(
                  (variant) => {
                    'variant_id': variant.variantId,
                    'variant_item_id': variant.variantItemId,
                  },
                )
                .toList(),
          },
        )
        .toList();
  }

  static Map<String, dynamic> mergePaymentBody({
    required Map<String, dynamic> baseBody,
    required List<CartProductModel>? cartProducts,
  }) {
    final apiCart = cartProductsForApi(cartProducts);
    final baseCart = baseBody['cart_products'];
    return {
      ...baseBody,
      'cart_products': apiCart.isNotEmpty
          ? apiCart
          : (baseCart is List ? baseCart : <dynamic>[]),
      'agree_terms_condition': '1',
    };
  }

  static double calculateProductsTotal(List<CartProductModel>? products) {
    if (products == null || products.isEmpty) return 0;
    return products.fold<double>(0, (sum, item) {
      final price = item.product.offerPrice > 0
          ? item.product.offerPrice
          : item.product.price;
      return sum + (price * item.qty);
    });
  }
}
