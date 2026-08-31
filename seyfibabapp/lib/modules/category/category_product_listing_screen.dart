import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../home/controller/cubit/product/product_state_model.dart';
import '../home/model/brand_model.dart';
import '../home/model/product_model.dart';
import '../../utils/constants.dart';
import '../../utils/language_string.dart';
import '../../utils/utils.dart';
import '../../widgets/capitalized_word.dart';
import '../../widgets/filter_empty_state.dart';
import '../../widgets/fetch_error_text.dart';
import '../../widgets/loading_widget.dart';
import '../../widgets/page_refresh.dart';
import 'component/product_category_chip_bar.dart';
import 'component/product_listing_filter_bar.dart';
import 'controller/cubit/category_cubit.dart';
import 'controller/cubit/cubit/sub_category_cubit.dart';
import 'model/product_listing_kind.dart';
import 'model/sub_category_model.dart';
import 'utils/product_list_filter.dart';

class CategoryProductListingScreen extends StatefulWidget {
  const CategoryProductListingScreen({
    super.key,
    required this.slug,
    required this.title,
    required this.kind,
    this.categoryId,
  });

  final String slug;
  final String title;
  final ProductListingKind kind;
  final int? categoryId;

  @override
  State<CategoryProductListingScreen> createState() =>
      _CategoryProductListingScreenState();
}

