import 'package:flutter/material.dart';

import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmStaffAuthScreen extends StatefulWidget {
  const SalonCrmStaffAuthScreen({super.key});

  @override
  State<SalonCrmStaffAuthScreen> createState() =>
      _SalonCrmStaffAuthScreenState();
}

class _SalonCrmStaffAuthScreenState extends State<SalonCrmStaffAuthScreen> {
  final _service = SalonCrmService();
  final _username = TextEditingController();
  final _password = TextEditingController();

  @override
  void dispose() {
    _username.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_username.text.trim().isEmpty || _password.text.isEmpty) {
      Utils.errorSnackBar(context, 'Kullanıcı adı ve şifre gerekli');
      return;
    }
    Utils.loadingDialog(context);
    try {
      final res = await _service.staffLogin(
        username: _username.text.trim(),
        password: _password.text,
      );
      final token = '${res['token'] ?? ''}';
      if (token.isEmpty) throw Exception('Token alınamadı');
      final salon = res['salon'];
      final staff = res['staff'];
      await SalonCrmSession.save(
        token: token,
        role: 'staff',
        salonName: salon is Map ? '${salon['name'] ?? ''}' : null,
        salonUsername:
            salon is Map ? '${salon['owner_username'] ?? ''}' : null,
        displayName: staff is Map ? '${staff['name'] ?? ''}' : null,
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
      salonCrmShowError(context, e);
    }
  }

  @override
  Widget build(BuildContext context) {
    return CrmScaffold(
      title: 'Personel girişi',
      body: ListView(
        padding: const EdgeInsets.fromLTRB(22, 8, 22, 36),
        children: [
          Text('Güne hazır mısın?', style: SalonCrmTheme.titleMd),
          const SizedBox(height: 8),
          Text(
            'Patronun verdiği kullanıcı adı ve şifre ile giriş yap.',
            style: SalonCrmTheme.body,
          ),
          const SizedBox(height: 22),
          CrmSoftCard(
            child: Column(
              children: [
                TextField(
                  controller: _username,
                  autofocus: true,
                  textInputAction: TextInputAction.next,
                  decoration: SalonCrmTheme.field('Kullanıcı adı'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _password,
                  obscureText: true,
                  textInputAction: TextInputAction.done,
                  onSubmitted: (_) => _submit(),
                  decoration: SalonCrmTheme.field('Şifre'),
                ),
                const SizedBox(height: 18),
                CrmPrimaryButton(label: 'Giriş yap', onPressed: _submit),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
