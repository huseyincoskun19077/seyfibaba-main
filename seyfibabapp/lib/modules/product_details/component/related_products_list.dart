import 'package:flutter/material.dart';
import 'package:shop_o/utils/language_string.dart';
import 'package:shop_o/widgets/capitalized_word.dart';

import '../../category/component/product_card.dart';
import '../../home/component/section_header.dart';
import '../../home/model/product_model.dart';
import '../model/product_details_product_model.dart';

class RelatedProductsList extends StatelessWidget {
  const RelatedProductsList(
    this.relatedProducts, {
    super.key,
  });
  final List<ProductDetailsProductModel> relatedProducts;

  @override
  Widget build(BuildContext context) {
    final items = relatedProducts.length > 9
        ? relatedProducts.take(9).toList()
        : relatedProducts;
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 4),
          child: SectionHeader(
            headerText: Language.relatedProduct.capitalizeByWord(),
            onTap: () {},
            isSeeAllShow: false,
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
          child: GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: ProductCard.listingDelegate(
              context,
              horizontalPadding: 12,
              spacing: 8,
              contentHeight: 96,
            ),
            itemCount: items.length,
            itemBuilder: (context, index) => ProductCard(
              productModel: ProductModel.fromMap(items[index].toMap()),
            ),
          ),
        ),
      ],
    );
  }
}
