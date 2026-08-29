import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/error/exception.dart';
import '../../../core/router_name.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../authentication/controller/login/login_bloc.dart';
import '../../category/component/product_card.dart';
import '../../main_page/main_controller.dart';
import '../../search/search_recent_storage.dart';
import '../models/second_hand_models.dart';
import '../services/second_hand_service.dart';
import '../widgets/second_hand_listing_card.dart';
import '../widgets/second_hand_my_listings_tab.dart';
import '../widgets/second_hand_ui.dart';
import '../widgets/turkey_address_selects.dart';
import 'second_hand_hub_screen.dart';

/// İkinci el ana kabuk — CRM / alışveriş ile aynı yüzen bottom nav.
class SecondHandListScreen extends StatefulWidget {
  const SecondHandListScreen({
    super.key,
    this.initialTab = 1,
  });

  /// 0 Menü(çıkış), 1 Keşfet, 2 İlanlarım, 3 Ekle, 4 Mesajlar, 5 Doğrula
  final int initialTab;

  @override
  State<SecondHandListScreen> createState() => _SecondHandListScreenState();
}

/// Eski hub rotası uyumu: 0 Doğrula, 1 İlanlarım, 2 Ekle, 3 Mesajlar
class SecondHandHubScreen extends StatelessWidget {
  const SecondHandHubScreen({super.key, this.initialTab = 0});

  final int initialTab;

  static int mapLegacyTab(int legacy) {
    switch (legacy) {
      case 0:
        return 5;
      case 1:
        return 2;
      case 2:
        return 3;
      case 3:
        return 4;
      default:
        return 1;
    }
  }

  @override
  Widget build(BuildContext context) {
    return SecondHandListScreen(initialTab: mapLegacyTab(initialTab));
  }
}

class _SecondHandListScreenState extends State<SecondHandListScreen> {
  final _service = SecondHandService();
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();

  final List<SecondHandListing> _items = [];
  Map<String, String> _conditionOptions =
      Map<String, String>.from(secondHandConditionLabels);

  bool _loading = true;
  bool _loadingMore = false;
  int _page = 1;
  int _lastPage = 1;
  String? _selectedCondition;
  String? _categoryId;
  String? _categoryName;
  String? _subCategoryId;
  List<Map<String, dynamic>> _subCategories = [];
  String? _province;
  String? _district;
  List<Map<String, dynamic>> _categories = [];
  late int _tab;
  List<String> _recentSearches = [];

  String get _token =>
      context.read<LoginBloc>().userInfo?.accessToken ?? '';

  @override
  void initState() {
    super.initState();
    _tab = widget.initialTab.clamp(1, 5);
    _load(reset: true);
    _loadRecentSearches();
    _scrollController.addListener(_onScroll);
    _service.fetchCategories().then((cats) {
      if (mounted) {
        setState(() {
          _categories = withoutCosmeticSecondHandCategories(cats);
        });
      }
    }).catchError((_) => <Map<String, dynamic>>[]);
  }

