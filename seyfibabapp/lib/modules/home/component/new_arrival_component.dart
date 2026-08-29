import 'package:custom_pop_up_menu/custom_pop_up_menu.dart';
import 'package:flutter/material.dart';
import 'package:sliver_tools/sliver_tools.dart';

import '../../../utils/constants.dart';
import '../../../utils/k_images.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_image.dart';
import '../../category/component/product_card.dart';
import '../model/product_model.dart';
import '../widgets/home_theme.dart';
import 'section_header.dart';

class NewArrivalComponent extends StatelessWidget {
  const NewArrivalComponent({
    super.key,
    required this.productList,
    required this.sectionTitle,
    this.onTap,
  });

  final List<ProductModel> productList;
  final String sectionTitle;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return SliverToBoxAdapter(
      child: Container(
        margin: const EdgeInsets.fromLTRB(16, 8, 16, 16),
        padding: const EdgeInsets.only(top: 14, bottom: 16),
        decoration: HomeTheme.cardDecoration(),
        child: Column(
          children: [
            SectionHeader(headerText: sectionTitle, onTap: onTap),
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
              itemCount: productList.length,
            ),
          ],
        ),
      ),
    );
  }
}

class _NewArrivalHeader extends StatefulWidget {
  const _NewArrivalHeader({required this.title});

  final String title;

  @override
  State<_NewArrivalHeader> createState() => _NewArrivalHeaderState();
}

class _NewArrivalHeaderState extends State<_NewArrivalHeader> {
  final _controller = CustomPopupMenuController();

  List<String> get list => <String>[
    Language.newArrival,
    Language.products,
    Language.bestSelling,
    Language.discountProduct,
    Language.highestPrice,
    Language.lowPrice,
    Language.freeDelivery,
  ];

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          widget.title,
          style: const TextStyle(
              fontSize: 18, height: 1.5, fontWeight: FontWeight.w600),
        ),
        CustomPopupMenu(
          pressType: PressType.singleClick,
          position: PreferredPosition.bottom,
          showArrow: false,
          verticalMargin: 4,
          controller: _controller,
          child: const SizedBox(
            height: 24,
            width: 24,
            child: Center(child: CustomImage(path: Kimages.menuIcon)),
          ),
          menuBuilder: () =>
              MenuItemListComponent(list: list, controller: _controller),
        ),
      ],
    );
  }
}

class MenuItemListComponent extends StatelessWidget {
  const MenuItemListComponent({
    super.key,
    required this.controller,
    required this.list,
  });

  final List<String> list;
  final CustomPopupMenuController controller;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(5),
      child: Container(
        color: Colors.white,
        width: 175,
        height: 280,
        alignment: Alignment.topLeft,
        padding: const EdgeInsets.only(left: 12),
        child: ListView(
          shrinkWrap: true,
          padding: EdgeInsets.zero,
          children: list
              .map(
                (e) => InkWell(
                  onTap: () {
                    controller.hideMenu();
                  },
                  child: Padding(
                    padding: const EdgeInsets.all(8),
                    child: Text(
                      e,
                      style: const TextStyle(fontSize: 16),
                    ),
                  ),
                ),
              )
              .toList(),
        ),
      ),
    );
  }
}
