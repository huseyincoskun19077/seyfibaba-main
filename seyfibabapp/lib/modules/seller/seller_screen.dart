import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';
import '/modules/seller/seller_model.dart';
import '/widgets/fetch_error_text.dart';
import '/widgets/loading_widget.dart';
import 'package:sliver_tools/sliver_tools.dart';

import '/widgets/capitalized_word.dart';
import '/widgets/rounded_app_bar.dart';
import '../../core/remote_urls.dart';
import '../../utils/constants.dart';
import '../../utils/language_string.dart';
import '../../widgets/custom_image.dart';
import '../category/component/product_card.dart';
import '../category/controller/cubit/category_cubit.dart';
import '../home/controller/cubit/product/product_state_model.dart';
import '../home/model/home_seller_model.dart';

class SellerDetailsScreen extends StatefulWidget {
  const SellerDetailsScreen({super.key});

  @override
  State<SellerDetailsScreen> createState() => _SellerDetailsScreenState();
}

class _SellerDetailsScreenState extends State<SellerDetailsScreen> {
  late CategoryCubit categoryCubit;

  @override
  void initState() {
    super.initState();
    categoryCubit = context.read<CategoryCubit>();
    Future.microtask(() => categoryCubit.getSellerProduct());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: RoundedAppBar(
          titleText: categoryCubit.state.gender.capitalizeByWord()),
      body: BlocConsumer<CategoryCubit, ProductStateModel>(
        listener: (context, states) {
          final state = states.catState;
          if (state is CategoryErrorState) {
            if (state.statusCode == 503 ||
                categoryCubit.homeSellerModel == null) {
              categoryCubit.getSellerProduct();
            }
          }
        },
        builder: (context, states) {
          final state = states.catState;
          if (state is CategoryLoadingState) {
            return const LoadingWidget();
          } else if (state is SellerProductState) {
            if (state.sellerModel.products.isEmpty) {
              return Center(
                  child: Text(Language.noItemsFound.capitalizeByWord()));
            }
            return SellerProduct(home: state.sellerModel);
          } else if (state is CategoryErrorState) {
            if (state.statusCode == 503 ||
                categoryCubit.homeSellerModel != null) {
              return SellerProduct(home: categoryCubit.homeSellerModel);
            } else {
              return FetchErrorText(text: state.message);
            }
          }
          if (categoryCubit.homeSellerModel != null) {
            return SellerProduct(home: categoryCubit.homeSellerModel);
          } else {
            return FetchErrorText(
                text: Language.somethingWentWrong.capitalizeByWord());
          }
        },
      ),
    );
  }
}

class SingleSellerInfo extends StatelessWidget {
  const SingleSellerInfo({super.key, required this.singleSellerModel});

  final HomeSellerModel? singleSellerModel;

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        Container(
            height: 130.0,
            width: double.infinity,
            margin: const EdgeInsets.symmetric(horizontal: 0.0),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(10.0),
              child: CustomImage(
                  path:
                      RemoteUrls.imageUrl(singleSellerModel?.bannerImage ?? ''),
                  fit: BoxFit.cover),
            )),
        Positioned.fill(
          child: Padding(
            padding:
                const EdgeInsets.symmetric(horizontal: 20.0, vertical: 0.0),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.center,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      buildSellerInfo(
                          Icons.email, singleSellerModel?.email ?? ''),
                      buildSellerInfo(
                          Icons.phone, singleSellerModel?.phone ?? ''),
                      buildSellerInfo(
                          Icons.location_on, singleSellerModel?.address ?? ''),
                    ],
                  ),
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Container(
                      height: 60.0,
                      width: 60.0,
                      alignment: Alignment.center,
                      decoration: const BoxDecoration(
                          shape: BoxShape.circle, color: Colors.white),
                      child: Center(
                        child: CustomImage(
                          path: RemoteUrls.imageUrl(
                              singleSellerModel?.logo ?? ''),
                          height: 30.0,
                        ),
                      ),
                    ),
                    const SizedBox(height: 2.0),
                    Text(
                      singleSellerModel?.shopName ?? '',
                      style: GoogleFonts.inter(
                        fontWeight: FontWeight.w400,
                        fontSize: 12.0,
                        color: blackColor,
                      ),
                    )
                  ],
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget buildSellerInfo(IconData icon, String info) {
    return Expanded(
      child: Row(
        children: [
          Padding(
            padding: const EdgeInsets.only(top: 6.0, right: 10.0),
            child: Icon(
              icon,
              size: 20.0,
            ),
          ),
          Flexible(child: Text(info)),
        ],
      ),
    );
  }
}

class SellerProduct extends StatelessWidget {
  const SellerProduct({super.key, this.home});

  final SellerProductModel? home;

  @override
  Widget build(BuildContext context) {
    return CustomScrollView(
      slivers: [
        SliverPadding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
          sliver: MultiSliver(
            children: [
              SliverToBoxAdapter(
                  child: SingleSellerInfo(
                      singleSellerModel: home?.singleSellerModel)),
              const SizedBox(height: 20),
              SliverGrid(
                gridDelegate: ProductCard.listingDelegate(
                  context,
                  horizontalPadding: 20,
                  spacing: 8,
                  contentHeight: 96,
                ),
                delegate: SliverChildBuilderDelegate(
                  (BuildContext context, int index) {
                    final item = home?.products[index];
                    if (item != null) {
                      return ProductCard(productModel: item);
                    } else {
                      return const SizedBox.shrink();
                    }
                  },
                  childCount: home?.products.length,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
