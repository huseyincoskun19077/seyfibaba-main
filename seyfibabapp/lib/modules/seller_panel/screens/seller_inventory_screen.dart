import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/remote_urls.dart';
import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../services/seller_api_service.dart';

enum _InventoryTab { inventory, lowStock, stockout }

class SellerInventoryScreen extends StatefulWidget {
  const SellerInventoryScreen({super.key});

  @override
  State<SellerInventoryScreen> createState() => _SellerInventoryScreenState();
}

class _SellerInventoryScreenState extends State<SellerInventoryScreen> {
  final _service = SellerApiService();
  _InventoryTab _tab = _InventoryTab.inventory;
  List<Map<String, dynamic>> _products = const [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final List<Map<String, dynamic>> products;
      switch (_tab) {
        case _InventoryTab.inventory:
          products = await _service.fetchInventory(_token);
        case _InventoryTab.lowStock:
          products = await _service.fetchLowStock(_token);
        case _InventoryTab.stockout:
          products = await _service.fetchStockout(_token);
      }
      if (!mounted) return;
      setState(() {
        _products = products;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = '$e';
      });
    }
  }

  void _setTab(_InventoryTab tab) {
    if (_tab == tab) return;
    setState(() => _tab = tab);
    _load();
  }

  Future<void> _openHistory(Map<String, dynamic> product) async {
    final id = int.tryParse('${product['id']}') ?? 0;
    if (id <= 0) return;
    Utils.loadingDialog(context);
    try {
      final data = await _service.fetchStockHistory(_token, id);
      if (!mounted) return;
      Utils.closeDialog(context);
      final histories = data['histories'];
      final list = histories is List
          ? histories
              .whereType<Map>()
              .map((e) => Map<String, dynamic>.from(e))
              .toList()
          : <Map<String, dynamic>>[];
      await showModalBottomSheet<void>(
        context: context,
        isScrollControlled: true,
        backgroundColor: HomeTheme.bg,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
        ),
        builder: (context) {
          return SafeArea(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: HomeTheme.border,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    '${product['name'] ?? 'Stok Geçmişi'}',
                    style: const TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 16,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Güncel stok: ${product['qty'] ?? 0}',
                    style: const TextStyle(
                      color: HomeTheme.textMuted,
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(height: 12),
                  if (list.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 32),
                      child: Center(
                        child: Text(
                          'Henüz stok hareketi yok',
                          style: TextStyle(color: HomeTheme.textMuted),
                        ),
                      ),
                    )
                  else
                    ConstrainedBox(
                      constraints: BoxConstraints(
                        maxHeight: MediaQuery.sizeOf(context).height * 0.45,
                      ),
                      child: ListView.separated(
                        shrinkWrap: true,
                        itemCount: list.length,
                        separatorBuilder: (_, __) => const Divider(height: 1),
                        itemBuilder: (context, index) {
                          final h = list[index];
                          final stockIn = h['stock_in'] ?? h['qty'] ?? '-';
                          final created = '${h['created_at'] ?? ''}';
                          return ListTile(
                            dense: true,
                            contentPadding: EdgeInsets.zero,
                            leading: const Icon(Icons.add_box_outlined),
                            title: Text('+$stockIn adet'),
                            subtitle: Text(
                              created.length >= 16
                                  ? created.substring(0, 16)
                                  : created,
                            ),
                          );
                        },
                      ),
                    ),
                  const SizedBox(height: 8),
                  FilledButton.icon(
                    onPressed: () {
                      Navigator.pop(context);
                      _showAddStockDialog(id, '${product['name'] ?? ''}');
                    },
                    icon: const Icon(Icons.add),
                    label: const Text('Stok Ekle'),
                    style: FilledButton.styleFrom(
                      backgroundColor: HomeTheme.brandYellow,
                      foregroundColor: HomeTheme.textDark,
                      minimumSize: const Size.fromHeight(46),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      );
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  Future<void> _showAddStockDialog(int productId, String name) async {
    final ctrl = TextEditingController(text: '1');
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(name.isEmpty ? 'Stok Ekle' : 'Stok Ekle — $name'),
        content: TextField(
          controller: ctrl,
          keyboardType: TextInputType.number,
          autofocus: true,
          decoration: const InputDecoration(
            labelText: 'Adet',
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
    final qty = int.tryParse(ctrl.text.trim()) ?? 0;
    ctrl.dispose();
    if (ok != true || !mounted) return;
    if (qty < 1) {
      Utils.errorSnackBar(context, 'Geçerli adet girin');
      return;
    }
    Utils.loadingDialog(context);
    try {
      await _service.addStock(
        token: _token,
        productId: productId,
        stockIn: qty,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, 'Stok eklendi');
      await _load();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  String get _emptyLabel => switch (_tab) {
        _InventoryTab.inventory => 'Envanterde ürün yok',
        _InventoryTab.lowStock => 'Düşük stoklu ürün yok',
        _InventoryTab.stockout => 'Tükenen ürün yok',
      };

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('Envanter / Stok'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
      ),
      body: Column(
        children: [
          SizedBox(
            height: 48,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.fromLTRB(12, 8, 12, 4),
              children: [
                _chip('Envanter', _InventoryTab.inventory),
                _chip('Düşük Stok', _InventoryTab.lowStock),
                _chip('Tükendi', _InventoryTab.stockout),
              ],
            ),
          ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: _load,
                    color: HomeTheme.brandYellow,
                    child: _error != null
                        ? ListView(
                            children: [
                              const SizedBox(height: 80),
                              Center(child: Text(_error!)),
                              const SizedBox(height: 12),
                              Center(
                                child: FilledButton(
                                  onPressed: _load,
                                  child: const Text('Tekrar Dene'),
                                ),
                              ),
                            ],
                          )
                        : _products.isEmpty
                            ? ListView(
                                children: [
                                  const SizedBox(height: 100),
                                  Icon(
                                    Icons.inventory_2_outlined,
                                    size: 48,
                                    color: HomeTheme.textMuted.withValues(
                                      alpha: 0.5,
                                    ),
                                  ),
                                  const SizedBox(height: 12),
                                  Center(
                                    child: Text(
                                      _emptyLabel,
                                      style: const TextStyle(
                                        color: HomeTheme.textMuted,
                                      ),
                                    ),
                                  ),
                                ],
                              )
                            : ListView.separated(
                                padding: const EdgeInsets.all(16),
                                itemCount: _products.length,
                                separatorBuilder: (_, __) =>
                                    const SizedBox(height: 8),
                                itemBuilder: (context, index) {
                                  final p = _products[index];
                                  final thumb = '${p['thumb_image'] ?? ''}';
                                  final qty =
                                      int.tryParse('${p['qty'] ?? 0}') ?? 0;
                                  return InkWell(
                                    borderRadius: BorderRadius.circular(
                                      HomeTheme.radius,
                                    ),
                                    onTap: () => _openHistory(p),
                                    onLongPress: () {
                                      final id =
                                          int.tryParse('${p['id']}') ?? 0;
                                      if (id > 0) {
                                        _showAddStockDialog(
                                          id,
                                          '${p['name'] ?? ''}',
                                        );
                                      }
                                    },
                                    child: Container(
                                      padding: const EdgeInsets.all(12),
                                      decoration: HomeTheme.cardDecoration(),
                                      child: Row(
                                        children: [
                                          ClipRRect(
                                            borderRadius:
                                                BorderRadius.circular(8),
                                            child: thumb.isEmpty
                                                ? Container(
                                                    width: 56,
                                                    height: 56,
                                                    color: HomeTheme.border
                                                        .withValues(alpha: 0.3),
                                                    child: const Icon(
                                                      Icons.image_outlined,
                                                    ),
                                                  )
                                                : Image.network(
                                                    RemoteUrls.imageUrl(thumb),
                                                    width: 56,
                                                    height: 56,
                                                    fit: BoxFit.cover,
                                                    errorBuilder: (_, __, ___) =>
                                                        Container(
                                                      width: 56,
                                                      height: 56,
                                                      color: HomeTheme.border
                                                          .withValues(
                                                        alpha: 0.3,
                                                      ),
                                                      child: const Icon(
                                                        Icons.broken_image,
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
                                                  '${p['name'] ?? 'Ürün'}',
                                                  maxLines: 2,
                                                  overflow:
                                                      TextOverflow.ellipsis,
                                                  style: const TextStyle(
                                                    fontWeight: FontWeight.w700,
                                                  ),
                                                ),
                                                const SizedBox(height: 4),
                                                Text(
                                                  'SKU: ${p['sku'] ?? '-'} · Stok: $qty',
                                                  style: const TextStyle(
                                                    fontSize: 12,
                                                    color: HomeTheme.textMuted,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                          IconButton(
                                            tooltip: 'Stok ekle',
                                            onPressed: () {
                                              final id = int.tryParse(
                                                    '${p['id']}',
                                                  ) ??
                                                  0;
                                              if (id > 0) {
                                                _showAddStockDialog(
                                                  id,
                                                  '${p['name'] ?? ''}',
                                                );
                                              }
                                            },
                                            icon: const Icon(Icons.add_circle_outline),
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
      ),
    );
  }

  Widget _chip(String label, _InventoryTab tab) {
    final selected = _tab == tab;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(label),
        selected: selected,
        onSelected: (_) => _setTab(tab),
        selectedColor: HomeTheme.brandYellow,
        labelStyle: TextStyle(
          color: HomeTheme.textDark,
          fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
        ),
      ),
    );
  }
}
