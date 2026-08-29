import 'package:flutter/material.dart';

import 'category_product_listing_screen.dart';
import 'model/category_navigation_args.dart';
import 'model/product_listing_kind.dart';

class ChildCategoryProductScreen extends StatelessWidget {
  const ChildCategoryProductScreen({super.key, required this.args});

  final CategoryProductArgs args;

  @override
  Widget build(BuildContext context) {
    return CategoryProductListingScreen(
      slug: args.slug,
      title: args.name,
      kind: ProductListingKind.childCategory,
    );
  }
}
