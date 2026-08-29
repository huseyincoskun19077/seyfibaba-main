import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '/utils/language_string.dart';
import '/widgets/capitalized_word.dart';
import '../../utils/constants.dart';
import '../../widgets/fetch_error_text.dart';
import '../../widgets/filter_empty_state.dart';
import '../../widgets/loading_widget.dart';
import '../home/controller/cubit/product/product_state_model.dart';
import '../home/model/product_model.dart';
import 'component/product_listing_filter_bar.dart';
import 'controller/cubit/category_cubit.dart';
import 'utils/product_list_filter.dart';

class BrandProductScreen extends StatefulWidget {
  const BrandProductScreen({
    super.key,
    required this.slug,
  });
  final String slug;

  @override
  State<BrandProductScreen> createState() => _BrandProductScreenState();
}

class _BrandProductScreenState extends State<BrandProductScreen>
    with ListingApiSearchMixin {
  ProductListFilter _filter = const ProductListFilter();
  (double, double)? _priceRange;
  late CategoryCubit _categoryCubit;

  @override
  void initState() {
    super.initState();
    _categoryCubit = context.read<CategoryCubit>();
    Future.microtask(() => _categoryCubit.getBrandProduct(widget.slug));
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
    return Scaffold(
      backgroundColor: scaBgColor,
      body: BlocBuilder<CategoryCubit, ProductStateModel>(
        builder: (context, states) {
          final state = states.catState;
          if (state is CategoryLoadingState) {
            return const LoadingWidget(color: greenColor);
          }
          if (state is CategoryErrorState) {
            return FetchErrorText(text: state.message);
          }
          if (state is CategoryLoadedState) {
            final products = _categoryCubit.brandProducts;
            if (products.isEmpty) {
              return Column(
                children: [
                  ProductListingFilterBar(
                    title: widget.slug.capitalizeByWord(),
                    productCount: 0,
                    filter: _filter,
                    priceMin: 0,
                    priceMax: 1,
                    onFilterChanged: (f) => setState(() => _filter = f),
                  ),
                  Expanded(
                    child: Center(
                      child: Text(Language.noItemsFound.capitalizeByWord()),
                    ),
                  ),
                ],
              );
            }

            final range = _priceRange ?? priceRangeForProducts(products);
            final filtered = _filteredProducts(products);
            final visible =
                filtered.isEmpty &&
                    products.isNotEmpty &&
                    _filter.activeCount == 0
                ? products
                : filtered;
            final title = _categoryCubit.state.title.isNotEmpty
                ? _categoryCubit.state.title.capitalizeByWord()
                : widget.slug.capitalizeByWord();

            return Column(
              children: [
                ProductListingFilterBar(
                  title: title,
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
                      : ProductListingGrid(products: visible),
                ),
              ],
            );
          }
          return FetchErrorText(text: Language.somethingWentWrong);
        },
      ),
    );
  }
}
