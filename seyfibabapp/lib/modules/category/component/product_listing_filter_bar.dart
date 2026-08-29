import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/remote_urls.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/app_bar_leading.dart';
import '../../../widgets/capitalized_word.dart';
import '../../home/model/product_model.dart';
import '../../search/controllers/search_repository.dart';
import '../../search/search_recent_storage.dart';
import '../component/product_card.dart';
import '../utils/product_list_filter.dart';

class ProductListingFilterBar extends StatelessWidget {
  const ProductListingFilterBar({
    super.key,
    required this.title,
    required this.productCount,
    required this.filter,
    required this.priceMin,
    required this.priceMax,
    required this.onFilterChanged,
    this.showBrandChip = false,
    this.onBrandTap,
    this.activeFilterCount,
    this.showBackButton = true,
    this.showTitleRow = true,
    this.searchController,
    this.showSearchField = true,
  });

  final String title;
  final int productCount;
  final ProductListFilter filter;
  final double priceMin;
  final double priceMax;
  final ValueChanged<ProductListFilter> onFilterChanged;
  final bool showBrandChip;
  final VoidCallback? onBrandTap;
  final int? activeFilterCount;
  final bool showBackButton;
  final bool showTitleRow;
  final TextEditingController? searchController;
  final bool showSearchField;

  @override
  Widget build(BuildContext context) {
    final primary = Utils.dynamicPrimaryColor(context);

    return Material(
      color: whiteColor,
      elevation: 0,
      child: SafeArea(
        bottom: false,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (showTitleRow)
              Padding(
                padding: const EdgeInsets.fromLTRB(8, 4, 16, 8),
                child: Row(
                  children: [
                    if (showBackButton) ...[
                      const AppbarLeading(),
                      const SizedBox(width: 8),
                    ],
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            title,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 17,
                              fontWeight: FontWeight.w700,
                              color: blackColor,
                              height: 1.2,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            '$productCount ürün',
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                              color: textGreyColor,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            if (!showTitleRow && title.isNotEmpty)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 4),
                child: Text(
                  '$title · $productCount ürün',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w500,
                    color: textGreyColor,
                  ),
                ),
              ),
            if (showSearchField)
              _ListingSearchField(
                filter: filter,
                onFilterChanged: onFilterChanged,
                controller: searchController,
              ),
            SizedBox(
              height: 44,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.fromLTRB(12, 0, 12, 8),
                children: [
                  _FilterChip(
                    label: 'Sırala',
                    icon: Icons.swap_vert,
                    active: filter.sort != ProductSortOption.recommended,
                    onTap: () => _openSortSheet(context),
                  ),
                  const SizedBox(width: 8),
                  _FilterChip(
                    label: Language.filter.capitalizeByWord(),
                    icon: Icons.tune,
                    active: (activeFilterCount ?? filter.activeCount) > 0,
                    badge: (activeFilterCount ?? filter.activeCount) > 0
                        ? '${activeFilterCount ?? filter.activeCount}'
                        : null,
                    accent: primary,
                    onTap: () => _openFilterSheet(context, primary),
                  ),
                  if (showBrandChip) ...[
                    const SizedBox(width: 8),
                    _FilterChip(
                      label: Language.brand.capitalizeByWord(),
                      icon: Icons.keyboard_arrow_down,
                      onTap: onBrandTap ??
                          () => _openFilterSheet(context, primary),
                    ),
                  ],
                ],
              ),
            ),
            const Divider(height: 1, color: grayBorderColor),
          ],
        ),
      ),
    );
  }

  Future<void> _openSortSheet(BuildContext context) async {
    final selected = await showModalBottomSheet<ProductSortOption>(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (ctx) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Padding(
                padding: EdgeInsets.all(16),
                child: Text(
                  'Sıralama',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                ),
              ),
              ...ProductSortOption.values.map(
                (option) => ListTile(
                  title: Text(sortLabel(option)),
                  trailing: filter.sort == option
                      ? Icon(Icons.check, color: Utils.dynamicPrimaryColor(ctx))
                      : null,
                  onTap: () => Navigator.pop(ctx, option),
                ),
              ),
              const SizedBox(height: 8),
            ],
          ),
        );
      },
    );

    if (selected != null) {
      onFilterChanged(filter.copyWith(sort: selected));
    }
  }

  Future<void> _openFilterSheet(BuildContext context, Color primary) async {
    var localMin = filter.minPrice ?? priceMin;
    var localMax = filter.maxPrice ?? priceMax;
    var onlyDiscount = filter.onlyDiscount;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                left: 16,
                right: 16,
                top: 16,
                bottom: MediaQuery.viewInsetsOf(context).bottom + 16,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Filtrele',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      TextButton(
                        onPressed: () {
                          setModalState(() {
                            localMin = priceMin;
                            localMax = priceMax;
                            onlyDiscount = false;
                          });
                        },
                        child: Text(Language.clearFilter),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '${Language.price.capitalizeByWord()}: ${localMin.toStringAsFixed(0)} - ${localMax.toStringAsFixed(0)} TL',
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                  RangeSlider(
                    values: RangeValues(localMin, localMax),
                    min: priceMin,
                    max: priceMax == priceMin ? priceMin + 1 : priceMax,
                    activeColor: primary,
                    onChanged: (values) {
                      setModalState(() {
                        localMin = values.start;
                        localMax = values.end;
                      });
                    },
                  ),
                  SwitchListTile(
                    contentPadding: EdgeInsets.zero,
                    title: Text(Language.discountedProducts),
                    value: onlyDiscount,
                    activeColor: primary,
                    onChanged: (v) => setModalState(() => onlyDiscount = v),
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: primary,
                        foregroundColor: blackColor,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(24),
                        ),
                      ),
                      onPressed: () {
                        onFilterChanged(
                          filter.copyWith(
                            minPrice: localMin,
                            maxPrice: localMax,
                            onlyDiscount: onlyDiscount,
                          ),
                        );
                        Navigator.pop(ctx);
                      },
                      child: Text(Language.findProduct.capitalizeByWord()),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }
}