class _CategoryProductListingScreenState
    extends State<CategoryProductListingScreen> with ListingApiSearchMixin {
  late CategoryCubit _cubit;
  final _scrollController = ScrollController();
  ProductListFilter _filter = const ProductListFilter();
  Set<int> _selectedBrandIds = {};
  (double, double)? _priceRange;
  List<SubCategoryModel> _subCategories = const [];
  String? _selectedSubCategorySlug;
  String _listingSlug = '';
  ProductListingKind _listingKind = ProductListingKind.category;

  @override
  void initState() {
    super.initState();
    _cubit = context.read<CategoryCubit>();
    _listingSlug = widget.slug;
    _listingKind = widget.kind;
    Future.microtask(() async {
      _cubit.initPage();
      _cubit.changeTitle(widget.title);
      await _cubit.getProductListing(_listingSlug, _listingKind);
      if (widget.kind == ProductListingKind.category &&
          widget.categoryId != null) {
        await _loadSubCategories(widget.categoryId!);
      }
    });
    _scrollController.addListener(_onScroll);
  }

  Future<void> _loadSubCategories(int categoryId) async {
    final subCubit = context.read<SubCategoryCubit>();
    await subCubit.getSubCategoryList('$categoryId');
    if (!mounted) return;
    final state = subCubit.state;
    setState(() {
      if (state is SubCategoryListLoadedState) {
        _subCategories = state.subCategoryList;
      }
    });
  }

  Future<void> _onSubCategoryTap(SubCategoryModel? subCategory) async {
    setState(() {
      _selectedSubCategorySlug = subCategory?.slug;
      if (subCategory == null) {
        _listingSlug = widget.slug;
        _listingKind = ProductListingKind.category;
      } else {
        _listingSlug = subCategory.slug;
        _listingKind = ProductListingKind.subCategory;
      }
    });
    _cubit.initPage();
    await _cubit.getProductListing(_listingSlug, _listingKind);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.atEdge &&
        _scrollController.position.pixels != 0.0 &&
        !_cubit.state.isListEmpty) {
      _cubit.loadMoreListingProducts();
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
    var list = applyProductListFilter(listSource, filterToApply);
    if (_selectedBrandIds.isNotEmpty) {
      list = list
          .where((product) => _selectedBrandIds.contains(product.brandId))
          .toList();
    }
    return list;
  }

  int _activeFilterCount() {
    return _filter.activeCount + (_selectedBrandIds.isNotEmpty ? 1 : 0);
  }

  Future<void> _openBrandSheet(List<BrandModel> brands) async {
    final selected = Set<int>.from(_selectedBrandIds);
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      Language.brand.capitalizeByWord(),
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 8),
                    ConstrainedBox(
                      constraints: BoxConstraints(
                        maxHeight: MediaQuery.sizeOf(context).height * 0.45,
                      ),
                      child: ListView(
                        shrinkWrap: true,
                        children: brands
                            .map(
                              (brand) => CheckboxListTile(
                                contentPadding: EdgeInsets.zero,
                                value: selected.contains(brand.id),
                                title: Text(brand.name),
                                onChanged: (checked) {
                                  setModalState(() {
                                    if (checked == true) {
                                      selected.add(brand.id);
                                    } else {
                                      selected.remove(brand.id);
                                    }
                                  });
                                },
                              ),
                            )
                            .toList(),
                      ),
                    ),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: () {
                          setState(() => _selectedBrandIds = selected);
                          Navigator.pop(ctx);
                        },
                        child: Text(Language.findProduct.capitalizeByWord()),
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: scaBgColor,
      body: PageRefresh(
        onRefresh: () async {
          setState(() {
            _filter = const ProductListFilter();
            _selectedBrandIds = {};
            _priceRange = null;
          });
          await _cubit.reloadListingProducts();
        },
        child: BlocConsumer<CategoryCubit, ProductStateModel>(
          listener: (context, productState) {
            final state = productState.catState;
            if (state is CategoryErrorState && state.statusCode == 503) {
              _cubit.getProductListing(widget.slug, widget.kind);
            }
            if (state is CategoryLoadingState &&
                _cubit.state.initialPage != 1) {
              Utils.loadingDialog(context);
            } else if (state is CategoryMoreLoadedState) {
              Utils.closeDialog(context);
            }
          },
          builder: (context, productState) {
            final state = productState.catState;
            if (state is CategoryLoadingState &&
                _cubit.state.initialPage == 1) {
              return const LoadingWidget(color: greenColor);
            }

            if (state is CategoryErrorState &&
                state.statusCode != 503 &&
                _cubit.catProducts.isEmpty) {
              return FetchErrorText(text: state.message);
            }

            final products = _cubit.catProducts;
            final brands = _cubit.productCategoriesModel?.brands ?? [];
            final range = _priceRange ?? priceRangeForProducts(products);
            final filtered = _filteredProducts(products);
            final visible =
                filtered.isEmpty &&
                    products.isNotEmpty &&
                    _activeFilterCount() == 0
                ? products
                : filtered;
            final title = widget.title.isNotEmpty
                ? widget.title
                : _cubit.state.title;
            final productCount = _filter.query.trim().length >= 2
                ? visible.length
                : (_cubit.listingTotalProducts > 0
                    ? _cubit.listingTotalProducts
                    : visible.length);

            final chipBar = widget.kind == ProductListingKind.category &&
                    _subCategories.isNotEmpty
                ? ProductCategoryChipBar(
                    showCategories: false,
                    subCategories: _subCategories,
                    selectedSubCategorySlug: _selectedSubCategorySlug,
                    onSubCategoryTap: _onSubCategoryTap,
                  )
                : null;

            return Column(
              children: [
                ProductListingFilterBar(
                  title: title,
                  productCount: productCount,
                  filter: _filter.copyWith(
                    onlyDiscount: _filter.onlyDiscount,
                  ),
                  priceMin: range.$1,
                  priceMax: range.$2,
                  showBrandChip: brands.isNotEmpty,
                  activeFilterCount: _activeFilterCount(),
                  categoryChips: chipBar,
                  onFilterChanged: (f) {
                    setState(() => _filter = f);
                    runListingApiSearch(f.query);
                  },
                  onBrandTap: brands.isNotEmpty
                      ? () => _openBrandSheet(brands)
                      : null,
                ),
                Expanded(
                  child: listingSearchLoading
                      ? const LoadingWidget(color: greenColor)
                      : visible.isEmpty
                      ? FilterEmptyState(
                          hasProducts: products.isNotEmpty &&
                              _activeFilterCount() > 0,
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
