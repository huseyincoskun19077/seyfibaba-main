import 'package:flutter/material.dart';

import '../../category/component/product_card.dart';
import '../model/product_model.dart';
import '../widgets/home_theme.dart';
import 'section_header.dart';

class HorizontalProductComponent extends StatelessWidget {
  const HorizontalProductComponent({
    super.key,
    required this.productList,
    required this.category,
    this.bgColor,
    this.onTap,
    this.columns = 3,
    this.maxItems = 24,
  });

  final List<ProductModel> productList;
  final String category;
  final Color? bgColor;
  final VoidCallback? onTap;
  final int columns;
  final int maxItems;

  @override
  Widget build(BuildContext context) {
    if (productList.isEmpty) {
      return const SliverToBoxAdapter(child: SizedBox.shrink());
    }

    const gridPad = 10.0;
    const spacing = 8.0;

    return SliverToBoxAdapter(
      child: Container(
        margin: const EdgeInsets.fromLTRB(16, 8, 16, 4),
        padding: const EdgeInsets.only(top: 14, bottom: 12),
        decoration: HomeTheme.cardDecoration(color: bgColor ?? HomeTheme.card),
        child: Column(
          children: [
            SectionHeader(headerText: category, onTap: onTap),
            const SizedBox(height: 10),
            GridView.builder(
              shrinkWrap: true,
              padding: const EdgeInsets.symmetric(horizontal: gridPad),
              physics: const NeverScrollableScrollPhysics(),
              gridDelegate: ProductCard.listingDelegate(
                context,
                horizontalPadding: 16 + gridPad,
                spacing: spacing,
                contentHeight: 96,
              ),
              itemBuilder: (context, index) =>
                  ProductCard(productModel: productList[index]),
              itemCount:
                  productList.length > maxItems ? maxItems : productList.length,
            ),
          ],
        ),
      ),
    );
  }
}
