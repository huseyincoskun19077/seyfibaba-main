import 'package:flutter/material.dart';
import 'package:sliver_tools/sliver_tools.dart';
import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../model/home_seller_model.dart';
import 'section_header.dart';
import 'single_circuler_seller.dart';

class BestSellerGridView extends StatelessWidget {
  const BestSellerGridView({
    super.key,
    required this.sectionTitle,
    required this.sellers,
  });

  final List<HomeSellerModel> sellers;
  final String sectionTitle;

  @override
  Widget build(BuildContext context) {
    if (sellers.isEmpty) return const SliverToBoxAdapter();
    return SliverPadding(
      padding: const EdgeInsets.only(bottom: 10, left: 0, right: 0),
      sliver: MultiSliver(
        children: [
          SliverToBoxAdapter(
            child: SectionHeader(
              headerText: sectionTitle,
              onTap: () {
                Navigator.pushNamed(context, RouteNames.allSellerList,
                    arguments: sellers);
              },
            ),
          ),
          const SliverToBoxAdapter(child: SizedBox(height: 10)),
          SliverToBoxAdapter(
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: List.generate(sellers.length,
                    (index) => Padding(
                      padding: Utils.symmetric(v: 6.0).copyWith(bottom: 20.0),
                      child: SingleCircularSeller(seller: sellers[index]),
                    )),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
