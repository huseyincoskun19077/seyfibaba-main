import '../../home/model/product_model.dart';
import '../../../utils/utils.dart';

enum ProductSortOption {
  recommended,
  priceLow,
  priceHigh,
  rating,
  newest,
}

class ProductListFilter {
  const ProductListFilter({
    this.sort = ProductSortOption.recommended,
    this.minPrice,
    this.maxPrice,
    this.onlyDiscount = false,
    this.query = '',
  });

  final ProductSortOption sort;
  final double? minPrice;
  final double? maxPrice;
  final bool onlyDiscount;
  final String query;

  int get activeCount {
    var count = 0;
    if (onlyDiscount) count++;
    if (minPrice != null || maxPrice != null) count++;
    if (query.trim().length >= 2) count++;
    return count;
  }

  ProductListFilter copyWith({
    ProductSortOption? sort,
    double? minPrice,
    double? maxPrice,
    bool? onlyDiscount,
    String? query,
    bool clearPrice = false,
  }) {
    return ProductListFilter(
      sort: sort ?? this.sort,
      minPrice: clearPrice ? null : (minPrice ?? this.minPrice),
      maxPrice: clearPrice ? null : (maxPrice ?? this.maxPrice),
      onlyDiscount: onlyDiscount ?? this.onlyDiscount,
      query: query ?? this.query,
    );
  }
}

double productEffectivePrice(ProductModel product) {
  var offerPrice = 0.0;
  var mainPrice = 0.0;

  if (product.offerPrice != 0) {
    if (product.productVariants.isNotEmpty) {
      var variantExtra = 0.0;
      for (final variant in product.productVariants) {
        if (variant.activeVariantsItems.isNotEmpty) {
          variantExtra += Utils.toDouble(
            variant.activeVariantsItems.first.price.toString(),
          );
        }
      }
      offerPrice = variantExtra + product.offerPrice;
    } else {
      offerPrice = product.offerPrice;
    }
  }

  if (product.productVariants.isNotEmpty) {
    var variantExtra = 0.0;
    for (final variant in product.productVariants) {
      if (variant.activeVariantsItems.isNotEmpty) {
        variantExtra += Utils.toDouble(
          variant.activeVariantsItems.first.price.toString(),
        );
      }
    }
    mainPrice = variantExtra + product.price;
  } else {
    mainPrice = product.price;
  }

  return offerPrice != 0 ? offerPrice : mainPrice;
}

double productListPrice(ProductModel product) => productEffectivePrice(product);

bool productHasDiscount(ProductModel product) {
  if (product.offerPrice == 0) return false;
  return productEffectivePrice(product) < productListMainPrice(product);
}

double productListMainPrice(ProductModel product) {
  if (product.productVariants.isNotEmpty) {
    var variantExtra = 0.0;
    for (final variant in product.productVariants) {
      if (variant.activeVariantsItems.isNotEmpty) {
        variantExtra += Utils.toDouble(
          variant.activeVariantsItems.first.price.toString(),
        );
      }
    }
    return variantExtra + product.price;
  }
  return product.price;
}

String normalizeProductSearch(String value) {
  return value
      .toLowerCase()
      .replaceAll('ı', 'i')
      .replaceAll('İ', 'i')
      .replaceAll('I', 'i')
      .replaceAll('ğ', 'g')
      .replaceAll('ü', 'u')
      .replaceAll('ş', 's')
      .replaceAll('ö', 'o')
      .replaceAll('ç', 'c')
      .replaceAll('â', 'a')
      .replaceAll('î', 'i')
      .replaceAll('û', 'u');
}

bool productMatchesSearchQuery(ProductModel product, String query) {
  final q = normalizeProductSearch(query.trim());
  if (q.length < 2) return true;
  return normalizeProductSearch(product.name).contains(q) ||
      normalizeProductSearch(product.shortName).contains(q) ||
      normalizeProductSearch(product.slug).contains(q) ||
      normalizeProductSearch(product.shortDescription).contains(q);
}

List<ProductModel> applyProductListFilter(
  List<ProductModel> source,
  ProductListFilter filter,
) {
  Iterable<ProductModel> items = source;
  final q = filter.query.trim();
  if (q.length >= 2) {
    items = items.where((p) => productMatchesSearchQuery(p, q));
  }

  if (filter.onlyDiscount) {
    items = items.where(productHasDiscount);
  }

  if (filter.minPrice != null || filter.maxPrice != null) {
    items = items.where((p) {
      final price = productListPrice(p);
      if (filter.minPrice != null && price < filter.minPrice!) return false;
      if (filter.maxPrice != null && price > filter.maxPrice!) return false;
      return true;
    });
  }

  final list = items.toList();

  switch (filter.sort) {
    case ProductSortOption.priceLow:
      list.sort(
        (a, b) => productListPrice(a).compareTo(productListPrice(b)),
      );
      break;
    case ProductSortOption.priceHigh:
      list.sort(
        (a, b) => productListPrice(b).compareTo(productListPrice(a)),
      );
      break;
    case ProductSortOption.rating:
      list.sort((a, b) => b.rating.compareTo(a.rating));
      break;
    case ProductSortOption.newest:
      list.sort((a, b) => b.id.compareTo(a.id));
      break;
    case ProductSortOption.recommended:
      break;
  }

  return list;
}

(double min, double max) priceRangeForProducts(List<ProductModel> products) {
  if (products.isEmpty) return (0, 0);
  var min = double.infinity;
  var max = 0.0;
  for (final p in products) {
    final price = productListPrice(p);
    if (price < min) min = price;
    if (price > max) max = price;
  }
  if (min == double.infinity) min = 0;
  return (min, max);
}

String sortLabel(ProductSortOption option) {
  switch (option) {
    case ProductSortOption.recommended:
      return 'Önerilen';
    case ProductSortOption.priceLow:
      return 'Fiyat: Artan';
    case ProductSortOption.priceHigh:
      return 'Fiyat: Azalan';
    case ProductSortOption.rating:
      return 'Puana Göre';
    case ProductSortOption.newest:
      return 'En Yeni';
  }
}
