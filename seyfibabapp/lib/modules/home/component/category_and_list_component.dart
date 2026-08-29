import 'package:flutter/material.dart';

import '../../category/component/product_card.dart';
import '../model/product_model.dart';
import '../widgets/home_theme.dart';
import 'section_header.dart';

class CategoryAndListComponent extends StatelessWidget {
  const CategoryAndListComponent({
    super.key,
    required this.productList,
    required this.category,
    this.bgColor,
    this.onTap,
  });

  final List<ProductModel> productList;
  final String category;
  final Color? bgColor;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    if (productList.isEmpty) return const SliverToBoxAdapter();

    return SliverToBoxAdapter(
      child: Container(
        margin: const EdgeInsets.fromLTRB(16, 8, 16, 4),
        padding: const EdgeInsets.only(top: 14, bottom: 16),
        decoration: HomeTheme.cardDecoration(color: bgColor ?? HomeTheme.card),
        child: Column(
          children: [
            SectionHeader(headerText: category, onTap: onTap),
            const SizedBox(height: 10),
            GridView.builder(
              shrinkWrap: true,
              padding: const EdgeInsets.symmetric(horizontal: 10),
              physics: const NeverScrollableScrollPhysics(),
              gridDelegate: ProductCard.listingDelegate(
                context,
                horizontalPadding: 26,
                spacing: 8,
                contentHeight: 96,
              ),
              itemBuilder: (context, index) =>
                  ProductCard(productModel: productList[index]),
              itemCount: productList.length > 9 ? 9 : productList.length,
            ),
          ],
        ),
      ),
    );
  }
}
