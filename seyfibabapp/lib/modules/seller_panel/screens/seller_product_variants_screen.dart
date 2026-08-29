import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../services/seller_api_service.dart';

class SellerProductVariantsScreen extends StatefulWidget {
  const SellerProductVariantsScreen({super.key, required this.productId});

  final int productId;

  @override
  State<SellerProductVariantsScreen> createState() =>
      _SellerProductVariantsScreenState();
}

class _SellerProductVariantsScreenState
    extends State<SellerProductVariantsScreen> {
  final _service = SellerApiService();
  late Future<List<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  Future<List<Map<String, dynamic>>> _load() =>
      _service.fetchProductVariants(_token, widget.productId);

  Future<void> _refresh() async {
    setState(() => _future = _load());
    await _future;
  }

  Future<void> _addVariant() async {
    final nameCtrl = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Yeni varyant'),
        content: TextField(
          controller: nameCtrl,
          decoration: const InputDecoration(
            labelText: 'Varyant adı (örn. Renk)',
            border: OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('İptal'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Ekle'),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    if (nameCtrl.text.trim().isEmpty) {
      Utils.errorSnackBar(context, 'Varyant adı girin');
      return;
    }
    try {
      Utils.loadingDialog(context);
      await _service.createProductVariant(
        token: _token,
        productId: widget.productId,
        name: nameCtrl.text.trim(),
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, 'Varyant eklendi');
      await _refresh();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  Future<void> _deleteVariant(int id) async {
    try {
      Utils.loadingDialog(context);
      await _service.deleteProductVariant(_token, id);
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, 'Varyant silindi');
      await _refresh();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  Future<void> _openItems(Map<String, dynamic> variant) async {
    final variantId = int.tryParse('${variant['id']}') ?? 0;
    if (variantId <= 0) return;
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _VariantItemsScreen(
          productId: widget.productId,
          variantId: variantId,
          variantName: '${variant['name'] ?? 'Varyant'}',
        ),
      ),
    );
    if (mounted) _refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('Varyantlar'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: _addVariant,
        backgroundColor: HomeTheme.brandYellow,
        child: const Icon(Icons.add, color: HomeTheme.textDark),
      ),
      body: FutureBuilder<List<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('${snapshot.error}'));
          }
          final variants = snapshot.data ?? const [];
          if (variants.isEmpty) {
            return RefreshIndicator(
              onRefresh: _refresh,
              child: ListView(
                children: const [
                  SizedBox(height: 120),
                  Center(child: Text('Henüz varyant yok')),
                ],
              ),
            );
          }
          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView.separated(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
              itemCount: variants.length,
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (context, index) {
                final v = variants[index];
                final id = int.tryParse('${v['id']}') ?? 0;
                final name = '${v['name'] ?? ''}';
                final items = v['variant_items'] ?? v['variantItems'];
                final count = items is List ? items.length : 0;
                return ListTile(
                  tileColor: HomeTheme.card,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(HomeTheme.radius),
                    side: BorderSide(
                      color: HomeTheme.border.withValues(alpha: 0.6),
                    ),
                  ),
                  title: Text(
                    name,
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                  subtitle: Text('$count seçenek'),
                  trailing: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      IconButton(
                        onPressed: id > 0 ? () => _deleteVariant(id) : null,
                        icon: const Icon(Icons.delete_outline),
                      ),
                      const Icon(Icons.chevron_right),
                    ],
                  ),
                  onTap: () => _openItems(v),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

class _VariantItemsScreen extends StatefulWidget {
  const _VariantItemsScreen({
    required this.productId,
    required this.variantId,
    required this.variantName,
  });

  final int productId;
  final int variantId;
  final String variantName;

  @override
  State<_VariantItemsScreen> createState() => _VariantItemsScreenState();
}

class _VariantItemsScreenState extends State<_VariantItemsScreen> {
  final _service = SellerApiService();
  late Future<List<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  Future<List<Map<String, dynamic>>> _load() => _service.fetchVariantItems(
        token: _token,
        productId: widget.productId,
        variantId: widget.variantId,
      );

  Future<void> _refresh() async {
    setState(() => _future = _load());
    await _future;
  }

  Future<void> _addItem() async {
    final nameCtrl = TextEditingController();
    final priceCtrl = TextEditingController(text: '0');
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Seçenek ekle'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: nameCtrl,
              decoration: const InputDecoration(
                labelText: 'Ad (örn. Kırmızı)',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: priceCtrl,
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'Ek fiyat',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('İptal'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Ekle'),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    final price =
        double.tryParse(priceCtrl.text.trim().replaceAll(',', '.')) ?? 0;
    if (nameCtrl.text.trim().isEmpty) {
      Utils.errorSnackBar(context, 'Seçenek adı girin');
      return;
    }
    try {
      Utils.loadingDialog(context);
      await _service.createVariantItem(
        token: _token,
        productId: widget.productId,
        variantId: widget.variantId,
        name: nameCtrl.text.trim(),
        price: price,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, 'Seçenek eklendi');
      await _refresh();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  Future<void> _deleteItem(int id) async {
    try {
      Utils.loadingDialog(context);
      await _service.deleteVariantItem(_token, id);
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, 'Seçenek silindi');
      await _refresh();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: Text(widget.variantName),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: _addItem,
        backgroundColor: HomeTheme.brandYellow,
        child: const Icon(Icons.add, color: HomeTheme.textDark),
      ),
      body: FutureBuilder<List<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('${snapshot.error}'));
          }
          final items = snapshot.data ?? const [];
          if (items.isEmpty) {
            return const Center(child: Text('Seçenek yok'));
          }
          return ListView.separated(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
            itemCount: items.length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (context, index) {
              final item = items[index];
              final id = int.tryParse('${item['id']}') ?? 0;
              final name = '${item['name'] ?? ''}';
              final price = double.tryParse('${item['price'] ?? 0}') ?? 0;
              return ListTile(
                tileColor: HomeTheme.card,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(HomeTheme.radius),
                ),
                title: Text(name),
                subtitle: Text(Utils.formatPrice(price, context)),
                trailing: IconButton(
                  onPressed: id > 0 ? () => _deleteItem(id) : null,
                  icon: const Icon(Icons.delete_outline),
                ),
              );
            },
          );
        },
      ),
    );
  }
}