  @override
  void dispose() {
    _scrollController.removeListener(_onScroll);
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_loadingMore || _page >= _lastPage) return;
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      _loadMore();
    }
  }

  Future<void> _loadRecentSearches() async {
    final recent = await SearchRecentStorage.loadSecondHand();
    if (mounted) setState(() => _recentSearches = recent);
  }

  Future<void> _runSearch() async {
    final trimmed = _searchController.text.trim();
    if (trimmed.length >= 2) {
      await SearchRecentStorage.addSecondHand(trimmed);
      await _loadRecentSearches();
    }
    await _load(reset: true);
  }

  Future<void> _selectCategory(String? id, String? name) async {
    setState(() {
      _categoryId = id;
      _categoryName = name;
      _subCategoryId = null;
      _subCategories = [];
    });
    if (id != null) {
      Map<String, dynamic> selected = {};
      for (final c in _categories) {
        if ('${c['id']}' == id) {
          selected = c;
          break;
        }
      }
      var subs = SecondHandService.nestedList(selected, const [
        'active_sub_categories',
        'activeSubCategories',
        'sub_categories',
        'subCategories',
      ]);
      if (subs.isEmpty) {
        try {
          subs = await _service.fetchSubCategories(id);
        } catch (_) {
          subs = [];
        }
      }
      if (!mounted) return;
      setState(() {
        _subCategories = withoutCosmeticSecondHandCategories(subs);
      });
    }
    await _load(reset: true);
  }

  Future<void> _load({bool reset = false}) async {
    if (reset) {
      _page = 1;
      if (_items.isEmpty) {
        setState(() => _loading = true);
      }
    }
    try {
      final result = await _service.fetchPublicListings(
        page: 1,
        q: _searchController.text,
        condition: _selectedCondition,
        province: _province,
        district: _district,
        categoryId: _categoryId,
        subCategoryId: _subCategoryId,
        sort: 'views',
      );
      if (!mounted) return;
      setState(() {
        _items
          ..clear()
          ..addAll(result.items);
        _page = result.currentPage;
        _lastPage = result.lastPage;
        if (result.conditionOptions.isNotEmpty) {
          _conditionOptions = result.conditionOptions;
        }
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
      Utils.errorSnackBar(context, _errorMessage(e));
    }
  }

  Future<void> _loadMore() async {
    if (_loadingMore || _page >= _lastPage) return;
    setState(() => _loadingMore = true);
    try {
      final nextPage = _page + 1;
      final result = await _service.fetchPublicListings(
        page: nextPage,
        q: _searchController.text,
        condition: _selectedCondition,
        province: _province,
        district: _district,
        categoryId: _categoryId,
        subCategoryId: _subCategoryId,
        sort: 'views',
      );
      if (!mounted) return;
      setState(() {
        _items.addAll(result.items);
        _page = result.currentPage;
        _lastPage = result.lastPage;
        _loadingMore = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loadingMore = false);
      Utils.errorSnackBar(context, _errorMessage(e));
    }
  }

  String _errorMessage(Object e) {
    if (e is ServerException) return e.message;
    if (e is UnauthorisedException) return e.message;
    if (e is BadRequestException) return e.message;
    return 'Bir hata oluştu. Lütfen tekrar deneyin.';
  }

  void _goToMainHub() {
    MainController().naveListener.sink.add(0);
    final nav = Navigator.of(context);
    var foundMain = false;
    nav.popUntil((route) {
      if (route.settings.name == RouteNames.mainPage) {
        foundMain = true;
        return true;
      }
      return route.isFirst;
    });
    if (!foundMain && mounted) {
      nav.pushNamedAndRemoveUntil(RouteNames.mainPage, (route) => false);
    }
  }

  bool _requireLogin() {
    if (Utils.isLoggedIn(context)) return true;
    Utils.showSnackBarWithLogin(
      context,
      'Devam etmek için giriş yapın',
      () => Navigator.pushNamedAndRemoveUntil(
        context,
        RouteNames.authenticationScreen,
        (route) => false,
      ),
    );
    return false;
  }

  void _onNavTap(int i) {
    if (i == 0) {
      _goToMainHub();
      return;
    }
    if (i >= 2 && !_requireLogin()) return;
    setState(() => _tab = i);
  }

  bool get _hasActiveFilters =>
      (_categoryId?.isNotEmpty ?? false) ||
      (_province?.isNotEmpty ?? false) ||
      (_district?.isNotEmpty ?? false);

  Future<void> _openFilters() async {
    if (_categories.isEmpty) {
      try {
        _categories = withoutCosmeticSecondHandCategories(
          await _service.fetchCategories(),
        );
      } catch (_) {}
    }
    if (!mounted) return;

    final result = await showModalBottomSheet<_FilterResult>(
      context: context,
      isScrollControlled: true,
      backgroundColor: ShTheme.card,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(18)),
      ),
      builder: (ctx) => _SecondHandFilterSheet(
        categories: _categories,
        categoryId: _categoryId,
        province: _province,
        district: _district,
      ),
    );

    if (result == null || !mounted) return;
    setState(() {
      _categoryId = result.categoryId;
      _categoryName = result.categoryName;
      _province = result.province;
      _district = result.district;
    });
    _load(reset: true);
  }

  @override
  Widget build(BuildContext context) {
    // Nav: 0 Menü, 1 Keşfet, 2 İlanlarım, 3 Ekle, 4 Mesajlar
    // Doğrula (5) nav'da yok; İlanlarım'dan açılır, seçili olarak İlanlarım görünür
    final navIndex = _tab == 5 ? 2 : _tab.clamp(0, 4);

    return Scaffold(
      backgroundColor: ShTheme.bg,
      appBar: ShAppBar(
        title: 'İkinci El',
        showLogo: true,
        actions: [
          if (_tab == 1)
            IconButton(
              tooltip: 'Filtrele',
              onPressed: _openFilters,
              icon: Badge(
                isLabelVisible: _hasActiveFilters,
                smallSize: 8,
                child: const Icon(Icons.tune_rounded),
              ),
            ),
        ],
      ),
      body: _buildCurrentTab(),
      bottomNavigationBar: ShBottomNav(
        currentIndex: navIndex,
        onTap: _onNavTap,
        items: const [
          ShBottomNavItem(
            icon: Icons.apps_outlined,
            activeIcon: Icons.apps_rounded,
            label: 'Menü',
          ),
          ShBottomNavItem(
            icon: Icons.storefront_outlined,
            activeIcon: Icons.storefront_rounded,
            label: 'Keşfet',
          ),
          ShBottomNavItem(
            icon: Icons.inventory_2_outlined,
            activeIcon: Icons.inventory_2,
            label: 'İlanlarım',
          ),
          ShBottomNavItem(
            icon: Icons.add_circle_outline,
            activeIcon: Icons.add_circle,
            label: 'Ekle',
          ),
          ShBottomNavItem(
            icon: Icons.chat_bubble_outline,
            activeIcon: Icons.chat_bubble,
            label: 'Mesajlar',
          ),
        ],
      ),
    );
  }

  Widget _buildCurrentTab() {
    switch (_tab) {
      case 2:
        return SecondHandMyListingsTab(
          service: _service,
          token: _token,
          errorMessage: _errorMessage,
          onAddListing: () => setState(() => _tab = 3),
          onNeedVerification: () => setState(() => _tab = 5),
        );
      case 3:
        return SecondHandAddListingTab(
          service: _service,
          token: _token,
          errorMessage: _errorMessage,
          onPublished: () => setState(() => _tab = 2),
        );
      case 4:
        return SecondHandMessagesTab(
          service: _service,
          token: _token,
          errorMessage: _errorMessage,
        );
      case 5:
        return SecondHandVerificationTab(
          service: _service,
          token: _token,
          errorMessage: _errorMessage,
          onApproved: () => setState(() => _tab = 3),
        );
      default:
        return _buildBrowse();
    }
  }

  Widget _buildBrowse() {
    final showRecents = _searchController.text.trim().isEmpty &&
        _recentSearches.isNotEmpty;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const SizedBox(height: 8),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: ShSearchBar(
            controller: _searchController,
            hint: 'İlan, marka veya şehir ara',
            onSearch: _runSearch,
            onChanged: (_) => setState(() {}),
            onClear: () {
              _searchController.clear();
              _load(reset: true);
              setState(() {});
            },
          ),
        ),
        if (showRecents) ...[
          const SizedBox(height: 12),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    Language.recentSearches,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                      color: ShTheme.dark,
                    ),
                  ),
                ),
                GestureDetector(
                  onTap: () async {
                    await SearchRecentStorage.clearSecondHand();
                    await _loadRecentSearches();
                  },
                  child: const Text(
                    'Temizle',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: ShTheme.muted,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Wrap(
              spacing: 8,
              runSpacing: 8,
              children: _recentSearches
                  .map(
                    (term) => ActionChip(
                      visualDensity: VisualDensity.compact,
                      materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      labelPadding: const EdgeInsets.symmetric(horizontal: 6),
                      padding: EdgeInsets.zero,
                      label: Text(
                        term,
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                          color: ShTheme.dark,
                        ),
                      ),
                      backgroundColor: ShTheme.card,
                      side: const BorderSide(color: ShTheme.border),
                      onPressed: () {
                        _searchController.text = term;
                        _runSearch();
                      },
                    ),
                  )
                  .toList(),
            ),
          ),
        ],
        const SizedBox(height: 12),
        SizedBox(
          height: 38,
          child: ListView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            children: [
              Padding(
                padding: const EdgeInsets.only(right: 8),
                child: ShFilterChip(
                  label: 'Tümü',
                  selected: _categoryId == null,
                  onTap: () => _selectCategory(null, null),
                ),
              ),
              ..._categories.map(
                (c) => Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ShFilterChip(
                    label: '${c['name'] ?? ''}',
                    selected: _categoryId == '${c['id']}',
                    onTap: () => _selectCategory('${c['id']}', '${c['name'] ?? ''}'),
                  ),
                ),
              ),
            ],
          ),
        ),
        if (_categoryId != null && _subCategories.isNotEmpty) ...[
          const SizedBox(height: 8),
          SizedBox(
            height: 38,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              children: [
                Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ShFilterChip(
                    label: 'Tüm alt kategoriler',
                    selected: _subCategoryId == null,
                    onTap: () {
                      setState(() => _subCategoryId = null);
                      _load(reset: true);
                    },
                  ),
                ),
                ..._subCategories.map(
                  (s) => Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: ShFilterChip(
                      label: '${s['name'] ?? ''}',
                      selected: _subCategoryId == '${s['id']}',
                      onTap: () {
                        setState(() => _subCategoryId = '${s['id']}');
                        _load(reset: true);
                      },
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
        const SizedBox(height: 8),
        SizedBox(
          height: 38,
          child: ListView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            children: [
              Padding(
                padding: const EdgeInsets.only(right: 8),
                child: ShFilterChip(
                  label: 'Tümü',
                  selected: _selectedCondition == null,
                  onTap: () {
                    setState(() => _selectedCondition = null);
                    _load(reset: true);
                  },
                ),
              ),
              ..._conditionOptions.entries.map(
                (e) => Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ShFilterChip(
                    label: e.value,
                    selected: _selectedCondition == e.key,
                    onTap: () {
                      setState(() => _selectedCondition = e.key);
                      _load(reset: true);
                    },
                  ),
                ),
              ),
              if (_province != null)
                Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ShFilterChip(
                    label: _province!,
                    selected: true,
                    onTap: () {
                      setState(() => _province = null);
                      _load(reset: true);
                    },
                  ),
                ),
              if (_district != null)
                Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ShFilterChip(
                    label: _district!,
                    selected: true,
                    onTap: () {
                      setState(() => _district = null);
                      _load(reset: true);
                    },
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(height: 8),
        Expanded(child: _buildBrowseList()),
      ],
    );
  }

  Widget _buildBrowseList() {
    if (_loading) return const ShLoading();
    if (_items.isEmpty) {
      return ShEmptyState(
        icon: Icons.search_off_rounded,
        title: 'İlan bulunamadı',
        subtitle: 'Arama veya filtreleri değiştirip tekrar deneyin.',
      );
    }
    return RefreshIndicator(
      color: ShTheme.primary,
      onRefresh: () => _load(reset: true),
      child: GridView.builder(
        controller: _scrollController,
        cacheExtent: 800,
        padding: const EdgeInsets.fromLTRB(12, 4, 12, 24),
        gridDelegate: ProductCard.listingDelegate(context, contentHeight: 88),
        itemCount: _items.length + (_loadingMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index >= _items.length) {
            return const Center(child: ShLoading());
          }
          final listing = _items[index];
          return SecondHandListingCard(
            grid: true,
            listing: listing,
            onTap: () {
              Navigator.pushNamed(
                context,
                RouteNames.secondHandDetailScreen,
                arguments: listing.id,
              );
            },
          );
        },
      ),
    );
  }
}

