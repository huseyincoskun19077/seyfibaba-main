import 'package:flutter/material.dart';

import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmCustomerAuthScreen extends StatefulWidget {
  const SalonCrmCustomerAuthScreen({super.key});

  @override
  State<SalonCrmCustomerAuthScreen> createState() =>
      _SalonCrmCustomerAuthScreenState();
}

class _SalonCrmCustomerAuthScreenState
    extends State<SalonCrmCustomerAuthScreen> {
  final _service = SalonCrmService();
  bool _registerMode = true;
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _password = TextEditingController();
  String _joinCode = '';
  String _salonName = '';

  @override
  void initState() {
    super.initState();
    _loadLinked();
  }

  Future<void> _loadLinked() async {
    final linked = await SalonCrmSession.readLinkedSalon();
    if (!mounted) return;
    if (linked == null) {
      Navigator.pushReplacementNamed(
        context,
        RouteNames.salonCrmCustomerLinkScreen,
      );
      return;
    }
    final args = ModalRoute.of(context)?.settings.arguments;
    final register = args is Map ? args['register'] == true : null;
    setState(() {
      _joinCode = linked['join_code'] ?? '';
      _salonName = linked['salon_name'] ?? '';
      if (register != null) _registerMode = register;
    });
  }

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_joinCode.isEmpty) {
      Utils.errorSnackBar(context, 'Önce berber kodunu doğrulayın');
      return;
    }
    if (_phone.text.trim().isEmpty || _password.text.isEmpty) {
      Utils.errorSnackBar(context, 'Telefon ve şifre gerekli');
      return;
    }
    if (_registerMode && _name.text.trim().isEmpty) {
      Utils.errorSnackBar(context, 'Ad gerekli');
      return;
    }

    Utils.loadingDialog(context);
    try {
      final Map<String, dynamic> res;
      if (_registerMode) {
        res = await _service.customerRegister(
          joinCode: _joinCode,
          name: _name.text.trim(),
          phone: _phone.text.trim(),
          password: _password.text,
        );
      } else {
        res = await _service.customerLogin(
          joinCode: _joinCode,
          phone: _phone.text.trim(),
          password: _password.text,
        );
      }
      final token = '${res['token'] ?? ''}';
      if (token.isEmpty) throw Exception('Token alınamadı');
      final salon = res['salon'];
      final customer = res['customer'];
      final joinCode = salon is Map
          ? '${salon['join_code'] ?? _joinCode}'
          : _joinCode;
      await SalonCrmSession.saveLinkedSalon(
        joinCode: joinCode,
        salonName: salon is Map ? '${salon['name'] ?? _salonName}' : _salonName,
        salonUsername:
            salon is Map ? '${salon['owner_username'] ?? ''}' : null,
        salonId: salon is Map ? int.tryParse('${salon['id'] ?? ''}') : null,
      );
      await SalonCrmSession.save(
        token: token,
        role: 'customer',
        salonName: salon is Map ? '${salon['name'] ?? ''}' : _salonName,
        salonUsername:
            salon is Map ? '${salon['owner_username'] ?? ''}' : null,
        joinCode: joinCode,
        displayName: customer is Map ? '${customer['name'] ?? ''}' : null,
      );
      await _service.syncPushToken(token);
      if (!mounted) return;
      Utils.closeDialog(context);
      Navigator.pushNamedAndRemoveUntil(
        context,
        RouteNames.salonCrmCustomerHomeScreen,
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
    if (_joinCode.isEmpty) {
      return const CrmScaffold(
        title: 'Müşteri',
        body: Center(
          child: CircularProgressIndicator(color: SalonCrmTheme.accent),
        ),
      );
    }

    return CrmScaffold(
      title: _registerMode ? 'Müşteri kaydı' : 'Müşteri girişi',
      body: ListView(
        padding: const EdgeInsets.fromLTRB(22, 8, 22, 36),
        children: [
          Text(
            _registerMode ? 'Randevu için kayıt ol' : 'Randevularına dön',
            style: SalonCrmTheme.titleMd,
          ),
          const SizedBox(height: 8),
          Text(
            'Yalnızca bağlı olduğun berber için kayıt olursun.',
            style: SalonCrmTheme.body,
          ),
          const SizedBox(height: 16),
          CrmSoftCard(
            child: Row(
              children: [
                const Icon(Icons.storefront_outlined, color: SalonCrmTheme.ink),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _salonName,
                        style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          color: SalonCrmTheme.ink,
                        ),
                      ),
                      Text('Kod: $_joinCode', style: SalonCrmTheme.caption),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          CrmSoftCard(
            child: Column(
              children: [
                if (_registerMode) ...[
                  TextField(
                    controller: _name,
                    decoration: SalonCrmTheme.field('Adınız'),
                  ),
                  const SizedBox(height: 12),
                ],
                TextField(
                  controller: _phone,
                  keyboardType: TextInputType.phone,
                  decoration: SalonCrmTheme.field('Telefon'),
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
                  ? 'Hesabım var — Giriş'
                  : 'Yeni müşteri — Kayıt ol',
              style: const TextStyle(
                color: SalonCrmTheme.inkSoft,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          TextButton(
            onPressed: () => Navigator.pushReplacementNamed(
              context,
              RouteNames.salonCrmCustomerLinkScreen,
            ),
            child: const Text(
              'Başka berbere geç',
              style: TextStyle(
                color: SalonCrmTheme.muted,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
