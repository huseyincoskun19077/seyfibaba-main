import 'package:flutter/material.dart';

import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../models/seller_order_model.dart';
import '../services/seller_api_service.dart';

class SellerOrderDetailScreen extends StatefulWidget {
  const SellerOrderDetailScreen({
    super.key,
    required this.token,
    required this.orderId,
  });

  final String token;
  final int orderId;

  @override
  State<SellerOrderDetailScreen> createState() =>
      _SellerOrderDetailScreenState();
}

class _SellerOrderDetailScreenState extends State<SellerOrderDetailScreen> {
  final _service = SellerApiService();
  late Future<SellerOrderDetail> _future;

  @override
  void initState() {
    super.initState();
    _future = _service.fetchOrderDetail(widget.token, widget.orderId);
  }

  Future<void> _refresh() async {
    setState(() {
      _future = _service.fetchOrderDetail(widget.token, widget.orderId);
    });
    await _future;
  }

  Future<void> _updateStatus(
    int nextStatus, {
    String? carrierName,
    String? trackingNumber,
    String? trackingUrl,
  }) async {
    try {
      Utils.loadingDialog(context);
      await _service.updateOrderStatus(
        widget.token,
        widget.orderId,
        orderStatus: nextStatus,
        carrierName: carrierName,
        trackingNumber: trackingNumber,
        trackingUrl: trackingUrl,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, 'Sipariş durumu güncellendi');
      await _refresh();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  Future<void> _shipOrder() async {
    final payload = await showModalBottomSheet<Map<String, String>>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (sheetContext) => const _ManualShipFormSheet(),
    );

    if (payload == null || !mounted) return;

    final carrier = payload['carrier'] ?? '';
    final tracking = payload['tracking'] ?? '';
    final trackingUrl = payload['tracking_url'] ?? '';

    if (carrier.isEmpty || tracking.isEmpty) return;

    try {
      Utils.loadingDialog(context);
      await _service.manualShipOrder(
        widget.token,
        widget.orderId,
        carrierName: carrier,
        trackingNumber: tracking,
        trackingUrl: trackingUrl.isEmpty ? null : trackingUrl,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, 'Sipariş kargoya verildi');
      await _refresh();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      final message = '$e';
      if (message.contains('404') || message.contains('could not be found')) {
        Utils.errorSnackBar(
          context,
          'Sunucu henüz güncellenmemiş. Lütfen backend deploy edin.',
        );
        return;
      }
      Utils.errorSnackBar(context, message);
    }
  }

  int _sellerStatus(Map<String, dynamic> raw) {
    final order = raw['order'];
    if (order is! Map) return -1;
    final products = order['order_products'] ?? order['orderProducts'];
    if (products is! List || products.isEmpty) return -1;
    final first = products.first;
    if (first is! Map) return -1;
    return int.tryParse('${first['seller_status'] ?? -1}') ?? -1;
  }

  List<Map<String, dynamic>> _products(Map<String, dynamic> raw) {
    final order = raw['order'];
    if (order is! Map) return const [];
    final products = order['order_products'] ?? order['orderProducts'];
    if (products is! List) return const [];
    return products
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('Sipariş Detayı'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
      ),
      body: FutureBuilder<SellerOrderDetail>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text('${snapshot.error}', textAlign: TextAlign.center),
              ),
            );
          }
          final detail = snapshot.data!;
          final sellerStatus = _sellerStatus(detail.raw);
          final products = _products(detail.raw);

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Container(
                  decoration: HomeTheme.cardDecoration(),
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '#${detail.order.orderId}',
                        style: const TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 18,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        detail.order.customerName.isEmpty
                            ? 'Müşteri'
                            : detail.order.customerName,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        Utils.formatPrice(detail.order.totalAmount, context),
                        style: const TextStyle(fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Satıcı durumu: $sellerStatus',
                        style: const TextStyle(
                          color: HomeTheme.textMuted,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                if (sellerStatus == 0 || sellerStatus == 1)
                  Row(
                    children: [
                      if (sellerStatus == 0)
                        Expanded(
                          child: FilledButton(
                            onPressed: () => _updateStatus(1),
                            style: FilledButton.styleFrom(
                              backgroundColor: HomeTheme.brandYellow,
                              foregroundColor: HomeTheme.textDark,
                            ),
                            child: const Text('Onayla / Hazırla'),
                          ),
                        ),
                      if (sellerStatus == 1)
                        Expanded(
                          child: FilledButton(
                            onPressed: _shipOrder,
                            style: FilledButton.styleFrom(
                              backgroundColor: HomeTheme.brandYellow,
                              foregroundColor: HomeTheme.textDark,
                            ),
                            child: const Text('Kargoya Ver'),
                          ),
                        ),
                    ],
                  ),
                const SizedBox(height: 16),
                const Text(
                  'Ürünler',
                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
                ),
                const SizedBox(height: 8),
                ...products.map((p) {
                  final name = '${p['product_name'] ?? p['name'] ?? 'Ürün'}';
                  final qty = '${p['qty'] ?? 1}';
                  final price = double.tryParse(
                        '${p['unit_price'] ?? p['price'] ?? 0}',
                      ) ??
                      0;
                  return Container(
                    margin: const EdgeInsets.only(bottom: 8),
                    decoration: HomeTheme.cardDecoration(),
                    padding: const EdgeInsets.all(12),
                    child: Row(
                      children: [
                        Expanded(
                          child: Text(
                            name,
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                        ),
                        Text('x$qty'),
                        const SizedBox(width: 10),
                        Text(Utils.formatPrice(price, context)),
                      ],
                    ),
                  );
                }),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _ManualShipFormSheet extends StatefulWidget {
  const _ManualShipFormSheet();

  @override
  State<_ManualShipFormSheet> createState() => _ManualShipFormSheetState();
}

class _ManualShipFormSheetState extends State<_ManualShipFormSheet> {
  final _carrierController = TextEditingController();
  final _trackingController = TextEditingController();
  final _trackingUrlController = TextEditingController();

  @override
  void dispose() {
    _carrierController.dispose();
    _trackingController.dispose();
    _trackingUrlController.dispose();
    super.dispose();
  }

  void _submit() {
    final carrier = _carrierController.text.trim();
    final tracking = _trackingController.text.trim();
    if (carrier.isEmpty || tracking.isEmpty) {
      Utils.errorSnackBar(
        context,
        'Kargo firması ve takip numarası zorunludur.',
      );
      return;
    }

    Navigator.pop(context, {
      'carrier': carrier,
      'tracking': tracking,
      'tracking_url': _trackingUrlController.text.trim(),
    });
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.fromLTRB(
        16,
        16,
        16,
        16 + MediaQuery.viewInsetsOf(context).bottom,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'Manuel Kargo Bilgisi',
              style: TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 18,
                color: HomeTheme.textDark,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Kargo firması ve takip numarasını girin. Takip numarası için özel bir format yok; kargo firmasından aldığınız numarayı olduğu gibi yazın.',
              style: TextStyle(color: HomeTheme.textMuted, fontSize: 13),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _carrierController,
              textInputAction: TextInputAction.next,
              textCapitalization: TextCapitalization.words,
              decoration: const InputDecoration(
                labelText: 'Kargo firması *',
                hintText: 'Örn: Yurtiçi, Aras, MNG',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _trackingController,
              textInputAction: TextInputAction.next,
              decoration: const InputDecoration(
                labelText: 'Takip numarası *',
                hintText: 'Örn: 1234567890',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _trackingUrlController,
              keyboardType: TextInputType.url,
              decoration: const InputDecoration(
                labelText: 'Takip linki (isteğe bağlı)',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _submit,
              style: FilledButton.styleFrom(
                backgroundColor: HomeTheme.brandYellow,
                foregroundColor: HomeTheme.textDark,
                padding: const EdgeInsets.symmetric(vertical: 14),
              ),
              child: const Text('Kargoya Ver'),
            ),
          ],
        ),
      ),
    );
  }
}
