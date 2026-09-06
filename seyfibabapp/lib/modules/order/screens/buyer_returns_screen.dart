import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../model/buyer_return_model.dart';
import '../services/buyer_return_service.dart';

class BuyerReturnsScreen extends StatefulWidget {
  const BuyerReturnsScreen({super.key});

  @override
  State<BuyerReturnsScreen> createState() => _BuyerReturnsScreenState();
}

class _BuyerReturnsScreenState extends State<BuyerReturnsScreen> {
  final _service = BuyerReturnService();
  late Future<List<BuyerReturnRequest>> _future;
  int? _statusFilter;

  static const _filters = <String, int?>{
    'Tümü': null,
    'Bekleyen': 0,
    'Onaylı': 1,
    'İade edildi': 4,
    'Red': 5,
  };

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  Future<List<BuyerReturnRequest>> _load() =>
      _service.fetchReturnRequests(token: _token, status: _statusFilter);

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

  Future<void> _cancel(BuyerReturnRequest item) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Talebi iptal et'),
        content: const Text('Bu iade talebini iptal etmek istiyor musunuz?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Vazgeç'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('İptal et'),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;

    try {
      final msg = await _service.cancelReturnRequest(
        token: _token,
        id: item.id,
      );
      if (!mounted) return;
      Utils.showSnackBar(context, msg);
      await _refresh();
    } catch (e) {
      if (!mounted) return;
      Utils.errorSnackBar(context, e.toString().replaceFirst('Exception: ', ''));
    }
  }

