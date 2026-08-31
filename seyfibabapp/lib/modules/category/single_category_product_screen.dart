import 'package:flutter/material.dart';

import 'category_product_listing_screen.dart';
import 'model/category_navigation_args.dart';
import 'model/product_listing_kind.dart';

class SingleCategoryProductScreen extends StatelessWidget {
  const SingleCategoryProductScreen({
    super.key,
    required this.slug,
    this.name = '',
    this.categoryId,
  });

  final String slug;
  final String name;
  final int? categoryId;

  factory SingleCategoryProductScreen.fromArgs(Object? arguments) {
    if (arguments is CategoryProductArgs) {
      return SingleCategoryProductScreen(
        slug: arguments.slug,
        name: arguments.name,
        categoryId: arguments.categoryId,
      );
    }
    if (arguments is CategoryNavigationArgs) {
      return SingleCategoryProductScreen(
        slug: arguments.slug,
        name: arguments.name,
        categoryId: arguments.id,
      );
    }
    final slug = arguments?.toString() ?? '';
    return SingleCategoryProductScreen(slug: slug);
  }

  @override
  Widget build(BuildContext context) {
    final title = name.trim().isNotEmpty
        ? name.trim()
        : slug.replaceAll('-', ' ');

    return CategoryProductListingScreen(
      slug: slug,
      title: title,
      kind: ProductListingKind.category,
      categoryId: categoryId,
    );
  }
}
