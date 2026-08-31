import 'package:flutter/material.dart';

import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../models/seller_order_model.dart';
import '../services/seller_api_service.dart';
import '../widgets/seller_order_flow_card.dart';
import 'seller_order_detail_screen.dart';

class SellerOrdersTab extends StatefulWidget {
  const SellerOrdersTab({super.key, required this.token});

  final String token;

  @override
  State<SellerOrdersTab> createState() => _SellerOrdersTabState();
}

class _SellerOrdersTabState extends State<SellerOrdersTab> {
  final _service = SellerApiService();
  String _filter = 'all';
  late Future<PaginatedSellerOrders> _future;

  static const _filters = <String, String>{
    'all': 'Tümü',
    'progress': 'Hazırlanan',
    'delivered': 'Kargoda',
    'completed': 'Tamamlanan',
    'declined': 'İptal',
  };

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<PaginatedSellerOrders> _load() =>
      _service.fetchOrders(widget.token, filter: _filter);

  Future<void> _refresh() async {
    setState(() => _future = _load());
    await _future;
  }

  void _setFilter(String filter) {
    if (_filter == filter) return;
    setState(() {
      _filter = filter;
      _future = _load();
    });
  }

  String _statusLabel(SellerOrderModel order) =>
      sellerStatusListLabel(order.sellerStatus);

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        SizedBox(
          height: 48,
          child: ListView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.fromLTRB(12, 8, 12, 4),
            children: _filters.entries.map((e) {
              final selected = _filter == e.key;
              return Padding(
                padding: const EdgeInsets.only(right: 8),
                child: ChoiceChip(
                  label: Text(e.value),
                  selected: selected,
                  onSelected: (_) => _setFilter(e.key),
                  selectedColor: HomeTheme.brandYellow,
                  labelStyle: TextStyle(
                    color: HomeTheme.textDark,
                    fontWeight: selected ? FontWeight.w800 : FontWeight.w600,
                    fontSize: 12,
                  ),
                  side: BorderSide(
                    color: selected ? HomeTheme.brandYellow : HomeTheme.border,
                  ),
                  backgroundColor: HomeTheme.header,
                ),
              );
            }).toList(),
          ),
        ),
        Expanded(
          child: FutureBuilder<PaginatedSellerOrders>(
            future: _future,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const Center(child: CircularProgressIndicator());
              }
              if (snapshot.hasError) {
                return Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Text('Siparişler yüklenemedi'),
                        const SizedBox(height: 8),
                        Text(
                          '${snapshot.error}',
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            color: HomeTheme.textMuted,
                            fontSize: 12,
                          ),
                        ),
                        const SizedBox(height: 12),
                        FilledButton(
                          onPressed: _refresh,
                          style: FilledButton.styleFrom(
                            backgroundColor: HomeTheme.brandYellow,
                            foregroundColor: HomeTheme.textDark,
                          ),
                          child: const Text('Tekrar Dene'),
                        ),
                      ],
                    ),
                  ),
                );
              }

              final page = snapshot.data!;
              if (page.items.isEmpty) {
                return RefreshIndicator(
                  onRefresh: _refresh,
                  child: ListView(
                    children: const [
                      SizedBox(height: 120),
                      Center(
                        child: Text(
                          'Bu filtrede sipariş yok',
                          style: TextStyle(color: HomeTheme.textMuted),
                        ),
                      ),
                    ],
                  ),
                );
              }

              return RefreshIndicator(
                onRefresh: _refresh,
                color: HomeTheme.brandYellow,
                child: ListView.separated(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                  itemCount: page.items.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final order = page.items[index];
                    return InkWell(
                      borderRadius: BorderRadius.circular(HomeTheme.radius),
                      onTap: () async {
                        await Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) => SellerOrderDetailScreen(
                              token: widget.token,
                              orderId: order.id,
                            ),
                          ),
                        );
                        if (mounted) _refresh();
                      },
                      child: Container(
                        decoration: HomeTheme.cardDecoration(),
                        padding: const EdgeInsets.all(14),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    '#${order.orderId}',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w800,
                                      color: HomeTheme.textDark,
                                    ),
                                  ),
                                ),
                                Text(
                                  _statusLabel(order),
                                  style: const TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w700,
                                    color: HomeTheme.textMuted,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 3,
                                  ),
                                  decoration: BoxDecoration(
                                    color: HomeTheme.bg,
                                    borderRadius: BorderRadius.circular(6),
                                    border: Border.all(color: HomeTheme.border),
                                  ),
                                  child: Text(
                                    'Hakediş: ${sellerPayoutShortLabel(order.payoutState)}',
                                    style: const TextStyle(
                                      fontSize: 11,
                                      fontWeight: FontWeight.w600,
                                      color: HomeTheme.textMuted,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            Text(
                              order.customerName.isEmpty
                                  ? 'Müşteri'
                                  : order.customerName,
                              style: const TextStyle(
                                color: HomeTheme.textDark,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                Text(
                                  Utils.formatPrice(order.totalAmount, context),
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                                const Spacer(),
                                Text(
                                  order.createdAt.length >= 10
                                      ? order.createdAt.substring(0, 10)
                                      : order.createdAt,
                                  style: const TextStyle(
                                    fontSize: 12,
                                    color: HomeTheme.textMuted,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}
