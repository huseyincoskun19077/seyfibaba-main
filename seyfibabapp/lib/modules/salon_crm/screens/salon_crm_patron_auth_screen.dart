import 'package:flutter/material.dart';

import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmPatronAuthScreen extends StatefulWidget {
  const SalonCrmPatronAuthScreen({super.key});

  @override
  State<SalonCrmPatronAuthScreen> createState() =>
      _SalonCrmPatronAuthScreenState();
}

class _SalonCrmPatronAuthScreenState extends State<SalonCrmPatronAuthScreen> {
  final _service = SalonCrmService();
  bool _registerMode = false;
  final _salonName = TextEditingController();
  final _ownerName = TextEditingController();
  final _username = TextEditingController();
  final _password = TextEditingController();
  String _type = 'kuafor';

  @override
  void dispose() {
    _salonName.dispose();
    _ownerName.dispose();
    _username.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final username = _username.text.trim();
    final password = _password.text;
    if (username.isEmpty || password.isEmpty) {
      Utils.errorSnackBar(context, 'Kullanıcı adı ve şifre gerekli');
      return;
    }
    if (_registerMode &&
        (_salonName.text.trim().isEmpty || _ownerName.text.trim().isEmpty)) {
      Utils.errorSnackBar(context, 'Salon adı ve patron adı gerekli');
      return;
    }

    Utils.loadingDialog(context);
    try {
      final Map<String, dynamic> res;
      if (_registerMode) {
        res = await _service.patronRegister(
          salonName: _salonName.text.trim(),
          ownerName: _ownerName.text.trim(),
          username: username,
          password: password,
          type: _type,
        );
      } else {
        res = await _service.patronLogin(
          username: username,
          password: password,
        );
      }
      final token = '${res['token'] ?? ''}';
      if (token.isEmpty) throw Exception('Token alınamadı');
      final salon = res['salon'];
      await SalonCrmSession.save(
        token: token,
        role: 'owner',
        salonName: salon is Map ? '${salon['name'] ?? ''}' : null,
        salonUsername: salon is Map
            ? '${salon['owner_username'] ?? username}'
            : username,
        displayName: salon is Map
            ? '${salon['owner_name'] ?? _ownerName.text}'
            : _ownerName.text,
      );
      await _service.syncPushToken(token);
      if (!mounted) return;
      Utils.closeDialog(context);
      Navigator.pushNamedAndRemoveUntil(
        context,
        RouteNames.salonCrmHomeScreen,
        (route) => route.isFirst,
      );
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    return CrmScaffold(
      title: _registerMode ? 'Salon kaydı' : 'Patron girişi',
      body: ListView(
        padding: const EdgeInsets.fromLTRB(22, 8, 22, 36),
        children: [
          Text(
            _registerMode ? 'Salonunu oluştur' : 'Tekrar hoş geldin',
            style: SalonCrmTheme.titleMd,
          ),
          const SizedBox(height: 8),
          Text(
            _registerMode
                ? 'Birkaç bilgiyle salonunu CRM’e bağla.'
                : 'Salon kullanıcı adın ve şifrenle devam et.',
            style: SalonCrmTheme.body,
          ),
          const SizedBox(height: 22),
          CrmSoftCard(
            child: Column(
              children: [
                if (_registerMode) ...[
                  TextField(
                    controller: _salonName,
                    decoration: SalonCrmTheme.field('Salon / kuaför adı'),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _ownerName,
                    decoration: SalonCrmTheme.field('Patron adı'),
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    value: _type,
                    decoration: SalonCrmTheme.field('Tür'),
                    items: const [
                      DropdownMenuItem(
                        value: 'kuafor',
                        child: Text('Kuaför / Berber'),
                      ),
                      DropdownMenuItem(
                        value: 'guzellik',
                        child: Text('Güzellik'),
                      ),
                    ],
                    onChanged: (v) => setState(() => _type = v ?? 'kuafor'),
                  ),
                  const SizedBox(height: 12),
                ],
                TextField(
                  controller: _username,
                  decoration: SalonCrmTheme.field('Salon kullanıcı adı'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _password,
                  obscureText: true,
                  decoration: SalonCrmTheme.field('Şifre'),
                ),
                const SizedBox(height: 18),
                CrmPrimaryButton(
                  label: _registerMode ? 'Kayıt ol' : 'Giriş yap',
                  onPressed: _submit,
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
          TextButton(
            onPressed: () => setState(() => _registerMode = !_registerMode),
            child: Text(
              _registerMode
                  ? 'Zaten hesabım var — Giriş'
                  : 'Yeni salon — Kayıt ol',
              style: const TextStyle(
                color: SalonCrmTheme.inkSoft,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
