import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmCustomerLinkScreen extends StatefulWidget {
  const SalonCrmCustomerLinkScreen({super.key});

  @override
  State<SalonCrmCustomerLinkScreen> createState() =>
      _SalonCrmCustomerLinkScreenState();
}

class _SalonCrmCustomerLinkScreenState extends State<SalonCrmCustomerLinkScreen> {
  final _service = SalonCrmService();
  final _codeCtrl = TextEditingController();
  bool _loading = false;
  bool _checkingSaved = true;
  SalonCrmJoinPreview? _preview;
  Map<String, String>? _savedLink;

  @override
  void initState() {
    super.initState();
    _boot();
  }

  @override
  void dispose() {
    _codeCtrl.dispose();
    super.dispose();
  }

  Future<void> _boot() async {
    final saved = await SalonCrmSession.readLinkedSalon();
    if (!mounted) return;
    if (saved != null) {
      setState(() {
        _savedLink = saved;
        _checkingSaved = false;
      });
      await _lookup(saved['join_code'] ?? '', silent: true);
      return;
    }
    setState(() => _checkingSaved = false);
  }

  Future<void> _lookup(String code, {bool silent = false}) async {
    final normalized = code.trim().toUpperCase();
    if (normalized.length < 4) {
      if (!silent) Utils.errorSnackBar(context, 'Geçerli bir berber kodu girin');
      return;
    }
    setState(() => _loading = true);
    try {
      final preview = await _service.fetchJoinPreview(normalized);
      await SalonCrmSession.saveLinkedSalon(
        joinCode: preview.joinCode,
        salonName: preview.salonName,
        salonId: preview.salonId,
      );
      if (!mounted) return;
      setState(() {
        _preview = preview;
        _savedLink = {
          'join_code': preview.joinCode,
          'salon_name': preview.salonName,
        };
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        if (!silent) _preview = null;
      });
      if (!silent) Utils.errorSnackBar(context, e.toString());
    }
  }

  Future<void> _scanQr() async {
    await Navigator.pushNamed(
      context,
      RouteNames.salonCrmCustomerQrScanScreen,
    );
  }

  void _continueAuth({required bool register}) {
    if (_preview == null) {
      Utils.errorSnackBar(context, 'Önce berber kodunu doğrulayın');
      return;
    }
    Navigator.pushNamed(
      context,
      RouteNames.salonCrmCustomerAuthScreen,
      arguments: {'register': register},
    );
  }

  Future<void> _changeSalon() async {
    await SalonCrmSession.clearCustomerAll();
    if (!mounted) return;
    setState(() {
      _preview = null;
      _savedLink = null;
      _codeCtrl.clear();
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_checkingSaved) {
      return const CrmScaffold(
        title: 'Berberine bağlan',
        body: Center(
          child: CircularProgressIndicator(color: SalonCrmTheme.accent),
        ),
      );
    }

    return CrmScaffold(
      title: 'Berberine bağlan',
      body: ListView(
        padding: const EdgeInsets.fromLTRB(22, 8, 22, 36),
        children: [
          Text('Kendi berberin', style: SalonCrmTheme.titleMd),
          const SizedBox(height: 8),
          Text(
            'Diğer berberler listelenmez. Berberinin verdiği kodu girin veya QR okutun.',
            style: SalonCrmTheme.body,
          ),
          const SizedBox(height: 22),
          if (_preview != null) ...[
            CrmSoftCard(
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 28,
                    backgroundColor: SalonCrmTheme.line.withValues(alpha: 0.4),
                    backgroundImage: _preview!.logoImage != null &&
                            _preview!.logoImage!.isNotEmpty
                        ? NetworkImage(
                            RemoteUrls.imageUrl(_preview!.logoImage!),
                          )
                        : null,
                    child: _preview!.logoImage == null ||
                            _preview!.logoImage!.isEmpty
                        ? const Icon(Icons.storefront_outlined)
                        : null,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _preview!.salonName,
                          style: const TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 16,
                            color: SalonCrmTheme.ink,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Kod: ${_preview!.joinCode}',
                          style: SalonCrmTheme.caption,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            CrmPrimaryButton(
              label: 'Kayıt ol',
              icon: Icons.person_add_outlined,
              onPressed: () => _continueAuth(register: true),
            ),
            const SizedBox(height: 10),
            OutlinedButton(
              onPressed: () => _continueAuth(register: false),
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
                foregroundColor: SalonCrmTheme.ink,
                side: const BorderSide(color: SalonCrmTheme.line),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(SalonCrmTheme.radiusSm),
                ),
              ),
              child: const Text(
                'Giriş yap',
                style: TextStyle(fontWeight: FontWeight.w700),
              ),
            ),
            const SizedBox(height: 16),
            TextButton(
              onPressed: _changeSalon,
              child: const Text(
                'Başka berbere geç (yeni kod)',
                style: TextStyle(
                  color: SalonCrmTheme.inkSoft,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ] else ...[
            CrmSoftCard(
              child: Column(
                children: [
                  TextField(
                    controller: _codeCtrl,
                    textCapitalization: TextCapitalization.characters,
                    inputFormatters: [
                      FilteringTextInputFormatter.allow(RegExp(r'[A-Za-z0-9]')),
                      LengthLimitingTextInputFormatter(8),
                    ],
                    decoration: SalonCrmTheme.field('Berber kodu'),
                  ),
                  const SizedBox(height: 12),
                  CrmPrimaryButton(
                    label: _loading ? 'Kontrol ediliyor…' : 'Berberi bul',
                    icon: Icons.search_rounded,
                    onPressed: _loading ? null : () => _lookup(_codeCtrl.text),
                  ),
                  const SizedBox(height: 10),
                  OutlinedButton.icon(
                    onPressed: _loading ? null : _scanQr,
                    style: OutlinedButton.styleFrom(
                      minimumSize: const Size.fromHeight(48),
                      foregroundColor: SalonCrmTheme.ink,
                      side: const BorderSide(color: SalonCrmTheme.line),
                      shape: RoundedRectangleBorder(
                        borderRadius:
                            BorderRadius.circular(SalonCrmTheme.radiusSm),
                      ),
                    ),
                    icon: const Icon(Icons.qr_code_scanner_rounded),
                    label: const Text(
                      'QR okut',
                      style: TextStyle(fontWeight: FontWeight.w700),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}
