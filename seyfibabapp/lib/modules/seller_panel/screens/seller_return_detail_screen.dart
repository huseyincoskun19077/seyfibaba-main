import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../models/seller_return_model.dart';
import '../services/seller_api_service.dart';

class SellerReturnDetailScreen extends StatefulWidget {
  const SellerReturnDetailScreen({super.key, required this.returnId});

  final int returnId;

  @override
  State<SellerReturnDetailScreen> createState() =>
      _SellerReturnDetailScreenState();
}

class _SellerReturnDetailScreenState extends State<SellerReturnDetailScreen> {
  final _service = SellerApiService();
  late Future<SellerReturnRequest> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  Future<SellerReturnRequest> _load() =>
      _service.fetchReturnRequest(_token, widget.returnId);

  Future<void> _refresh() async {
    setState(() => _future = _load());
    await _future;
  }

  Future<void> _approve(SellerReturnRequest item) async {
    final noteCtrl = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('İadeyi onayla'),
        content: TextField(
          controller: noteCtrl,
          maxLines: 3,
          decoration: const InputDecoration(
            labelText: 'Not (opsiyonel)',
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
            child: const Text('Onayla'),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    try {
      Utils.loadingDialog(context);
      final msg = await _service.approveReturnRequest(
        _token,
        item.id,
        sellerNote: noteCtrl.text,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, msg);
      await _refresh();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    }
  }

  Future<void> _reject(SellerReturnRequest item) async {
    final reasonCtrl = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('İadeyi reddet'),
        content: TextField(
          controller: reasonCtrl,
          maxLines: 3,
          decoration: const InputDecoration(
            labelText: 'Red nedeni *',
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
            child: const Text('Reddet'),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    if (reasonCtrl.text.trim().isEmpty) {
      Utils.errorSnackBar(context, 'Red nedeni zorunlu');
      return;
    }
    try {
      Utils.loadingDialog(context);
      final msg = await _service.rejectReturnRequest(
        _token,
        item.id,
        reason: reasonCtrl.text,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(context, msg);
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
        title: const Text('İade Detayı'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
      ),
      body: FutureBuilder<SellerReturnRequest>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('${snapshot.error}'));
          }
          final item = snapshot.data!;
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
                        '#${item.orderCode}',
                        style: const TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 18,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(item.statusLabel),
                      const SizedBox(height: 8),
                      Text(
                        item.productName.isEmpty ? 'Ürün' : item.productName,
                        style: const TextStyle(fontWeight: FontWeight.w700),
                      ),
                      Text('Adet: ${item.qty}'),
                      Text(
                        'Müşteri: ${item.customerName.isEmpty ? '-' : item.customerName}',
                      ),
                      if (item.refundAmount > 0)
                        Text(
                          'İade tutarı: ${Utils.formatPrice(item.refundAmount, context)}',
                          style: const TextStyle(fontWeight: FontWeight.w800),
                        ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                Container(
                  decoration: HomeTheme.cardDecoration(),
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Sebep',
                        style: TextStyle(fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(height: 6),
                      Text(item.reason.isEmpty ? '-' : item.reason),
                      if (item.details.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        const Text(
                          'Detay',
                          style: TextStyle(fontWeight: FontWeight.w800),
                        ),
                        const SizedBox(height: 6),
                        Text(item.details),
                      ],
                      if (item.sellerNote.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        const Text(
                          'Satıcı notu',
                          style: TextStyle(fontWeight: FontWeight.w800),
                        ),
                        const SizedBox(height: 6),
                        Text(item.sellerNote),
                      ],
                      if (item.rejectedReason.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        const Text(
                          'Red nedeni',
                          style: TextStyle(fontWeight: FontWeight.w800),
                        ),
                        const SizedBox(height: 6),
                        Text(item.rejectedReason),
                      ],
                    ],
                  ),
                ),
                if (item.isPending) ...[
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () => _reject(item),
                          child: const Text('Reddet'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: FilledButton(
                          onPressed: () => _approve(item),
                          style: FilledButton.styleFrom(
                            backgroundColor: HomeTheme.brandYellow,
                            foregroundColor: HomeTheme.textDark,
                          ),
                          child: const Text('Onayla'),
                        ),
                      ),
                    ],
                  ),
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}