class _FilterResult {
  const _FilterResult({
    this.categoryId,
    this.categoryName,
    this.province,
    this.district,
  });

  final String? categoryId;
  final String? categoryName;
  final String? province;
  final String? district;
}

class _SecondHandFilterSheet extends StatefulWidget {
  const _SecondHandFilterSheet({
    required this.categories,
    this.categoryId,
    this.province,
    this.district,
  });

  final List<Map<String, dynamic>> categories;
  final String? categoryId;
  final String? province;
  final String? district;

  @override
  State<_SecondHandFilterSheet> createState() => _SecondHandFilterSheetState();
}

class _SecondHandFilterSheetState extends State<_SecondHandFilterSheet> {
  late TurkeyAddressValue _address;
  String? _categoryId;

  @override
  void initState() {
    super.initState();
    _address = TurkeyAddressValue(
      province: widget.province ?? '',
      district: widget.district ?? '',
    );
    _categoryId = widget.categoryId;
  }

  void _apply() {
    String? name;
    if (_categoryId != null) {
      for (final c in widget.categories) {
        if ('${c['id']}' == _categoryId) {
          name = '${c['name']}';
          break;
        }
      }
    }
    Navigator.pop(
      context,
      _FilterResult(
        categoryId: _categoryId,
        categoryName: name,
        province: _address.province.trim().isEmpty
            ? null
            : _address.province.trim(),
        district: _address.district.trim().isEmpty
            ? null
            : _address.district.trim(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: 16,
        right: 16,
        top: 12,
        bottom: MediaQuery.of(context).viewInsets.bottom + 16,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: Container(
              width: 40,
              height: 4,
              margin: const EdgeInsets.only(bottom: 12),
              decoration: BoxDecoration(
                color: ShTheme.border,
                borderRadius: BorderRadius.circular(4),
              ),
            ),
          ),
          const Text(
            'Filtrele',
            style: TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w700,
              color: ShTheme.dark,
            ),
          ),
          const SizedBox(height: 14),
          ShDropdownField<String>(
            label: 'Kategori',
            value: _categoryId ?? '',
            hint: 'Tüm kategoriler',
            items: [
              const DropdownMenuItem(
                value: '',
                child: Text('Tüm kategoriler'),
              ),
              ...widget.categories.map(
                (c) => DropdownMenuItem(
                  value: '${c['id']}',
                  child: Text('${c['name']}'),
                ),
              ),
            ],
            onChanged: (v) => setState(() {
              _categoryId = (v == null || v.isEmpty) ? null : v;
            }),
          ),
          const SizedBox(height: 12),
          TurkeyAddressSelects(
            value: _address,
            showNeighborhood: false,
            onChanged: (v) => setState(() => _address = v),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: ShOutlineButton(
                  label: 'Temizle',
                  onPressed: () {
                    setState(() {
                      _categoryId = null;
                      _address = const TurkeyAddressValue();
                    });
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: ShPrimaryButton(
                  label: 'Uygula',
                  expand: true,
                  onPressed: _apply,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
