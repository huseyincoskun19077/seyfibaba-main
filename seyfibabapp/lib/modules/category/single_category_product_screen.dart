import 'package:flutter/material.dart';

import 'category_product_listing_screen.dart';
import 'model/category_navigation_args.dart';
import 'model/product_listing_kind.dart';

class SingleCategoryProductScreen extends StatelessWidget {
  const SingleCategoryProductScreen({
    super.key,
    required this.slug,
    this.name = '',
  });

  final String slug;
  final String name;

  factory SingleCategoryProductScreen.fromArgs(Object? arguments) {
    if (arguments is CategoryProductArgs) {
      return SingleCategoryProductScreen(
        slug: arguments.slug,
        name: arguments.name,
      );
    }
    if (arguments is CategoryNavigationArgs) {
      return SingleCategoryProductScreen(
        slug: arguments.slug,
        name: arguments.name,
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
    );
  }
}
