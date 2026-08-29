import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:share_plus/share_plus.dart';

import '../../../utils/utils.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmCalendarShareScreen extends StatefulWidget {
  const SalonCrmCalendarShareScreen({super.key});

  @override
  State<SalonCrmCalendarShareScreen> createState() =>
      _SalonCrmCalendarShareScreenState();
}

class _SalonCrmCalendarShareScreenState
    extends State<SalonCrmCalendarShareScreen> {
  final _service = SalonCrmService();
  bool _loading = true;
  bool _saving = false;
  String? _error;
  SalonCrmCalendarShare? _share;

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
      final share = await _service.fetchCalendarShare(token);
      if (!mounted) return;
      setState(() {
        _share = share;
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

  Future<void> _setHorizon(String horizon) async {
    final token = (await SalonCrmSession.token()) ?? '';
    if (token.isEmpty) return;
    setState(() => _saving = true);
    try {
      final share = await _service.updateCalendarShare(
        token: token,
        horizon: horizon,
      );
      if (!mounted) return;
      setState(() {
        _share = share;
        _saving = false;
      });
      Utils.showSnackBar(context, 'Müşteri bu aralığı görecek');
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  String get _shareText {
    final s = _share!;
    return '${s.salonName} — ${s.personName} takvimi\n'
        'Dolu ve boş saatleri canlı görün. Kim randevu aldı görünmez.\n'
        'Randevu almak için Seyfibaba uygulamasını indirin.\n'
        '${s.url}';
  }

  @override
  Widget build(BuildContext context) {
    final share = _share;
    return CrmScaffold(
      title: 'Takvim paylaş',
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
                        TextButton(
                          onPressed: _load,
                          child: const Text('Tekrar dene'),
                        ),
                      ],
                    ),
                  ),
                )
              : ListView(
                  padding: const EdgeInsets.fromLTRB(20, 8, 20, 40),
                  children: [
                    Text(
                      share?.salonName ?? '',
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        color: SalonCrmTheme.ink,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Müşteri uygulamayı indirmeden seyfibaba.com üzerinden dolu saatleri takip eder. Geçmiş günler görünmez. İsim veya kim olduğu yazılmaz.',
                      style: SalonCrmTheme.body,
                    ),
                    const SizedBox(height: 18),
                    Text('Müşteri ne kadar görsün?', style: SalonCrmTheme.caption),
                    const SizedBox(height: 10),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        _horizonChip('today_tomorrow', 'Bugün ve yarın'),
                        _horizonChip('week', '1 hafta'),
                        _horizonChip('month', '1 ay'),
                      ],
                    ),
                    const SizedBox(height: 20),
                    CrmSoftCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          SelectableText(
                            share?.url ?? '',
                            style: const TextStyle(
                              fontWeight: FontWeight.w700,
                              color: SalonCrmTheme.ink,
                            ),
                          ),
                          const SizedBox(height: 14),
                          CrmPrimaryButton(
                            label: 'Linki kopyala',
                            icon: Icons.copy_rounded,
                            onPressed: share == null
                                ? null
                                : () {
                                    Clipboard.setData(
                                      ClipboardData(text: share.url),
                                    );
                                    Utils.showSnackBar(context, 'Link kopyalandı');
                                  },
                          ),
                          const SizedBox(height: 10),
                          CrmPrimaryButton(
                            label: 'Paylaş',
                            icon: Icons.ios_share_rounded,
                            onPressed: share == null
                                ? null
                                : () => Share.share(_shareText),
                          ),
                        ],
                      ),
                    ),
                    if (_saving)
                      const Padding(
                        padding: EdgeInsets.only(top: 16),
                        child: Center(
                          child: CircularProgressIndicator(
                            color: SalonCrmTheme.accent,
                          ),
                        ),
                      ),
                  ],
                ),
    );
  }

  Widget _horizonChip(String value, String label) {
    final selected = _share?.horizon == value;
    return ChoiceChip(
      label: Text(label),
      selected: selected,
      onSelected: _saving ? null : (_) => _setHorizon(value),
      selectedColor: SalonCrmTheme.accent,
      labelStyle: TextStyle(
        fontWeight: FontWeight.w700,
        color: SalonCrmTheme.ink,
      ),
    );
  }
}
