import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../cart/model/add_to_cart_model.dart';
import '/widgets/fetch_error_text.dart';
import '/widgets/loading_widget.dart';

import '/widgets/page_refresh.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../utils/utils.dart';
import '../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../cart/controllers/cart/add_to_cart/add_to_cart_cubit.dart';
import '../cart/controllers/cart/cart_cubit.dart';
import 'component/category_grid_view.dart';
import 'component/flash_sale_component.dart';
import 'component/home_brands_section.dart';
import 'component/home_app_bar.dart';
import 'widgets/home_theme.dart';
import 'component/hot_deal_banner_slider.dart';
import 'component/new_arrival_component.dart';
import 'component/offer_banner_slider.dart';
import 'component/populer_product_component.dart';
import 'controller/cubit/home_controller_cubit.dart';
import 'controller/cubit/product/products_cubit.dart';
import 'model/banner_model.dart';
import 'model/home_model.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocListener<AddToCartCubit, AddToCartModel>(
      listenWhen: (previous, current) => true,
      listener: (context, addToCart) {
        final state = addToCart.addToState;
        if (state is AddToCartStateLoading) {
          Utils.loadingDialog(context);
        } else {
          Utils.closeDialog(context);
          if (state is AddToCartStateAdded) {
            context.read<CartCubit>().getCartProducts();
            Utils.showSnackBar(context, state.message);
          } else if (state is AddToCartStateError) {
            Utils.errorSnackBar(context, state.message);
          }
        }
      },
      child: Scaffold(
        backgroundColor: HomeTheme.bg,
        body: Column(
          children: [
            const HomeAppBar(),
            Expanded(
              child: PageRefresh(
                onRefresh: () async {
                  context.read<HomeControllerCubit>().getHomeData();
                },
                child: BlocBuilder<HomeControllerCubit, HomeControllerState>(
                  builder: (context, state) {
                    if (state is HomeControllerLoading) {
                      return const LoadingWidget();
                    }
                    if (state is HomeControllerError) {
                      return Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            FetchErrorText(text: state.errorMessage),
                            const SizedBox(height: 10),
                            IconButton(
                              onPressed: () {
                                context
                                    .read<HomeControllerCubit>()
                                    .getHomeData();
                              },
                              icon: const Icon(Icons.refresh_outlined),
                            ),
                          ],
                        ),
                      );
                    }

                    if (state is HomeControllerLoaded) {
                      return _LoadedHomePage(homeModel: state.homeModel);
                    }
                    return const SizedBox();
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _LoadedHomePage extends StatelessWidget {
  const _LoadedHomePage({required this.homeModel});

  final HomeModel homeModel;

  @override
  Widget build(BuildContext context) {
    final appSetting = context.read<AppSettingCubit>().settingModel;
    // final homeCubit = context.read<HomeControllerCubit>();
    final productCubit = context.read<ProductsCubit>();
    //print('banner-slider ${homeCubit.sliderBanner.length}');
    final combineBannerList = <BannerModel>[];
    final map = <String, String>{};
    homeModel.sectionTitle.map((e) {
      map[e.key] = e.custom!;
    }).toList();
    if (homeModel.twoColumnBannerOne != null) {
      combineBannerList.add(homeModel.twoColumnBannerOne!);
    }
    if (homeModel.twoColumnBannerTwo != null) {
      combineBannerList.add(homeModel.twoColumnBannerTwo!);
    }
    if (homeModel.singleBannerOne != null) {
      combineBannerList.add(homeModel.singleBannerOne!);
    }
    if (homeModel.singleBannerTwo != null) {
      combineBannerList.add(homeModel.singleBannerTwo!);
    }
    return CustomScrollView(
      physics: const BouncingScrollPhysics(
        parent: AlwaysScrollableScrollPhysics(),
      ),
      slivers: [
        const SliverToBoxAdapter(child: SizedBox(height: 12)),
        //Slider visibility start
        if (homeModel.sliderVisibilty is bool ||
            homeModel.sliderVisibilty is int ||
            homeModel.sliderVisibilty is String) ...[
          if (homeModel.sliderVisibilty == true ||
              homeModel.sliderVisibilty == 1 ||
              homeModel.sliderVisibilty == '1') ...[
            SliverToBoxAdapter(child: OfferBannerSlider())
          ],
        ] else ...[
          const SliverToBoxAdapter(child: SizedBox.shrink())
        ],

        //Slider visibility end
        CategoryGridView(model: homeModel),
        SliverToBoxAdapter(child: HomeBrandsSection(model: homeModel)),
        // const CountDownOfferAndProduct(),

        HorizontalProductComponent(
          productList: homeModel.popularCategoryProducts,
          category: 'En popüler ürünler',
          onTap: () {
            if (productCubit.state.initialPage > 1) {
              productCubit.initPage();
            }
            const keyword = "popular_category";
            const appBar = 'En popüler ürünler';
            productCubit.nameChange(appBar);
            Navigator.pushNamed(
              context,
              RouteNames.allPopularProductScreen,
              arguments: keyword,
            );
          },
        ),

        HorizontalProductComponent(
          productList: homeModel.discountedProducts,
          category: 'İndirimli ürünler',
          maxItems: 2,
          onTap: () {
            if (productCubit.state.initialPage > 1) {
              productCubit.initPage();
            }
            const keyword = "discounted";
            const appBar = 'İndirimli ürünler';
            productCubit.nameChange(appBar);
            Navigator.pushNamed(
              context,
              RouteNames.allPopularProductScreen,
              arguments: keyword,
            );
          },
        ),

        //Flash sale product visibility start
        if (appSetting!.flashSaleActive is bool ||
            appSetting.flashSaleActive is int ||
            appSetting.flashSaleActive is String) ...[
          if (appSetting.flashSaleActive == true ||
              appSetting.flashSaleActive == 1 ||
              appSetting.flashSaleActive == '1') ...[
            FlashSaleComponent(flashSale: homeModel.flashSale),
          ]
        ] else ...[
          const SliverToBoxAdapter(child: SizedBox.shrink())
        ],
        //Flash sale product visibility end

        //Best seller product visibility start
        //Best seller section removed
        const SliverToBoxAdapter(child: SizedBox.shrink()),
        //Best seller product visibility end

        const SliverToBoxAdapter(child: SizedBox(height: 5)),

        //Feature product visibility start
        if (homeModel.topRatedVisibility is bool ||
            homeModel.topRatedVisibility is int ||
            homeModel.topRatedVisibility is String) ...[
          if (homeModel.topRatedVisibility == true ||
              homeModel.topRatedVisibility == 1 ||
              homeModel.topRatedVisibility == '1') ...[
            HorizontalProductComponent(
              productList: homeModel.featuredCategoryProducts,
              category:
                  '${homeModel.sectionTitle[5].custom ?? homeModel.sectionTitle[5].dDefault}',
              onTap: () {
                if (productCubit.state.initialPage > 1) {
                  productCubit.initPage();
                }
                const keyword = "featured_product";
                final appBar =
                    '${homeModel.sectionTitle[5].custom ?? homeModel.sectionTitle[5].dDefault}';
                productCubit.nameChange(appBar);
                Navigator.pushNamed(
                  context,
                  RouteNames.allPopularProductScreen,
                  arguments: keyword,
                );
              },
            ),
          ]
        ] else ...[
          const SliverToBoxAdapter(child: SizedBox.shrink())
        ],
        //Feature product visibility end

        const SliverToBoxAdapter(child: SizedBox(height: 5)),
        //best product visibility start
        if (homeModel.bestProductVisibility is bool ||
            homeModel.bestProductVisibility is int ||
            homeModel.bestProductVisibility is String) ...[
          if (homeModel.bestProductVisibility == true ||
              homeModel.bestProductVisibility == 1 ||
              homeModel.bestProductVisibility == '1') ...[
            HorizontalProductComponent(
              productList: homeModel.bestProducts,
              category:
                  '${homeModel.sectionTitle[7].custom ?? homeModel.sectionTitle[7].dDefault}',
              onTap: () {
                if (productCubit.state.initialPage > 1) {
                  productCubit.initPage();
                }
                const keyword = "best_product";
                final appBar =
                    '${homeModel.sectionTitle[7].custom ?? homeModel.sectionTitle[7].dDefault}';
                productCubit.nameChange(appBar);
                Navigator.pushNamed(
                  context,
                  RouteNames.allPopularProductScreen,
                  arguments: keyword,
                );
              },
            ),
          ]
        ] else ...[
          const SliverToBoxAdapter(child: SizedBox.shrink())
        ],
        //best product visibility end

        //slider visibility start
        if (homeModel.sliderVisibilty is bool ||
            homeModel.sliderVisibilty is int ||
            homeModel.sliderVisibilty is String) ...[
          if (homeModel.sliderVisibilty == true ||
              homeModel.sliderVisibilty == 1 ||
              homeModel.sliderVisibilty == '1') ...[
            SliverToBoxAdapter(
              child: CombineBannerSlider(banners: combineBannerList),
            ),
          ]
        ] else ...[
          const SliverToBoxAdapter(child: SizedBox.shrink())
        ],
        //slider visibility end

        //new arrival visibility start
        if (homeModel.newArrivalProductVisibility is bool ||
            homeModel.newArrivalProductVisibility is int ||
            homeModel.newArrivalProductVisibility is String) ...[
          if (homeModel.newArrivalProductVisibility == true ||
              homeModel.newArrivalProductVisibility == 1 ||
              homeModel.newArrivalProductVisibility == '1') ...[
            NewArrivalComponent(
              sectionTitle:
                  '${homeModel.sectionTitle[6].custom ?? homeModel.sectionTitle[6].dDefault}',
              productList: homeModel.newArrivalProducts,
              onTap: () {
                if (productCubit.state.initialPage > 1) {
                  productCubit.initPage();
                }
                const keyword = "new_arrival";
                final appBar =
                    '${homeModel.sectionTitle[6].custom ?? homeModel.sectionTitle[6].dDefault}';
                productCubit.nameChange(appBar);
                Navigator.pushNamed(
                  context,
                  RouteNames.allPopularProductScreen,
                  arguments: keyword,
                );
              },
            ),
          ]
        ] else ...[
          const SliverToBoxAdapter(child: SizedBox.shrink())
        ],
        //new arrival visibility end
        const SliverToBoxAdapter(child: SizedBox(height: 60)),
      ],
    );
  }
}
