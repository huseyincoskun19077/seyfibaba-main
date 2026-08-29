import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../../authentication/controller/login/login_bloc.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

/// Patron salon kurulumu — Seyfibaba hesabına bağlı, ayrı CRM şifresi yok.
class SalonCrmPatronSetupScreen extends StatefulWidget {
  const SalonCrmPatronSetupScreen({super.key});

  @override
  State<SalonCrmPatronSetupScreen> createState() =>
      _SalonCrmPatronSetupScreenState();
}

class _SalonCrmPatronSetupScreenState extends State<SalonCrmPatronSetupScreen> {
  final _service = SalonCrmService();
  final _salonName = TextEditingController();
  String _type = 'kuafor';

  @override
  void dispose() {
    _salonName.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_salonName.text.trim().isEmpty) {
      Utils.errorSnackBar(context, 'Salon adı gerekli');
      return;
    }

    final ownerName = context.read<LoginBloc>().userInfo?.user.name;
    final jwt = context.read<LoginBloc>().userInfo?.accessToken ?? '';
    if (jwt.isEmpty) {
      Utils.errorSnackBar(context, 'Giriş gerekli');
      return;
    }

    Utils.loadingDialog(context);
    try {
      final res = await _service.patronRegisterLinked(
        shoppingToken: jwt,
        salonName: _salonName.text.trim(),
        type: _type,
      );
      final token = '${res['token'] ?? ''}';
      if (token.isEmpty) throw Exception('Token alınamadı');
      final salon = res['salon'];
      await SalonCrmSession.save(
        token: token,
        role: 'owner',
        salonName: salon is Map ? '${salon['name'] ?? ''}' : null,
        salonUsername:
            salon is Map ? '${salon['owner_username'] ?? ''}' : null,
        displayName: ownerName,
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
      title: 'Salon kur',
      body: ListView(
        padding: const EdgeInsets.fromLTRB(22, 8, 22, 36),
        children: [
          Text('Salonunu bağla', style: SalonCrmTheme.titleMd),
          const SizedBox(height: 8),
          Text(
            'Seyfibaba hesabın patron hesabın olacak. Personel ve müşteriler ayrı giriş kullanır.',
            style: SalonCrmTheme.body,
          ),
          const SizedBox(height: 22),
          CrmSoftCard(
            child: Column(
              children: [
                TextField(
                  controller: _salonName,
                  decoration: SalonCrmTheme.field('Salon / kuaför adı'),
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
                const SizedBox(height: 18),
                CrmPrimaryButton(label: 'Salonu oluştur', onPressed: _submit),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
