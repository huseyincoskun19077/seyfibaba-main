import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../../authentication/controller/login/login_bloc.dart';
import '../services/salon_crm_entry.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

class SalonCrmGateScreen extends StatefulWidget {
  const SalonCrmGateScreen({super.key});

  @override
  State<SalonCrmGateScreen> createState() => _SalonCrmGateScreenState();
}

class _SalonCrmGateScreenState extends State<SalonCrmGateScreen> {
  bool _checking = true;

  @override
  void initState() {
    super.initState();
    _boot();
  }

  Future<void> _boot() async {
    final session = await SalonCrmSession.read();
    if (!mounted) return;

    if (session != null) {
      final token = session['token'] ?? '';
      if (token.isNotEmpty) {
        SalonCrmService().syncPushToken(token);
      }
      final role = session['role'];
      if (role == 'customer') {
        Navigator.pushReplacementNamed(
          context,
          RouteNames.salonCrmCustomerHomeScreen,
        );
      } else {
        Navigator.pushReplacementNamed(
          context,
          RouteNames.salonCrmHomeScreen,
        );
      }
      return;
    }

    // CRM session yok ama Seyfibaba'ya giriş yapmışsa → direkt patron girişi
    if (Utils.isLoggedIn(context)) {
      SalonCrmEntry.openPatron(context);
      return;
    }

    // Hiç giriş yapılmamış → rol seçim ekranı
    setState(() => _checking = false);
  }

  @override
  Widget build(BuildContext context) {
    if (_checking) {
      return const Scaffold(
        backgroundColor: SalonCrmTheme.bg,
        body: Center(
          child: CircularProgressIndicator(color: SalonCrmTheme.accent),
        ),
      );
    }

    return CrmScaffold(
      title: 'Salon CRM',
      body: ListView(
        padding: const EdgeInsets.fromLTRB(22, 24, 22, 36),
        children: [
          const Icon(
            Icons.store_rounded,
            size: 56,
            color: SalonCrmTheme.accent,
          ),
          const SizedBox(height: 16),
          Text(
            'Salon CRM\'e Hoş Geldiniz',
            textAlign: TextAlign.center,
            style: SalonCrmTheme.titleMd,
          ),
          const SizedBox(height: 8),
          Text(
            'Devam etmek için rolünüzü seçin.',
            textAlign: TextAlign.center,
            style: SalonCrmTheme.body,
          ),
          const SizedBox(height: 28),
          CrmRoleTile(
            icon: Icons.admin_panel_settings_rounded,
            title: 'Salon Sahibi Girişi',
            subtitle:
                'Seyfibaba hesabınızla giriş yapın. Salonunuzu yönetin, personel ekleyin, randevuları takip edin.',
            onTap: () => SalonCrmEntry.openPatron(context),
          ),
          const SizedBox(height: 14),
          CrmRoleTile(
            icon: Icons.badge_rounded,
            title: 'Personel Girişi',
            subtitle:
                'Salon sahibinin size verdiği kullanıcı adı ve şifre ile giriş yapın.',
            onTap: () => Navigator.pushNamed(
              context,
              RouteNames.salonCrmStaffAuthScreen,
            ),
          ),
          const SizedBox(height: 14),
          CrmRoleTile(
            icon: Icons.spa_rounded,
            title: 'Müşteri Girişi',
            subtitle:
                'Berber kodu veya QR ile salona bağlanın. Randevu alın, geçmişinizi görün.',
            onTap: () => Navigator.pushNamed(
              context,
              RouteNames.salonCrmCustomerLinkScreen,
            ),
          ),
          const SizedBox(height: 20),
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text(
              'Menüye dön',
              style: TextStyle(
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
