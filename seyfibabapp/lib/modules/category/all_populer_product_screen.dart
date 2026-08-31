import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shop_o/utils/constants.dart';
import 'package:shop_o/widgets/page_refresh.dart';

import '../../utils/language_string.dart';
import '../../utils/utils.dart';
import '../../widgets/fetch_error_text.dart';
import '../../widgets/filter_empty_state.dart';
import '../../widgets/loading_widget.dart';
import '../category/component/product_category_chip_bar.dart';
import '../category/controller/cubit/category_cubit.dart';
import '../category/controller/cubit/cubit/sub_category_cubit.dart';
import '../category/model/sub_category_model.dart';
import '../category/utils/product_list_filter.dart';
import '../home/controller/cubit/product/product_state_model.dart';
import '../home/controller/cubit/product/products_cubit.dart';
import '../home/controller/cubit/product/products_state.dart';
import '../home/model/home_category_model.dart';
import '../home/model/product_model.dart';
import 'component/product_listing_filter_bar.dart';

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

  List<HomePageCategoriesModel> _categories = const [];
  List<SubCategoryModel> _subCategories = const [];
  String? _selectedCategorySlug;
  String? _selectedSubCategorySlug;
  bool _loadingCategories = true;

  @override
  void initState() {
    super.initState();
    _filter = widget.keyword == 'discounted'
        ? const ProductListFilter(onlyDiscount: true)
        : const ProductListFilter();
    final productCubit = context.read<ProductsCubit>();
    productCubit
      ..resetCategoryFilters()
      ..initPage();
    Future.microtask(() async {
      await productCubit.getHighlightedProduct(widget.keyword);
      await _loadCategories();
    });
    _scrollController.addListener(_onScroll);
  }

  Future<void> _loadCategories() async {
    final categoryCubit = context.read<CategoryCubit>();
    await categoryCubit.getCategoryList();
    if (!mounted) return;
    setState(() {
      _categories = categoryCubit.categoryList;
      _loadingCategories = false;
    });
  }

  Future<void> _loadSubCategories(int categoryId) async {
    final subCubit = context.read<SubCategoryCubit>();
    await subCubit.getSubCategoryList('$categoryId');
    if (!mounted) return;
    final state = subCubit.state;
    setState(() {
      if (state is SubCategoryListLoadedState) {
        _subCategories = state.subCategoryList;
      } else {
        _subCategories = const [];
      }
    });
  }

  Future<void> _reloadProducts() async {
    final productCubit = context.read<ProductsCubit>();
    productCubit.applyCategoryFilters(
      categorySlug: _selectedCategorySlug,
      subCategorySlug: _selectedSubCategorySlug,
    );
    await productCubit.getHighlightedProduct(widget.keyword);
  }

  Future<void> _onCategoryTap(HomePageCategoriesModel? category) async {
    setState(() {
      _selectedCategorySlug = category?.slug;
      _selectedSubCategorySlug = null;
      _subCategories = const [];
    });
    if (category != null) {
      await _loadSubCategories(category.id);
    }
    await _reloadProducts();
  }

  Future<void> _onSubCategoryTap(SubCategoryModel? subCategory) async {
    setState(() {
      _selectedSubCategorySlug = subCategory?.slug;
    });
    await _reloadProducts();
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
    var filterToApply =
        usingApiSearch ? _filter.copyWith(query: '') : _filter;

    if (widget.keyword == 'discounted') {
      filterToApply = filterToApply.copyWith(onlyDiscount: true);
    }

    return applyProductListFilter(listSource, filterToApply);
  }

  ProductListFilter _initialFilter() {
    if (widget.keyword == 'discounted') {
      return const ProductListFilter(onlyDiscount: true);
    }
    return const ProductListFilter();
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
            _filter = _initialFilter();
            _priceRange = null;
            _selectedCategorySlug = null;
            _selectedSubCategorySlug = null;
            _subCategories = const [];
          });
          productCubit.resetCategoryFilters();
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

            final chipBar = _loadingCategories
                ? const SizedBox.shrink()
                : ProductCategoryChipBar(
                    categories: _categories,
                    subCategories: _subCategories,
                    selectedCategorySlug: _selectedCategorySlug,
                    selectedSubCategorySlug: _selectedSubCategorySlug,
                    onCategoryTap: _onCategoryTap,
                    onSubCategoryTap: _onSubCategoryTap,
                  );

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
                    categoryChips: chipBar,
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
            final count = _filter.query.trim().length >= 2
                ? visible.length
                : (productCubit.highlightTotalProducts > 0
                    ? productCubit.highlightTotalProducts
                    : visible.length);

            return Column(
              children: [
                ProductListingFilterBar(
                  title: productCubit.state.name,
                  productCount: count,
                  filter: _filter,
                  priceMin: range.$1,
                  priceMax: range.$2,
                  categoryChips: chipBar,
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
