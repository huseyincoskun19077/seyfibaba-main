import 'package:flutter/material.dart';

import '../../utils/language_string.dart';
import 'category_product_listing_screen.dart';
import 'model/category_navigation_args.dart';
import 'model/product_listing_kind.dart';

class SubCategoryProductScreen extends StatelessWidget {
  const SubCategoryProductScreen({
    super.key,
    required this.args,
  });

  final CategoryProductArgs args;

  @override
  Widget build(BuildContext context) {
    return CategoryProductListingScreen(
      slug: args.slug,
      title: args.name.isNotEmpty ? args.name : Language.products,
      kind: ProductListingKind.subCategory,
    );
  }
}
