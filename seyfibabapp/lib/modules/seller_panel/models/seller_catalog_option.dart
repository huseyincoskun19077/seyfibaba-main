class SellerCatalogOption {
  SellerCatalogOption({required this.id, required this.name});

  final int id;
  final String name;

  factory SellerCatalogOption.fromMap(Map<String, dynamic> map) {
    return SellerCatalogOption(
      id: int.tryParse('${map['id']}') ?? 0,
      name: '${map['name'] ?? ''}',
    );
  }
}

class SellerProductCreateMeta {
  SellerProductCreateMeta({
    required this.categories,
    required this.brands,
  });

  final List<SellerCatalogOption> categories;
  final List<SellerCatalogOption> brands;

  factory SellerProductCreateMeta.fromMap(Map<String, dynamic> map) {
    List<SellerCatalogOption> parse(dynamic raw) {
      if (raw is! List) return const [];
      return raw
          .whereType<Map>()
          .map((e) => SellerCatalogOption.fromMap(Map<String, dynamic>.from(e)))
          .where((e) => e.id > 0)
          .toList();
    }

    return SellerProductCreateMeta(
      categories: parse(map['categories']),
      brands: parse(map['brands']),
    );
  }
}
