import 'dart:async';

import 'package:flutter/material.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../models/seller_product_model.dart';
import '../services/seller_api_service.dart';

class SellerProductsTab extends StatefulWidget {
  const SellerProductsTab({
    super.key,
    required this.token,
    this.kycStatus = 'approved',
  });

  final String token;
  final String kycStatus;

  @override
  State<SellerProductsTab> createState() => _SellerProductsTabState();
}

class _SellerProductsTabState extends State<SellerProductsTab> {
  final _service = SellerApiService();
  final _searchCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();
  Timer? _debounce;

  List<SellerProductModel> _products = const [];
  bool _loading = true;
  bool _loadingMore = false;
  String? _error;
  String _filter = 'all'; // all | active | inactive | low | out
  String _query = '';
  int _page = 1;
  int _lastPage = 1;
  int _total = 0;

  @override
  void initState() {
    super.initState();
    _load(reset: true);
    _searchCtrl.addListener(_onSearchChanged);
    _scrollCtrl.addListener(_onScroll);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchCtrl.removeListener(_onSearchChanged);
    _searchCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  void _onSearchChanged() {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      final next = _searchCtrl.text.trim();
      if (next == _query) return;
      _query = next;
      _load(reset: true);
    });
  }

  void _onScroll() {
    if (!_scrollCtrl.hasClients || _loading || _loadingMore) return;
    if (_page >= _lastPage) return;
    final pos = _scrollCtrl.position;
    if (pos.pixels >= pos.maxScrollExtent - 240) {
      _loadMore();
    }
  }

  Future<void> _load({required bool reset}) async {
    if (reset) {
      setState(() {
        _loading = true;
        _error = null;
        _page = 1;
        _lastPage = 1;
        _products = const [];
      });
    }
    try {
      final result = await _service.fetchProductsPage(
        token: widget.token,
        page: 1,
        perPage: 20,
        q: _query,
        filter: _filter,
      );
      if (!mounted) return;
      setState(() {
        _products = result.products;
        _page = result.currentPage;
        _lastPage = result.lastPage;
        _total = result.total;
        _loading = false;
        _error = null;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = '$e';
      });
    }
  }

  Future<void> _loadMore() async {
    if (_loadingMore || _page >= _lastPage) return;
    setState(() => _loadingMore = true);
    try {
      final nextPage = _page + 1;
      final result = await _service.fetchProductsPage(
        token: widget.token,
        page: nextPage,
        perPage: 20,
        q: _query,
        filter: _filter,
      );
      if (!mounted) return;
      setState(() {
        _products = [..._products, ...result.products];
        _page = result.currentPage;
        _lastPage = result.lastPage;
        _total = result.total;
        _loadingMore = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loadingMore = false);
    }
  }

  Future<void> _setFilter(String value) async {
    if (_filter == value) return;
    setState(() => _filter = value);
    await _load(reset: true);
  }

  Future<void> _toggleStatus(SellerProductModel product) async {
    try {
      Utils.loadingDialog(context);
      await _service.toggleProductStatus(widget.token, product.id);
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(
        context,
        product.isActive ? 'Ürün pasife alındı' : 'Ürün aktifleştirildi',
      );
      await _load(reset: true);
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  Future<void> _openQuickCreate() async {
    if (widget.kycStatus != 'approved') {
      Utils.errorSnackBar(context, 'Ürün eklemek için KYC onayınız gerekli.');
      return;
    }
    final ok = await Navigator.pushNamed(
      context,
      RouteNames.sellerQuickProductScreen,
    );
    if (ok == true && mounted) await _load(reset: true);
  }

  Future<void> _openFullCreate() async {
    if (widget.kycStatus != 'approved') {
      Utils.errorSnackBar(context, 'Ürün eklemek için KYC onayınız gerekli.');
      return;
    }
    final ok = await Navigator.pushNamed(
      context,
      RouteNames.sellerFullProductScreen,
    );
    if (ok == true && mounted) await _load(reset: true);
  }

  Future<void> _openBulk() async {
    if (widget.kycStatus != 'approved') {
      Utils.errorSnackBar(context, 'Excel yüklemek için KYC onayınız gerekli.');
      return;
    }
    await Navigator.pushNamed(context, RouteNames.sellerBulkImportScreen);
    if (mounted) await _load(reset: true);
  }

  Future<void> _openEdit(int id) async {
    final ok = await Navigator.pushNamed(
      context,
      RouteNames.sellerEditProductScreen,
      arguments: id,
    );
    if (ok == true && mounted) await _load(reset: true);
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
          child: Column(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 6),
                decoration: BoxDecoration(
                  color: HomeTheme.header,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(
                    color: HomeTheme.border.withValues(alpha: 0.7),
                  ),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: _ActionTile(
                        icon: Icons.flash_on_rounded,
                        label: 'Hızlı',
                        emphasize: true,
                        onTap: _openQuickCreate,
                      ),
                    ),
                    Container(width: 1, height: 36, color: HomeTheme.border),
                    Expanded(
                      child: _ActionTile(
                        icon: Icons.note_add_outlined,
                        label: 'Form',
                        onTap: _openFullCreate,
                      ),
                    ),
                    Container(width: 1, height: 36, color: HomeTheme.border),
                    Expanded(
                      child: _ActionTile(
                        icon: Icons.upload_file_outlined,
                        label: 'Excel',
                        onTap: _openBulk,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _searchCtrl,
                decoration: InputDecoration(
                  hintText: 'Ürün ara (ad / slug / SKU)',
                  prefixIcon: const Icon(Icons.search),
                  suffixIcon: _searchCtrl.text.isEmpty
                      ? null
                      : IconButton(
                          onPressed: () {
                            _searchCtrl.clear();
                          },
                          icon: const Icon(Icons.clear),
                        ),
                  filled: true,
                  fillColor: HomeTheme.header,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: HomeTheme.border),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: HomeTheme.border),
                  ),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                ),
              ),
              const SizedBox(height: 8),
              SizedBox(
                height: 40,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  children: [
                    _chip('Tümü', 'all'),
                    _chip('Aktif', 'active'),
                    _chip('Pasif', 'inactive'),
                    _chip('Düşük stok', 'low'),
                    _chip('Tükendi', 'out'),
                  ],
                ),
              ),
              if (!_loading && _error == null && _total > 0)
                Padding(
                  padding: const EdgeInsets.only(top: 6),
                  child: Align(
                    alignment: Alignment.centerLeft,
                    child: Text(
                      '$_total ürün',
                      style: const TextStyle(
                        fontSize: 12,
                        color: HomeTheme.textMuted,
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
        Expanded(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : _error != null
                  ? Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(_error!),
                          FilledButton(
                            onPressed: () => _load(reset: true),
                            child: const Text('Tekrar Dene'),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: () => _load(reset: true),
                      color: HomeTheme.brandYellow,
                      child: _products.isEmpty
                          ? ListView(
                              physics: const AlwaysScrollableScrollPhysics(),
                              children: [
                                const SizedBox(height: 80),
                                Center(
                                  child: Text(
                                    _query.isEmpty && _filter == 'all'
                                        ? 'Henüz ürün yok'
                                        : 'Filtreye uyan ürün yok',
                                    style: const TextStyle(
                                      color: HomeTheme.textMuted,
                                    ),
                                  ),
                                ),
                                if (_query.isEmpty && _filter == 'all') ...[
                                  const SizedBox(height: 16),
                                  Center(
                                    child: FilledButton(
                                      onPressed: _openQuickCreate,
                                      style: FilledButton.styleFrom(
                                        backgroundColor: HomeTheme.brandYellow,
                                        foregroundColor: HomeTheme.textDark,
                                      ),
                                      child: const Text('İlk ürünü ekle'),
                                    ),
                                  ),
                                ],
                              ],
                            )
                          : ListView.separated(
                              controller: _scrollCtrl,
                              physics: const AlwaysScrollableScrollPhysics(),
                              padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                              itemCount:
                                  _products.length + (_loadingMore ? 1 : 0),
                              separatorBuilder: (_, __) =>
                                  const SizedBox(height: 10),
                              itemBuilder: (context, index) {
                                if (index >= _products.length) {
                                  return const Padding(
                                    padding: EdgeInsets.symmetric(vertical: 16),
                                    child: Center(
                                      child: CircularProgressIndicator(),
                                    ),
                                  );
                                }
                                final p = _products[index];
                                final image = RemoteUrls.imageUrl(p.thumbImage);
                                return InkWell(
                                  borderRadius:
                                      BorderRadius.circular(HomeTheme.radius),
                                  onTap: () => _openEdit(p.id),
                                  child: Container(
                                    decoration: HomeTheme.cardDecoration(),
                                    padding: const EdgeInsets.all(12),
                                    child: Row(
                                      children: [
                                        ClipRRect(
                                          borderRadius:
                                              BorderRadius.circular(10),
                                          child: image.isEmpty
                                              ? Container(
                                                  width: 64,
                                                  height: 64,
                                                  color: HomeTheme.bg,
                                                  child: const Icon(
                                                    Icons.image_outlined,
                                                  ),
                                                )
                                              : Image.network(
                                                  image,
                                                  width: 64,
                                                  height: 64,
                                                  fit: BoxFit.cover,
                                                  errorBuilder: (_, __, ___) =>
                                                      Container(
                                                    width: 64,
                                                    height: 64,
                                                    color: HomeTheme.bg,
                                                    child: const Icon(
                                                      Icons.broken_image_outlined,
                                                    ),
                                                  ),
                                                ),
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                p.name,
                                                maxLines: 2,
                                                overflow: TextOverflow.ellipsis,
                                                style: const TextStyle(
                                                  fontWeight: FontWeight.w700,
                                                ),
                                              ),
                                              const SizedBox(height: 4),
                                              Text(
                                                Utils.formatPrice(
                                                  p.displayPrice,
                                                  context,
                                                ),
                                                style: const TextStyle(
                                                  fontWeight: FontWeight.w800,
                                                ),
                                              ),
                                              const SizedBox(height: 4),
                                              Text(
                                                'Stok: ${p.qty} · ${p.isApproved ? 'Onaylı' : 'Onay bekliyor'} · ${p.isActive ? 'Aktif' : 'Pasif'}',
                                                style: const TextStyle(
                                                  fontSize: 11,
                                                  color: HomeTheme.textMuted,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                        IconButton(
                                          tooltip: p.isActive
                                              ? 'Pasife al'
                                              : 'Aktifleştir',
                                          onPressed: () => _toggleStatus(p),
                                          icon: Icon(
                                            p.isActive
                                                ? Icons.toggle_on
                                                : Icons.toggle_off_outlined,
                                            color: p.isActive
                                                ? const Color(0xFF34A853)
                                                : HomeTheme.textMuted,
                                            size: 36,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                );
                              },
                            ),
                    ),
        ),
      ],
    );
  }

  Widget _chip(String label, String value) {
    final selected = _filter == value;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(label),
        selected: selected,
        onSelected: (_) => _setFilter(value),
        selectedColor: HomeTheme.brandYellow,
        labelStyle: TextStyle(
          fontWeight: selected ? FontWeight.w800 : FontWeight.w600,
          fontSize: 12,
          color: HomeTheme.textDark,
        ),
      ),
    );
  }
}

class _ActionTile extends StatelessWidget {
  const _ActionTile({
    required this.icon,
    required this.label,
    required this.onTap,
    this.emphasize = false,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final bool emphasize;

  @override
  Widget build(BuildContext context) {
    final fg = HomeTheme.textDark;
    return Material(
      color: emphasize
          ? HomeTheme.brandYellow.withValues(alpha: 0.55)
          : Colors.transparent,
      borderRadius: BorderRadius.circular(10),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 10),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, size: 22, color: fg),
              const SizedBox(height: 4),
              Text(
                label,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: emphasize ? FontWeight.w800 : FontWeight.w600,
                  color: fg,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