  Future<void> _submitTracking(BuyerReturnRequest item) async {
    final carrierCtrl = TextEditingController(text: item.buyerReturnCarrier ?? '');
    final trackingCtrl =
        TextEditingController(text: item.buyerReturnTrackingNumber ?? '');
    final urlCtrl =
        TextEditingController(text: item.buyerReturnTrackingUrl ?? '');
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('İade kargo takibi'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: carrierCtrl,
                decoration: const InputDecoration(
                  labelText: 'Kargo firması',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: trackingCtrl,
                decoration: const InputDecoration(
                  labelText: 'Takip numarası *',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: urlCtrl,
                decoration: const InputDecoration(
                  labelText: 'Takip linki (opsiyonel)',
                  border: OutlineInputBorder(),
                ),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Vazgeç'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Kaydet'),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    if (trackingCtrl.text.trim().length < 3) {
      Utils.errorSnackBar(context, 'Takip numarası zorunludur');
      return;
    }
    try {
      final msg = await _service.submitReturnTracking(
        token: _token,
        id: item.id,
        trackingNumber: trackingCtrl.text.trim(),
        carrier: carrierCtrl.text.trim(),
        trackingUrl: urlCtrl.text.trim(),
      );
      if (!mounted) return;
      Utils.showSnackBar(context, msg);
      await _refresh();
    } catch (e) {
      if (!mounted) return;
      Utils.errorSnackBar(context, e.toString().replaceFirst('Exception: ', ''));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('İade Taleplerim'),
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
            child: FutureBuilder<List<BuyerReturnRequest>>(
              future: _future,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator());
                }
                if (snapshot.hasError) {
                  return Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: Text(
                        snapshot.error
                            .toString()
                            .replaceFirst('Exception: ', ''),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  );
                }
                final items = snapshot.data ?? const [];
                if (items.isEmpty) {
                  return const Center(child: Text('İade talebi yok'));
                }
                return RefreshIndicator(
                  onRefresh: _refresh,
                  child: ListView.separated(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 10),
                    itemBuilder: (context, index) {
                      final item = items[index];
                      return Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: HomeTheme.border),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    item.productName.isNotEmpty
                                        ? item.productName
                                        : 'Ürün',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w700,
                                      fontSize: 14,
                                    ),
                                  ),
                                ),
                                Text(
                                  item.statusLabel,
                                  style: const TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w700,
                                    color: HomeTheme.textMuted,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            Text(
                              'Sipariş: ${item.orderCode}  ·  Adet: ${item.qty}',
                              style: const TextStyle(
                                fontSize: 12,
                                color: HomeTheme.textMuted,
                              ),
                            ),
                            if (item.refundAmount > 0) ...[
                              const SizedBox(height: 4),
                              Text(
                                'Tahmini iade: ${Utils.formatPrice(item.refundAmount, context)}',
                                style: const TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                            if (item.reason.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                'Neden: ${item.reason}',
                                style: const TextStyle(fontSize: 12),
                              ),
                            ],
                            if (item.isRejected) ...[
                              const SizedBox(height: 8),
                              Container(
                                width: double.infinity,
                                padding: const EdgeInsets.all(10),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFF8FAFC),
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(color: HomeTheme.border),
                                ),
                                child: Text(
                                  item.rejectionNote == null
                                      ? 'İade talebi reddedildi'
                                      : 'Ret gerekçesi: ${item.rejectionNote}',
                                  style: const TextStyle(
                                    fontSize: 12,
                                    height: 1.35,
                                    fontWeight: FontWeight.w600,
                                    color: Color(0xFF334155),
                                  ),
                                ),
                              ),
                            ],
                            if ((item.returnAddress ?? '').trim().isNotEmpty ||
                                (item.returnCargoCode ?? '').trim().isNotEmpty ||
                                item.canSubmitTracking) ...[
                              const SizedBox(height: 8),
                              Align(
                                alignment: Alignment.centerLeft,
                                child: OutlinedButton(
                                  onPressed: () => _showLogistics(item),
                                  child: const Text('İade kargo bilgilerini gör'),
                                ),
                              ),
                            ],
                            if (item.isPending) ...[
                              const SizedBox(height: 10),
                              Align(
                                alignment: Alignment.centerRight,
                                child: TextButton(
                                  onPressed: () => _cancel(item),
                                  child: const Text('İptal et'),
                                ),
                              ),
                            ],
                            if (item.canSubmitTracking) ...[
                              const SizedBox(height: 10),
                              Align(
                                alignment: Alignment.centerRight,
                                child: FilledButton(
                                  onPressed: () => _submitTracking(item),
                                  child: Text(
                                    (item.buyerReturnTrackingNumber ?? '')
                                            .trim()
                                            .isEmpty
                                        ? 'Takip no gir'
                                        : 'Takip bilgisini güncelle',
                                  ),
                                ),
                              ),
                            ],
                          ],
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

  Future<void> _showLogistics(BuyerReturnRequest item) async {
    final lines = <String>[
      if ((item.returnAddress ?? '').trim().isNotEmpty)
        'İade adresi:\n${item.returnAddress!.trim()}',
      'Kargo ücreti: ${item.shippingPayerLabel}',
      if ((item.returnCarrierName ?? '').trim().isNotEmpty)
        'Kargo: ${item.returnCarrierName}',
      if ((item.returnCargoCode ?? '').trim().isNotEmpty)
        'İade kodu: ${item.returnCargoCode}',
      if ((item.returnShippingInstructions ?? '').trim().isNotEmpty)
        'Talimat:\n${item.returnShippingInstructions!.trim()}',
      if ((item.buyerReturnTrackingNumber ?? '').trim().isNotEmpty)
        'Takip no: ${item.buyerReturnTrackingNumber}'
      else if (item.canSubmitTracking)
        'Kargoya verdikten sonra buradan takip numarası girebilirsiniz.',
      if ((item.refundInfo ?? '').trim().isNotEmpty)
        'Para iadesi:\n${item.refundInfo!.trim()}',
    ];

    if (!mounted) return;
    await showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('İade kargo bilgileri'),
        content: SingleChildScrollView(
          child: Text(
            lines.join('\n\n'),
            style: const TextStyle(fontSize: 13, height: 1.4),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Kapat'),
          ),
        ],
      ),
    );
  }
}
