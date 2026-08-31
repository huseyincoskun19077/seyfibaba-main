class CategoryNavigationArgs {
  const CategoryNavigationArgs({
    required this.id,
    required this.slug,
    required this.name,
  });

  final int id;
  final String slug;
  final String name;
}

class CategoryProductArgs {
  const CategoryProductArgs({
    required this.slug,
    required this.name,
    this.categoryId,
  });

  final String slug;
  final String name;
  final int? categoryId;
}
