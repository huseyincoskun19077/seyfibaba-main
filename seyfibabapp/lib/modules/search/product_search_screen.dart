import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../utils/constants.dart';
import '../../utils/language_string.dart';
import '../../utils/utils.dart';
import '../../widgets/app_empty_state.dart';
import '../../widgets/filter_empty_state.dart';
import '../../widgets/fetch_error_text.dart';
import '../../widgets/loading_widget.dart';
import '../category/component/product_listing_filter_bar.dart';
import '../category/utils/product_list_filter.dart';
import '../home/model/product_model.dart';
import '../home/widgets/home_theme.dart';
import 'controllers/search/search_bloc.dart';
import 'search_recent_storage.dart';

class ProductSearchScreen extends StatefulWidget {
  const ProductSearchScreen({super.key});

  @override
  State<ProductSearchScreen> createState() => ProductSearchScreenState();
}

class ProductSearchScreenState extends State<ProductSearchScreen> {
  final searchCtr = TextEditingController();
  final _scrollController = ScrollController();
  ProductListFilter _filter = const ProductListFilter();
  (double, double)? _priceRange;
  List<String> _recentSearches = [];
  String _lastQuery = '';
  bool _hasSearched = false;

  late SearchBloc searchBloc;

  @override
  void initState() {
    super.initState();
    searchBloc = context.read<SearchBloc>();
    _loadRecentSearches();
    _scrollController.addListener(_onScroll);
    searchCtr.addListener(() {
      if (mounted) setState(() {});
      if (searchCtr.text.isEmpty) {
        setState(() {
          _hasSearched = false;
          _filter = const ProductListFilter();
          _priceRange = null;
        });
      }
    });
  }

  Future<void> _loadRecentSearches() async {
    final recent = await SearchRecentStorage.load();
    if (mounted) setState(() => _recentSearches = recent);
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      searchBloc.add(const SearchEventLoadMore());
    }
  }

  void _runSearch(String query) {
    final trimmed = query.trim();
    if (trimmed.length < 2) return;
    setState(() {
      _lastQuery = trimmed;
      _hasSearched = true;
      _filter = const ProductListFilter();
      _priceRange = null;
    });
    SearchRecentStorage.add(trimmed);
    _loadRecentSearches();
    searchBloc.add(SearchEventSearch(trimmed));
  }

  List<ProductModel> _filteredProducts(List<ProductModel> source) {
    if (_priceRange == null && source.isNotEmpty) {
      _priceRange = priceRangeForProducts(source);
    }
    return applyProductListFilter(source, _filter);
  }

  @override
  void dispose() {
    searchCtr.dispose();
    _scrollController.dispose();
    searchBloc.products.clear();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: scaBgColor,
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildSearchHeader(context),
            if (_hasSearched) _buildFilterBar(context),
            Expanded(
              child: BlocConsumer<SearchBloc, SearchState>(
                listener: (context, state) {
                  if (state is SearchStateMoreError) {
                    Utils.errorSnackBar(context, state.message);
                  }
                },
                builder: (context, state) {
                  if (!_hasSearched) {
                    return _buildSuggestions(context);
                  }
                  if (state is SearchStateLoading) {
                    return const LoadingWidget(color: greenColor);
                  }
                  if (state is SearchStateError) {
                    return Center(child: FetchErrorText(text: state.message));
                  }

                  final products = searchBloc.products;
                  if (products.isEmpty) {
                    return _buildEmptyResults(context);
                  }

                  final filtered = _filteredProducts(products);

                  if (filtered.isEmpty) {
                    return const FilterEmptyState(hasProducts: true);
                  }

                  return ProductListingGrid(
                    products: filtered,
                    controller: _scrollController,
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSearchHeader(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(8, 8, 12, 8),
      child: Row(
        children: [
          IconButton(
            onPressed: () => Navigator.pop(context),
            icon: const Icon(Icons.arrow_back_ios_new, size: 18),
          ),
          Expanded(
            child: Container(
              height: 44,
              decoration: BoxDecoration(
                color: HomeTheme.bg,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: HomeTheme.headerBorder),
              ),
              padding: const EdgeInsets.symmetric(horizontal: 12),
              child: Row(
                children: [
                  Icon(
                    Icons.search_rounded,
                    size: 20,
                    color: HomeTheme.textMuted.withValues(alpha: 0.85),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: TextField(
                      controller: searchCtr,
                      autofocus: true,
                      textInputAction: TextInputAction.search,
                      decoration: InputDecoration(
                        hintText: Language.searchProductHint,
                        border: InputBorder.none,
                        isDense: true,
                        hintStyle: TextStyle(
                          color: HomeTheme.textMuted.withValues(alpha: 0.9),
                          fontSize: 14,
                        ),
                      ),
                      onSubmitted: _runSearch,
                    ),
                  ),
                  if (searchCtr.text.isNotEmpty)
                    GestureDetector(
                      onTap: () {
                        searchCtr.clear();
                        searchBloc.products.clear();
                        setState(() {
                          _hasSearched = false;
                          _lastQuery = '';
                        });
                      },
                      child: const Icon(Icons.close, size: 18),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterBar(BuildContext context) {
    final products = searchBloc.products;
    final range = _priceRange ?? priceRangeForProducts(products);
    final filtered = products.isEmpty ? products : _filteredProducts(products);

    return ProductListingFilterBar(
      title: _lastQuery,
      productCount: filtered.length,
      filter: _filter,
      priceMin: range.$1,
      priceMax: range.$2,
      onFilterChanged: (f) => setState(() => _filter = f),
      showBackButton: false,
      showTitleRow: false,
      showSearchField: false,
    );
  }

  Widget _buildSuggestions(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Text(
          Language.searchEmptyHint,
          style: TextStyle(color: HomeTheme.textMuted, fontSize: 14),
        ),
        if (_recentSearches.isNotEmpty) ...[
          const SizedBox(height: 20),
          Text(
            Language.recentSearches,
            style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _recentSearches
                .map((term) => _SuggestionChip(
                      label: term,
                      onTap: () {
                        searchCtr.text = term;
                        _runSearch(term);
                      },
                    ))
                .toList(),
          ),
        ],
        const SizedBox(height: 20),
        Text(
          Language.popularSearches,
          style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
        ),
        const SizedBox(height: 10),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: kPopularSearchTerms
              .map(
                (term) => _SuggestionChip(
                  label: term,
                  onTap: () {
                    searchCtr.text = term;
                    _runSearch(term);
                  },
                ),
              )
              .toList(),
        ),
      ],
    );
  }

  Widget _buildEmptyResults(BuildContext context) {
    return AppEmptyState(
      icon: Icons.search_off_rounded,
      title: Language.noItemsFound,
      subtitle: Language.searchTryDifferent,
    );
  }
}

class _SuggestionChip extends StatelessWidget {
  const _SuggestionChip({required this.label, required this.onTap});

  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ActionChip(
      label: Text(label),
      backgroundColor: whiteColor,
      side: const BorderSide(color: grayBorderColor),
      onPressed: onTap,
    );
  }
}
