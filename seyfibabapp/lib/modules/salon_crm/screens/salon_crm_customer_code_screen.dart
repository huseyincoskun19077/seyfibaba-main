import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../utils/utils.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmCustomerCodeScreen extends StatefulWidget {
  const SalonCrmCustomerCodeScreen({super.key});

  @override
  State<SalonCrmCustomerCodeScreen> createState() =>
      _SalonCrmCustomerCodeScreenState();
}

class _SalonCrmCustomerCodeScreenState
    extends State<SalonCrmCustomerCodeScreen> {
  final _service = SalonCrmService();
  bool _loading = true;
  String? _error;
  String? _joinCode;
  String? _salonName;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final token = (await SalonCrmSession.token()) ?? '';
      if (token.isEmpty) throw Exception('CRM girişi gerekli');
      final profile = await _service.fetchSalonProfile(token);
      if (!mounted) return;
      setState(() {
        _joinCode = profile.joinCode;
        _salonName = profile.name;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final code = _joinCode;

    return CrmScaffold(
      title: 'Müşteri kodu',
      actions: [
        IconButton(
          onPressed: _loading ? null : _load,
          icon: const Icon(Icons.refresh_rounded, size: 22),
          color: SalonCrmTheme.ink,
        ),
      ],
      body: _loading
          ? const Center(
              child: CircularProgressIndicator(color: SalonCrmTheme.accent),
            )
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(_error!, textAlign: TextAlign.center),
                        const SizedBox(height: 12),
                        TextButton(onPressed: _load, child: const Text('Tekrar dene')),
                      ],
                    ),
                  ),
                )
              : ListView(
                  padding: const EdgeInsets.fromLTRB(20, 8, 20, 40),
                  children: [
                    if ((_salonName ?? '').isNotEmpty)
                      Text(
                        _salonName!,
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w800,
                          color: SalonCrmTheme.ink,
                        ),
                      ),
                    const SizedBox(height: 8),
                    Text(
                      'Müşteriler yalnızca bu kod veya QR ile salonunuza bağlanır. Diğer berberler görünmez.',
                      style: SalonCrmTheme.body,
                    ),
                    const SizedBox(height: 20),
                    if (code == null || code.isEmpty)
                      CrmSoftCard(
                        child: Text(
                          'Bağlantı kodu henüz oluşmamış. Profili kaydedip tekrar deneyin.',
                          style: SalonCrmTheme.caption,
                        ),
                      )
                    else
                      CrmSoftCard(
                        child: Column(
                          children: [
                            ClipRRect(
                              borderRadius: BorderRadius.circular(12),
                              child: Image.network(
                                'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=SEYCRM:$code',
                                width: 220,
                                height: 220,
                                fit: BoxFit.cover,
                                errorBuilder: (_, __, ___) => Container(
                                  width: 220,
                                  height: 220,
                                  color: SalonCrmTheme.line,
                                  alignment: Alignment.center,
                                  child: Text(
                                    code,
                                    style: const TextStyle(
                                      fontSize: 22,
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(height: 16),
                            SelectableText(
                              code,
                              style: const TextStyle(
                                fontSize: 30,
                                fontWeight: FontWeight.w800,
                                letterSpacing: 4,
                                color: SalonCrmTheme.ink,
                              ),
                            ),
                            const SizedBox(height: 12),
                            CrmPrimaryButton(
                              label: 'Kodu kopyala',
                              icon: Icons.copy_rounded,
                              onPressed: () {
                                Clipboard.setData(ClipboardData(text: code));
                                Utils.showSnackBar(context, 'Kod kopyalandı');
                              },
                            ),
                          ],
                        ),
                      ),
                  ],
                ),
    );
  }
}
