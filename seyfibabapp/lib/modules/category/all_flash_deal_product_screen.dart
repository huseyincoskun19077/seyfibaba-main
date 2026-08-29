import 'package:flutter/material.dart';
import '../../utils/language_string.dart';
import '../../widgets/rounded_app_bar.dart';
import '../home/model/product_model.dart';
import 'component/product_card.dart';

class AllFlashDealProductScreen extends StatelessWidget {
  const AllFlashDealProductScreen({
    super.key,
    required this.products,
  });
  final List<ProductModel> products;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: RoundedAppBar(
        titleText: "Flaş Fırsatlar",
        onTap: () {
          Navigator.pop(context);
        },
      ),
      body: products.isEmpty
          ? Center(child: Text(Language.noItemsFound))
          : _buildProductGrid(),
    );
  }

  Widget _buildProductGrid() {
    return Builder(
      builder: (context) {
        return GridView.builder(
          gridDelegate: ProductCard.listingDelegate(
            context,
            horizontalPadding: 12,
            spacing: 8,
            contentHeight: 96,
          ),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 16),
          itemCount: products.length,
          itemBuilder: (context, index) =>
              ProductCard(productModel: products[index]),
        );
      },
    );
  }
}
