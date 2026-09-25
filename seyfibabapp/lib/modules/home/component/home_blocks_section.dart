import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../controller/cubit/product/products_cubit.dart';
import '../model/home_block_model.dart';
import '../model/home_model.dart';
import '../widgets/home_theme.dart';
import 'category_grid_view.dart';
import 'flash_sale_component.dart';
import 'home_brands_section.dart';
import 'populer_product_component.dart';
import 'story_actions.dart';

/// Admin home_blocks → CustomScrollView sliver listesi.
List<Widget> buildHomeBlockSlivers(BuildContext context, HomeModel homeModel) {
  final blocks = homeModel.homeBlocks;
  if (blocks.isEmpty) return const [];

  final out = <Widget>[];
  var i = 0;
  while (i < blocks.length) {
    final b = blocks[i];
    if (b.type == 'campaign') {
      final group = <HomeBlockModel>[];
      while (i < blocks.length && blocks[i].type == 'campaign') {
        group.add(blocks[i]);
        i++;
      }
      out.add(SliverToBoxAdapter(child: _CampaignStrip(campaigns: group)));
      continue;
    }

    if (b.type == 'product_feed' || b.type == 'all_products') {
      if (b.products.isNotEmpty) {
        out.add(
          HorizontalProductComponent(
            productList: b.products,
            category: b.title,
            onTap: () => _openSeeAll(context, b),
          ),
        );
      }
    } else if (b.type == 'category_grid') {
      out.add(CategoryGridView(model: homeModel));
    } else if (b.type == 'brands') {
      out.add(SliverToBoxAdapter(child: HomeBrandsSection(model: homeModel)));
    } else if (b.type == 'flash_sale') {
      final appSetting = context.read<AppSettingCubit>().settingModel;
      final active = appSetting?.flashSaleActive == true ||
          appSetting?.flashSaleActive == 1 ||
          '${appSetting?.flashSaleActive}' == '1';
      if (active) {
        out.add(FlashSaleComponent(flashSale: homeModel.flashSale));
      }
    }
    i++;
  }
  return out;
}

void _openSeeAll(BuildContext context, HomeBlockModel block) {
  final productCubit = context.read<ProductsCubit>();
  if (productCubit.state.initialPage > 1) {
    productCubit.initPage();
  }
  productCubit.nameChange(block.title);

  String keyword = 'popular_category';
  switch (block.feed) {
    case 'discounted':
      keyword = 'discounted';
      break;
    case 'featured':
      keyword = 'featured_product';
      break;
    case 'new':
      keyword = 'new_arrival';
      break;
    case 'best':
      keyword = 'best_product';
      break;
    case 'popular':
      keyword = 'popular_category';
      break;
  }

  final seeAll = (block.seeAllUrl ?? '').trim();
  if (seeAll.contains('highlight=')) {
    keyword = seeAll.split('highlight=').last.split('&').first;
  }

  Navigator.pushNamed(
    context,
    RouteNames.allPopularProductScreen,
    arguments: keyword,
  );
}

class _CampaignStrip extends StatelessWidget {
  const _CampaignStrip({required this.campaigns});

  final List<HomeBlockModel> campaigns;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 148,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 10, 16, 10),
        scrollDirection: Axis.horizontal,
        itemCount: campaigns.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final c = campaigns[index];
          final url = RemoteUrls.imageUrl(c.image ?? '');
          return GestureDetector(
            onTap: () {
              final link = (c.mobileLink ?? '').trim().isNotEmpty
                  ? c.mobileLink!
                  : (c.link ?? '');
              if (link.trim().isEmpty) return;
              openStoryLink(context, link);
            },
            child: ClipRRect(
              borderRadius: BorderRadius.circular(14),
              child: SizedBox(
                width: MediaQuery.of(context).size.width * 0.78,
                child: Image.network(
                  url,
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => Container(
                    color: HomeTheme.bg,
                    alignment: Alignment.center,
                    child: Text(
                      c.title,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
