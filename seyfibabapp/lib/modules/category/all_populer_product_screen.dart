import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shop_o/utils/constants.dart';
import 'package:shop_o/widgets/page_refresh.dart';

import '../../utils/language_string.dart';
import '../../utils/utils.dart';
import '../../widgets/fetch_error_text.dart';
import '../../widgets/filter_empty_state.dart';
import '../../widgets/loading_widget.dart';
import '../home/controller/cubit/product/product_state_model.dart';
import '../home/controller/cubit/product/products_cubit.dart';
import '../home/controller/cubit/product/products_state.dart';
import '../home/model/product_model.dart';
import 'component/product_listing_filter_bar.dart';
import 'utils/product_list_filter.dart';

class AllPopularProductScreen extends StatefulWidget {
  const AllPopularProductScreen({super.key, required this.keyword});

  final String keyword;

  @override
  State<AllPopularProductScreen> createState() =>
      _AllPopularProductScreenState();
}

class _AllPopularProductScreenState extends State<AllPopularProductScreen>
    with ListingApiSearchMixin {
  final _scrollController = ScrollController();
  ProductListFilter _filter = const ProductListFilter();
  (double, double)? _priceRange;

  @override
  void initState() {
    super.initState();
    Future.microtask(() =>
        context.read<ProductsCubit>().getHighlightedProduct(widget.keyword));
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    final productCubit = context.read<ProductsCubit>();
    if (_scrollController.position.atEdge) {
      if (_scrollController.position.pixels != 0.0) {
        if (productCubit.state.isListEmpty == false) {
          context.read<ProductsCubit>().getHighlightedProduct(widget.keyword);
        }
      }
    }
  }

  List<ProductModel> _filteredProducts(List<ProductModel> source) {
    if (_priceRange == null && source.isNotEmpty) {
      _priceRange = priceRangeForProducts(source);
    }
    final apiResults = listingSearchResults;
    final usingApiSearch =
        _filter.query.trim().length >= 2 && apiResults != null;
    final listSource = usingApiSearch ? apiResults : source;
    final filterToApply =
        usingApiSearch ? _filter.copyWith(query: '') : _filter;
    return applyProductListFilter(listSource, filterToApply);
  }

  @override
  Widget build(BuildContext context) {
    final productCubit = context.read<ProductsCubit>();

    return Scaffold(
      backgroundColor: scaBgColor,
      body: PageRefresh(
        onRefresh: () async {
          if (productCubit.state.initialPage > 1) {
            productCubit.initPage();
          }
          setState(() {
            _filter = const ProductListFilter();
            _priceRange = null;
          });
          await productCubit.getHighlightedProduct(widget.keyword);
        },
        child: BlocConsumer<ProductsCubit, ProductStateModel>(
          listener: (context, productState) {
            final state = productState.productState;
            if (state is ProductsStateError) {
              if (state.statusCode == 503) {
                productCubit.getHighlightedProduct(widget.keyword);
              }
            } else if (state is ProductsStateLoading &&
                productCubit.state.initialPage != 1) {
              Utils.loadingDialog(context);
            } else if (state is MoreProductsStateLoaded) {
              Utils.closeDialog(context);
            }
          },
          builder: (context, productState) {
            final state = productState.productState;
            if (state is ProductsStateLoading &&
                productCubit.state.initialPage == 1) {
              return const LoadingWidget(color: greenColor);
            }

            List<ProductModel> products = productCubit.highLightedProducts;
            if (state is ProductsStateLoaded) {
              products = state.highlightedProducts;
            } else if (state is MoreProductsStateLoaded) {
              products = state.highlightedProducts;
            } else if (state is ProductsStateError && state.statusCode != 503) {
              return FetchErrorText(text: state.errorMessage);
            }

            if (products.isEmpty &&
                state is! ProductsStateLoading &&
                productCubit.state.initialPage == 1) {
              return Column(
                children: [
                  ProductListingFilterBar(
                    title: productCubit.state.name,
                    productCount: 0,
                    filter: _filter,
                    priceMin: 0,
                    priceMax: 1,
                    onFilterChanged: (f) => setState(() => _filter = f),
                  ),
                  Expanded(
                    child: Center(child: Text(Language.noItemsFound)),
                  ),
                ],
              );
            }

            if (products.isEmpty && state is ProductsStateError) {
              return FetchErrorText(text: Language.somethingWentWrong);
            }

            final range = _priceRange ?? priceRangeForProducts(products);
            final filtered = _filteredProducts(products);
            final visible =
                filtered.isEmpty &&
                    products.isNotEmpty &&
                    _filter.activeCount == 0
                ? products
                : filtered;

            return Column(
              children: [
                ProductListingFilterBar(
                  title: productCubit.state.name,
                  productCount: visible.length,
                  filter: _filter,
                  priceMin: range.$1,
                  priceMax: range.$2,
                  onFilterChanged: (f) {
                    setState(() => _filter = f);
                    runListingApiSearch(f.query);
                  },
                ),
                Expanded(
                  child: listingSearchLoading
                      ? const LoadingWidget(color: greenColor)
                      : visible.isEmpty
                      ? FilterEmptyState(
                          hasProducts:
                              products.isNotEmpty && _filter.activeCount > 0,
                        )
                      : ProductListingGrid(
                          products: visible,
                          controller: _scrollController,
                        ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}
