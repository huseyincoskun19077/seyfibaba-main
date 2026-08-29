import 'package:flutter/material.dart';

import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../widgets/salon_crm_theme.dart';
import '../widgets/salon_crm_ui.dart';

/// QR okuma — kamera paketi olmadan manuel kod girişine yönlendirir.
/// Berber QR'ını telefon kamerasıyla okuyup kodu yapıştırabilir.
class SalonCrmCustomerQrScanScreen extends StatelessWidget {
  const SalonCrmCustomerQrScanScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return CrmScaffold(
      title: 'QR ile bağlan',
      body: ListView(
        padding: const EdgeInsets.fromLTRB(22, 8, 22, 36),
        children: [
          Text('Berber kodunu gir', style: SalonCrmTheme.titleMd),
          const SizedBox(height: 8),
          Text(
            'Telefonunuzun kamerasıyla berberin QR kodunu okuyun, '
            'ekranda çıkan kodu kopyalayıp bir sonraki adımda yapıştırın.',
            style: SalonCrmTheme.body,
          ),
          const SizedBox(height: 20),
          CrmSoftCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Row(
                  children: [
                    Icon(Icons.qr_code_2_rounded, color: SalonCrmTheme.ink),
                    SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        '1. Berberin QR kodunu okutun\n'
                        '2. Çıkan kodu kopyalayın\n'
                        '3. Berber kodu alanına yapıştırın',
                        style: TextStyle(
                          height: 1.5,
                          color: SalonCrmTheme.inkSoft,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                CrmPrimaryButton(
                  label: 'Berber kodu gir',
                  icon: Icons.edit_rounded,
                  onPressed: () {
                    Navigator.pop(context);
                    Utils.showSnackBar(
                      context,
                      'Berber kodunu girin veya yapıştırın',
                    );
                  },
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Geri dön'),
          ),
        ],
      ),
    );
  }
}
