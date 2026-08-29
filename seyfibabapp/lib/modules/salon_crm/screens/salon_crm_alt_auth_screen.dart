import 'package:flutter/material.dart';

import '../../../core/router_name.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

/// Personel ve salon müşterisi — ayrı giriş (patron kapısında gösterilmez).
class SalonCrmAltAuthScreen extends StatelessWidget {
  const SalonCrmAltAuthScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return CrmScaffold(
      title: 'Personel / Müşteri',
      body: ListView(
        padding: const EdgeInsets.fromLTRB(22, 12, 22, 36),
        children: [
          Text('Salon ekibi veya müşteri', style: SalonCrmTheme.titleMd),
          const SizedBox(height: 8),
          Text(
            'Patron değilseniz buradan devam edin. Patronlar Seyfibaba hesabıyla giriş yapar.',
            style: SalonCrmTheme.body,
          ),
          const SizedBox(height: 24),
          CrmRoleTile(
            icon: Icons.badge_rounded,
            title: 'Personel girişi',
            subtitle: 'Patronun verdiği kullanıcı adı',
            onTap: () => Navigator.pushNamed(
              context,
              RouteNames.salonCrmStaffAuthScreen,
            ),
          ),
          const SizedBox(height: 12),
          CrmRoleTile(
            icon: Icons.spa_rounded,
            title: 'Salon müşterisi',
            subtitle: 'Berber kodu veya QR ile bağlan',
            onTap: () => Navigator.pushNamed(
              context,
              RouteNames.salonCrmCustomerLinkScreen,
            ),
          ),
        ],
      ),
    );
  }
}
