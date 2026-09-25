import 'product_model.dart';
import 'home_category_model.dart';

class HomeBlockModel {
  final int id;
  final String title;
  final String type;
  final String? feed;
  final String? image;
  final String? link;
  final String? mobileLink;
  final String? seeAllUrl;
  final int limit;
  final int serial;
  final List<ProductModel> products;
  final List<HomePageCategoriesModel> categories;

  const HomeBlockModel({
    required this.id,
    required this.title,
    required this.type,
    this.feed,
    this.image,
    this.link,
    this.mobileLink,
    this.seeAllUrl,
    this.limit = 12,
    this.serial = 0,
    this.products = const [],
    this.categories = const [],
  });

  factory HomeBlockModel.fromMap(Map<String, dynamic> map) {
    final products = <ProductModel>[];
    final rawProducts = map['products'];
    if (rawProducts is List) {
      for (final e in rawProducts) {
        if (e is Map) {
          try {
            products.add(ProductModel.fromMap(Map<String, dynamic>.from(e)));
          } catch (_) {}
        }
      }
    }

    final categories = <HomePageCategoriesModel>[];
    final rawCats = map['categories'];
    if (rawCats is List) {
      for (final e in rawCats) {
        if (e is Map) {
          try {
            categories.add(
              HomePageCategoriesModel.fromMap(Map<String, dynamic>.from(e)),
            );
          } catch (_) {}
        }
      }
    }

    return HomeBlockModel(
      id: int.tryParse('${map['id'] ?? 0}') ?? 0,
      title: '${map['title'] ?? ''}',
      type: '${map['type'] ?? ''}',
      feed: map['feed']?.toString(),
      image: map['image']?.toString(),
      link: map['link']?.toString(),
      mobileLink: map['mobile_link']?.toString(),
      seeAllUrl: map['see_all_url']?.toString(),
      limit: int.tryParse('${map['limit'] ?? 12}') ?? 12,
      serial: int.tryParse('${map['serial'] ?? 0}') ?? 0,
      products: products,
      categories: categories,
    );
  }
}
