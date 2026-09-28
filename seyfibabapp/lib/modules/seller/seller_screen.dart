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
    final shopName = singleSellerModel?.shopName ?? '';
    final rating = singleSellerModel?.averageRating ?? 0;
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.fromLTRB(16, 12, 16, 4),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: const Color(0xFFF4F6F7),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0x1A04334A)),
      ),
      child: Row(
        children: [
          Container(
            height: 72,
            width: 72,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white,
              border: Border.all(color: const Color(0x1A04334A)),
            ),
            clipBehavior: Clip.antiAlias,
            child: CustomImage(
              path: RemoteUrls.imageUrl(singleSellerModel?.logo ?? ''),
              fit: BoxFit.contain,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  shopName,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: GoogleFonts.inter(
                    fontWeight: FontWeight.w700,
                    fontSize: 18,
                    color: const Color(0xFF04334A),
                  ),
                ),
                const SizedBox(height: 6),
                Row(
                  children: [
                    ...List.generate(
                      5,
                      (i) => Icon(
                        i < rating.round() ? Icons.star : Icons.star_border,
                        size: 16,
                        color: const Color(0xFFFFA800),
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      '(${rating.toStringAsFixed(0)})',
                      style: const TextStyle(
                        fontSize: 12,
                        color: Color(0x9904334A),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
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
