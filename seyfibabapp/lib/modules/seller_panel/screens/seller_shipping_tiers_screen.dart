import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../../../widgets/rounded_app_bar.dart';
import '../services/seller_api_service.dart';

class SellerShippingTiersScreen extends StatefulWidget {
  const SellerShippingTiersScreen({super.key});

  @override
  State<SellerShippingTiersScreen> createState() =>
      _SellerShippingTiersScreenState();
}

class _SellerShippingTiersScreenState extends State<SellerShippingTiersScreen> {
  final _service = SellerApiService();
  final List<_TierRow> _rows = [];
  bool _loading = true;
  bool _saving = false;
  String? _error;

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    for (final row in _rows) {
      row.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await _service.fetchShippingTiers(_token);
      final tiers = data['tiers'];
      final defaults = data['defaults'];
      for (final row in _rows) {
        row.dispose();
      }
      _rows.clear();
      if (tiers is List && tiers.isNotEmpty) {
        for (final t in tiers.whereType<Map>()) {
          _rows.add(_TierRow.fromMap(Map<String, dynamic>.from(t)));
        }
      } else if (defaults is List && defaults.isNotEmpty) {
        for (final t in defaults.whereType<Map>()) {
          _rows.add(_TierRow.fromMap(Map<String, dynamic>.from(t)));
        }
      } else {
        _rows.add(_TierRow());
      }
      if (!mounted) return;
      setState(() => _loading = false);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = '$e';
      });
    }
  }

  Future<void> _save() async {
    if (_rows.isEmpty) {
      Utils.errorSnackBar(context, 'En az bir kademe ekleyin.');
      return;
    }
    setState(() => _saving = true);
    Utils.loadingDialog(context);
    try {
      final payload = _rows.map((r) => r.toPayload()).toList();
      final res = await _service.saveShippingTiers(
        token: _token,
        tiers: payload,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(
        context,
        '${res['message'] ?? 'Kargo kademeleri kaydedildi.'}',
      );
      await _load();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, '$e');
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: RoundedAppBar(titleText: 'Kargo Ücretleri'),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _saving
            ? null
            : () => setState(() => _rows.add(_TierRow())),
        icon: const Icon(Icons.add),
        label: const Text('Kademe'),
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
                        ElevatedButton(
                          onPressed: _load,
                          child: const Text('Tekrar dene'),
                        ),
                      ],
                    ),
                  ),
                )
              : ListView(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFE8F4FC),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Text(
                        'Müşteri sepetinde sizin ürünlerinizin alt toplamına göre kargo hesaplanır. Üst tutarı boş bırakırsanız kademe sınırsızdır.',
                        style: TextStyle(fontSize: 13, height: 1.4),
                      ),
                    ),
                    const SizedBox(height: 16),
                    ...List.generate(_rows.length, (i) {
                      final row = _rows[i];
                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(HomeTheme.radius),
                          side: BorderSide(
                            color: HomeTheme.border.withValues(alpha: 0.6),
                          ),
                        ),
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Column(
                            children: [
                              Row(
                                children: [
                                  Text(
                                    'Kademe ${i + 1}',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                  const Spacer(),
                                  if (_rows.length > 1)
                                    IconButton(
                                      onPressed: () {
                                        setState(() {
                                          _rows.removeAt(i).dispose();
                                        });
                                      },
                                      icon: const Icon(
                                        Icons.delete_outline,
                                        color: Colors.red,
                                      ),
                                    ),
                                ],
                              ),
                              TextField(
                                controller: row.minCtrl,
                                keyboardType:
                                    const TextInputType.numberWithOptions(
                                  decimal: true,
                                ),
                                decoration: const InputDecoration(
                                  labelText: 'Alt tutar (₺)',
                                ),
                              ),
                              const SizedBox(height: 8),
                              TextField(
                                controller: row.maxCtrl,
                                keyboardType:
                                    const TextInputType.numberWithOptions(
                                  decimal: true,
                                ),
                                decoration: const InputDecoration(
                                  labelText: 'Üst tutar (₺, boş = sınırsız)',
                                ),
                              ),
                              const SizedBox(height: 8),
                              TextField(
                                controller: row.feeCtrl,
                                keyboardType:
                                    const TextInputType.numberWithOptions(
                                  decimal: true,
                                ),
                                decoration: const InputDecoration(
                                  labelText: 'Kargo ücreti (₺)',
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }),
                    const SizedBox(height: 8),
                    ElevatedButton(
                      onPressed: _saving ? null : _save,
                      child: const Text('Kaydet'),
                    ),
                  ],
                ),
    );
  }
}

class _TierRow {
  _TierRow({String min = '0', String max = '', String fee = '0'})
      : minCtrl = TextEditingController(text: min),
        maxCtrl = TextEditingController(text: max),
        feeCtrl = TextEditingController(text: fee);

  factory _TierRow.fromMap(Map<String, dynamic> map) {
    final max = map['max_amount'];
    return _TierRow(
      min: '${map['min_amount'] ?? 0}',
      max: max == null ? '' : '$max',
      fee: '${map['shipping_fee'] ?? 0}',
    );
  }

  final TextEditingController minCtrl;
  final TextEditingController maxCtrl;
  final TextEditingController feeCtrl;

  void dispose() {
    minCtrl.dispose();
    maxCtrl.dispose();
    feeCtrl.dispose();
  }

  Map<String, dynamic> toPayload() {
    final maxRaw = maxCtrl.text.trim();
    return {
      'min_amount': double.tryParse(minCtrl.text.replaceAll(',', '.')) ?? 0,
      'max_amount': maxRaw.isEmpty
          ? null
          : double.tryParse(maxRaw.replaceAll(',', '.')),
      'shipping_fee':
          double.tryParse(feeCtrl.text.replaceAll(',', '.')) ?? 0,
    };
  }
}
