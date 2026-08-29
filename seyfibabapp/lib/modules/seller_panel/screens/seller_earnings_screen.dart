import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../models/seller_earnings_model.dart';
import '../services/seller_api_service.dart';

class SellerEarningsScreen extends StatefulWidget {
  const SellerEarningsScreen({super.key});

  @override
  State<SellerEarningsScreen> createState() => _SellerEarningsScreenState();
}

class _SellerEarningsScreenState extends State<SellerEarningsScreen> {
  final _service = SellerApiService();
  bool _loading = true;
  String? _error;
  SellerEarningsSummary? _summary;
  List<SellerWithdrawItem> _withdraws = const [];
  List<SellerEarningOrderItem> _orders = const [];

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
      final results = await Future.wait([
        _service.fetchEarningsSummary(_token),
        _service.fetchWithdrawBundle(_token),
        _service.fetchEarningOrders(_token),
      ]);
      if (!mounted) return;
      final summary = results[0] as SellerEarningsSummary;
      final withdraws = results[1] as SellerWithdrawBundle;
      final orders = results[2] as List<SellerEarningOrderItem>;
      setState(() {
        _summary = summary;
        _withdraws = withdraws.withdraws;
        _orders = orders;
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

  Future<void> _openWithdrawSheet() async {
    final summary = _summary;
    if (summary == null) return;
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: HomeTheme.header,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (context) {
        return _WithdrawRequestSheet(
          token: _token,
          service: _service,
          withdrawable: summary.withdrawableBalance,
          onDone: () {
            Navigator.pop(context);
            _load();
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('Kazanç / Çekim'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
      ),
      floatingActionButton: _summary == null || !_summary!.withdrawRequestAllowed
          ? null
          : FloatingActionButton.extended(
              onPressed: _openWithdrawSheet,
              backgroundColor: HomeTheme.brandYellow,
              foregroundColor: HomeTheme.textDark,
              icon: const Icon(Icons.account_balance_wallet_outlined),
              label: const Text('Çekim Talebi'),
            ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(_error!, textAlign: TextAlign.center),
                        const SizedBox(height: 12),
                        FilledButton(
                          onPressed: _load,
                          child: const Text('Tekrar Dene'),
                        ),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  color: HomeTheme.brandYellow,
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
                    children: [
                      _SummaryCards(summary: _summary!),
                      if (!_summary!.withdrawRequestAllowed) ...[
                        const SizedBox(height: 12),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(14),
                          decoration: HomeTheme.cardDecoration(
                            color: HomeTheme.brandYellow.withValues(alpha: 0.2),
                          ),
                          child: Text(
                            _summary!.channelNote.isNotEmpty
                                ? _summary!.channelNote
                                : 'Kredi kartı ödemeleri İyzico üzerinden otomatik aktarılır. Çekim talebi yalnızca havale siparişleri için kullanılır.',
                            style: const TextStyle(
                              fontSize: 13,
                              height: 1.4,
                              color: HomeTheme.textDark,
                            ),
                          ),
                        ),
                      ],
                      const SizedBox(height: 20),
                      const Text(
                        'Çekim Geçmişi',
                        style: TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 16,
                        ),
                      ),
                      const SizedBox(height: 8),
                      if (_withdraws.isEmpty)
                        const Text(
                          'Henüz çekim talebi yok',
                          style: TextStyle(color: HomeTheme.textMuted),
                        )
                      else
                        ..._withdraws.map(
                          (w) => Container(
                            margin: const EdgeInsets.only(bottom: 8),
                            padding: const EdgeInsets.all(12),
                            decoration: HomeTheme.cardDecoration(),
                            child: Row(
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        w.method.isEmpty
                                            ? 'Çekim #${w.id}'
                                            : w.method,
                                        style: const TextStyle(
                                          fontWeight: FontWeight.w700,
                                        ),
                                      ),
                                      Text(
                                        w.createdAt.length >= 10
                                            ? w.createdAt.substring(0, 10)
                                            : w.createdAt,
                                        style: const TextStyle(
                                          fontSize: 12,
                                          color: HomeTheme.textMuted,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    Text(
                                      Utils.formatPrice(
                                        w.totalAmount,
                                        context,
                                      ),
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w800,
                                      ),
                                    ),
                                    Text(
                                      w.statusLabel,
                                      style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w700,
                                        color: w.status == 1
                                            ? const Color(0xFF34A853)
                                            : HomeTheme.textMuted,
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ),
                      const SizedBox(height: 20),
                      const Text(
                        'Sipariş Kazançları',
                        style: TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 16,
                        ),
                      ),
                      const SizedBox(height: 8),
                      if (_orders.isEmpty)
                        const Text(
                          'Kayıt yok',
                          style: TextStyle(color: HomeTheme.textMuted),
                        )
                      else
                        ..._orders.map(
                          (o) => Container(
                            margin: const EdgeInsets.only(bottom: 8),
                            padding: const EdgeInsets.all(12),
                            decoration: HomeTheme.cardDecoration(),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  '#${o.orderId} · ${o.productName}',
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'Adet ${o.qty} · Brüt ${Utils.formatPrice(o.grossAmount, context)} · Komisyon ${Utils.formatPrice(o.commissionAmount, context)}',
                                  style: const TextStyle(
                                    fontSize: 12,
                                    color: HomeTheme.textMuted,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  'Net: ${Utils.formatPrice(o.sellerNetAmount, context)}',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
    );
  }
}

class _SummaryCards extends StatelessWidget {
  const _SummaryCards({required this.summary});
  final SellerEarningsSummary summary;

  @override
  Widget build(BuildContext context) {
    final items = [
      ('Platformdaki toplam', summary.totalInPlatform),
      ('İyzico havuzu', summary.iyzicoPoolBalance),
      ('Çekilebilir (havale)', summary.withdrawableBalance),
      ('Havale beklemede', summary.bankPendingHoldBalance),
      ('Net kazanç', summary.totalNet),
      ('Brüt', summary.totalGross),
      ('Komisyon', summary.totalCommission),
      ('Çekilen', summary.totalWithdrawn),
      ('Bekleyen çekim', summary.pendingWithdrawals),
    ];
    return LayoutBuilder(
      builder: (context, constraints) {
        final w = (constraints.maxWidth - 12) / 2;
        return Wrap(
          spacing: 12,
          runSpacing: 12,
          children: [
            ...items.map(
              (e) => SizedBox(
                width: w,
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: HomeTheme.cardDecoration(),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        e.$1,
                        style: const TextStyle(
                          color: HomeTheme.textMuted,
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        Utils.formatPrice(e.$2, context),
                        style: const TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 16,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            SizedBox(
              width: constraints.maxWidth,
              child: Container(
                padding: const EdgeInsets.all(14),
                decoration: HomeTheme.cardDecoration(
                  color: HomeTheme.brandYellow.withValues(alpha: 0.25),
                ),
                child: Text(
                  'Komisyon oranı: %${summary.commissionRate.toStringAsFixed(2)}'
                  '${summary.payoutHoldDays > 0 ? ' · Havale bekleme: ${summary.payoutHoldDays} gün' : ''}',
                  style: const TextStyle(fontWeight: FontWeight.w700),
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}

class _WithdrawRequestSheet extends StatefulWidget {
  const _WithdrawRequestSheet({
    required this.token,
    required this.service,
    required this.withdrawable,
    required this.onDone,
  });

  final String token;
  final SellerApiService service;
  final double withdrawable;
  final VoidCallback onDone;

  @override
  State<_WithdrawRequestSheet> createState() => _WithdrawRequestSheetState();
}

class _WithdrawRequestSheetState extends State<_WithdrawRequestSheet> {
  final _amountCtrl = TextEditingController();
  final _accountCtrl = TextEditingController();
  List<SellerWithdrawMethod> _methods = const [];
  int? _methodId;
  bool _loading = true;
  bool _submitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadMethods();
  }

  @override
  void dispose() {
    _amountCtrl.dispose();
    _accountCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadMethods() async {
    try {
      final methods = await widget.service.fetchWithdrawMethods(widget.token);
      if (!mounted) return;
      setState(() {
        _methods = methods;
        _methodId = methods.isNotEmpty ? methods.first.id : null;
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

  Future<void> _submit() async {
    final amount =
        double.tryParse(_amountCtrl.text.trim().replaceAll(',', '.'));
    if (_methodId == null) {
      Utils.errorSnackBar(context, 'Ödeme yöntemi seçin');
      return;
    }
    if (amount == null || amount <= 0) {
      Utils.errorSnackBar(context, 'Geçerli tutar girin');
      return;
    }
    if (_accountCtrl.text.trim().isEmpty) {
      Utils.errorSnackBar(context, 'Hesap bilgisi girin');
      return;
    }
    setState(() => _submitting = true);
    try {
      final msg = await widget.service.createWithdrawRequest(
        token: widget.token,
        methodId: _methodId!,
        amount: amount,
        accountInfo: _accountCtrl.text.trim(),
      );
      if (!mounted) return;
      Utils.showSnackBar(context, msg);
      widget.onDone();
    } catch (e) {
      if (!mounted) return;
      Utils.errorSnackBar(context, '$e');
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + bottom),
      child: _loading
          ? const SizedBox(
              height: 180,
              child: Center(child: CircularProgressIndicator()),
            )
          : SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text(
                    'Yeni Çekim Talebi',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Çekilebilir: ${Utils.formatPrice(widget.withdrawable, context)}',
                    style: const TextStyle(color: HomeTheme.textMuted),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 8),
                    Text(_error!, style: const TextStyle(color: Colors.red)),
                  ],
                  const SizedBox(height: 12),
                  DropdownButtonFormField<int?>(
                    value: _methodId,
                    isExpanded: true,
                    decoration: const InputDecoration(
                      labelText: 'Ödeme yöntemi',
                      border: OutlineInputBorder(),
                    ),
                    items: _methods
                        .map(
                          (m) => DropdownMenuItem<int?>(
                            value: m.id,
                            child: Text(
                              '${m.name} (min ${m.minAmount.toStringAsFixed(0)} / max ${m.maxAmount.toStringAsFixed(0)})',
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        )
                        .toList(),
                    onChanged: (v) => setState(() => _methodId = v),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _amountCtrl,
                    keyboardType:
                        const TextInputType.numberWithOptions(decimal: true),
                    decoration: const InputDecoration(
                      labelText: 'Tutar',
                      border: OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _accountCtrl,
                    maxLines: 3,
                    decoration: const InputDecoration(
                      labelText: 'Hesap bilgisi / IBAN notu',
                      border: OutlineInputBorder(),
                      alignLabelWithHint: true,
                    ),
                  ),
                  const SizedBox(height: 16),
                  FilledButton(
                    onPressed: _submitting ? null : _submit,
                    style: FilledButton.styleFrom(
                      backgroundColor: HomeTheme.brandYellow,
                      foregroundColor: HomeTheme.textDark,
                      minimumSize: const Size.fromHeight(48),
                    ),
                    child: const Text(
                      'Talep Gönder',
                      style: TextStyle(fontWeight: FontWeight.w800),
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}