class _ListingSearchField extends StatefulWidget {
  const _ListingSearchField({
    required this.filter,
    required this.onFilterChanged,
    this.controller,
  });

  final ProductListFilter filter;
  final ValueChanged<ProductListFilter> onFilterChanged;
  final TextEditingController? controller;

  @override
  State<_ListingSearchField> createState() => _ListingSearchFieldState();
}

class _ListingSearchFieldState extends State<_ListingSearchField> {
  late final TextEditingController _controller;
  late final bool _ownsController;
  List<String> _recent = [];

  @override
  void initState() {
    super.initState();
    _ownsController = widget.controller == null;
    _controller =
        widget.controller ?? TextEditingController(text: widget.filter.query);
    _loadRecent();
  }

  Future<void> _loadRecent() async {
    final recent = await SearchRecentStorage.load();
    if (mounted) setState(() => _recent = recent);
  }

  @override
  void didUpdateWidget(covariant _ListingSearchField oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.filter.query != _controller.text &&
        widget.filter.query != oldWidget.filter.query) {
      _controller.text = widget.filter.query;
    }
  }

  @override
  void dispose() {
    if (_ownsController) {
      _controller.dispose();
    }
    super.dispose();
  }

  void _apply(String raw, {bool saveRecent = false}) {
    final trimmed = raw.trim();
    final query = trimmed.length >= 2 ? trimmed : '';
    if (query != widget.filter.query) {
      widget.onFilterChanged(widget.filter.copyWith(query: query));
    }
    if (saveRecent && query.length >= 2) {
      SearchRecentStorage.add(query).then((_) => _loadRecent());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 0, 12, 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            height: 42,
            decoration: BoxDecoration(
              color: const Color(0xFFF4F4F6),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: grayBorderColor),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: Row(
              children: [
                const Icon(Icons.search_rounded, size: 20, color: textGreyColor),
                const SizedBox(width: 8),
                Expanded(
                  child: TextField(
                    controller: _controller,
                    textInputAction: TextInputAction.search,
                    decoration: InputDecoration(
                      hintText: Language.searchProductHint,
                      border: InputBorder.none,
                      isDense: true,
                      hintStyle: const TextStyle(
                        color: textGreyColor,
                        fontSize: 14,
                      ),
                    ),
                    onChanged: (value) {
                      setState(() {});
                      if (value.trim().isEmpty &&
                          widget.filter.query.isNotEmpty) {
                        widget.onFilterChanged(
                          widget.filter.copyWith(query: ''),
                        );
                      }
                    },
                    onSubmitted: (value) => _apply(value, saveRecent: true),
                  ),
                ),
                if (_controller.text.isNotEmpty)
                  GestureDetector(
                    onTap: () {
                      _controller.clear();
                      _apply('');
                    },
                    child: const Icon(Icons.close, size: 18, color: textGreyColor),
                  ),
              ],
            ),
          ),
          if (_recent.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              Language.recentSearches,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: textGreyColor,
              ),
            ),
            const SizedBox(height: 6),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                ..._recent.map(
                  (term) => InkWell(
                    onTap: () {
                      _controller.text = term;
                      _apply(term, saveRecent: true);
                    },
                    borderRadius: BorderRadius.circular(16),
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: whiteColor,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: grayBorderColor),
                      ),
                      child: Text(
                        term,
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  const _FilterChip({
    required this.label,
    required this.onTap,
    this.icon,
    this.active = false,
    this.badge,
    this.accent,
  });

  final String label;
  final VoidCallback onTap;
  final IconData? icon;
  final bool active;
  final String? badge;
  final Color? accent;

  @override
  Widget build(BuildContext context) {
    final color = accent ?? Utils.dynamicPrimaryColor(context);
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: whiteColor,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: active ? color : grayBorderColor,
            width: active ? 1.5 : 1,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (icon != null) ...[
              Icon(icon, size: 16, color: blackColor),
              const SizedBox(width: 4),
            ],
            Text(
              label,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: blackColor,
              ),
            ),
            if (badge != null) ...[
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: color,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  badge!,
                  style: const TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: blackColor,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class ProductListingGrid extends StatelessWidget {
  const ProductListingGrid({
    super.key,
    required this.products,
    this.controller,
    this.padding,
  });

  final List<ProductModel> products;
  final ScrollController? controller;
  final EdgeInsets? padding;

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.sizeOf(context);
    const spacing = 8.0;
    final horizontal = size.width < 360 ? 10.0 : 12.0;
    final gridPadding =
        padding ?? EdgeInsets.fromLTRB(horizontal, 12, horizontal, 24);

    return GridView.builder(
      controller: controller,
      padding: gridPadding,
      gridDelegate: ProductCard.listingDelegate(
        context,
        horizontalPadding: gridPadding.left,
        spacing: spacing,
        contentHeight: 96,
      ),
      itemCount: products.length,
      itemBuilder: (context, index) {
        return ProductCard(productModel: products[index]);
      },
    );
  }
}

mixin ListingApiSearchMixin<T extends StatefulWidget> on State<T> {
  List<ProductModel>? listingSearchResults;
  bool listingSearchLoading = false;
  String listingSearchedQuery = '';

  Future<void> runListingApiSearch(String query) async {
    final q = query.trim();
    if (q.length < 2) {
      if (listingSearchResults != null || listingSearchedQuery.isNotEmpty) {
        setState(() {
          listingSearchResults = null;
          listingSearchedQuery = '';
          listingSearchLoading = false;
        });
      }
      return;
    }
    if (q == listingSearchedQuery && listingSearchResults != null) {
      return;
    }

    setState(() => listingSearchLoading = true);
    final repo = context.read<SearchRepository>();
    final uri = Uri.parse(RemoteUrls.searchProduct).replace(
      queryParameters: {'search': q},
    );
    final result = await repo.search(uri);
    if (!mounted) return;
    result.fold(
      (_) {
        setState(() {
          listingSearchLoading = false;
          listingSearchedQuery = q;
          listingSearchResults = const [];
        });
      },
      (data) {
        setState(() {
          listingSearchLoading = false;
          listingSearchedQuery = q;
          listingSearchResults = data.products;
        });
      },
    );
  }
}

