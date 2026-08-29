import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../models/seller_return_model.dart';
import '../services/seller_api_service.dart';
import 'seller_return_detail_screen.dart';

class SellerReturnsScreen extends StatefulWidget {
  const SellerReturnsScreen({super.key});

  @override
  State<SellerReturnsScreen> createState() => _SellerReturnsScreenState();
}

class _SellerReturnsScreenState extends State<SellerReturnsScreen> {
  final _service = SellerApiService();
  late Future<List<SellerReturnRequest>> _future;
  int? _statusFilter;

  static const _filters = <String, int?>{
    'Tümü': null,
    'Bekleyen': 0,
    'Onaylı': 1,
    'Red': 5,
  };

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  Future<List<SellerReturnRequest>> _load() =>
      _service.fetchReturnRequests(_token, status: _statusFilter);

  Future<void> _refresh() async {
    setState(() => _future = _load());
    await _future;
  }

  void _setFilter(int? status) {
    if (_statusFilter == status) return;
    setState(() {
      _statusFilter = status;
      _future = _load();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('İade Talepleri'),
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
              children: _filters.entries.map((e) {
                final selected = _statusFilter == e.value;
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ChoiceChip(
                    label: Text(e.key),
                    selected: selected,
                    onSelected: (_) => _setFilter(e.value),
                    selectedColor: HomeTheme.brandYellow,
                    labelStyle: TextStyle(
                      color: HomeTheme.textDark,
                      fontWeight:
                          selected ? FontWeight.w800 : FontWeight.w600,
                      fontSize: 12,
                    ),
                    side: BorderSide(
                      color:
                          selected ? HomeTheme.brandYellow : HomeTheme.border,
                    ),
                    backgroundColor: HomeTheme.header,
                  ),
                );
              }).toList(),
            ),
          ),
          Expanded(
            child: FutureBuilder<List<SellerReturnRequest>>(
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
                          Text('${snapshot.error}', textAlign: TextAlign.center),
                          const SizedBox(height: 12),
                          FilledButton(
                            onPressed: _refresh,
                            child: const Text('Tekrar Dene'),
                          ),
                        ],
                      ),
                    ),
                  );
                }
                final items = snapshot.data ?? const [];
                if (items.isEmpty) {
                  return RefreshIndicator(
                    onRefresh: _refresh,
                    child: ListView(
                      children: const [
                        SizedBox(height: 120),
                        Center(
                          child: Text(
                            'İade talebi yok',
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
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 10),
                    itemBuilder: (context, index) {
                      final item = items[index];
                      return InkWell(
                        borderRadius:
                            BorderRadius.circular(HomeTheme.radius),
                        onTap: () async {
                          await Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) =>
                                  SellerReturnDetailScreen(returnId: item.id),
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
                                      '#${item.orderCode}',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w800,
                                      ),
                                    ),
                                  ),
                                  Text(
                                    item.statusLabel,
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w700,
                                      color: HomeTheme.textMuted,
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              Text(
                                item.productName.isEmpty
                                    ? 'Ürün'
                                    : item.productName,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '${item.customerName.isEmpty ? 'Müşteri' : item.customerName} · ${item.reason}',
                                style: const TextStyle(
                                  fontSize: 12,
                                  color: HomeTheme.textMuted,
                                ),
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
      ),
    );
  }
}
