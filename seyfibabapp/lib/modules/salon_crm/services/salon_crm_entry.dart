import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/router_name.dart';
import '../../../utils/utils.dart';
import '../../authentication/controller/login/login_bloc.dart';
import '../services/salon_crm_service.dart';
import '../services/salon_crm_session.dart';

/// Patron (Seyfibaba alıcı) CRM girişi — alışveriş JWT ile.
class SalonCrmEntry {
  SalonCrmEntry._();

  static Future<void> openPatron(BuildContext context) async {
    if (!Utils.isLoggedIn(context)) {
      Utils.errorSnackBar(
        context,
        'Salon paneli için önce Seyfibaba hesabınızla giriş yapın.',
      );
      Navigator.pushNamed(context, RouteNames.authenticationScreen);
      return;
    }

    final displayName = context.read<LoginBloc>().userInfo?.user.name;
    final existing = await SalonCrmSession.read();
    if (context.mounted && existing != null) {
      final role = existing['role'] ?? '';
      if (role == 'owner' || role == 'staff') {
        await SalonCrmService().syncPushToken(existing['token'] ?? '');
        if (!context.mounted) return;
        Navigator.pushNamed(context, RouteNames.salonCrmHomeScreen);
        return;
      }
      if (role == 'customer') {
        await SalonCrmService().syncPushToken(existing['token'] ?? '');
        if (!context.mounted) return;
        Navigator.pushNamed(context, RouteNames.salonCrmCustomerHomeScreen);
        return;
      }
    }

    Utils.loadingDialog(context);
    try {
      final jwt = context.read<LoginBloc>().userInfo?.accessToken ?? '';
      if (jwt.isEmpty) throw Exception('Giriş gerekli');

      final service = SalonCrmService();
      final res = await service.patronBootstrap(jwt);
      if (!context.mounted) return;
      Utils.closeDialog(context);

      if (res['has_salon'] == true) {
        final token = '${res['token'] ?? ''}';
        final salon = res['salon'];
        await SalonCrmSession.save(
          token: token,
          role: 'owner',
          salonName: salon is Map ? '${salon['name'] ?? ''}' : null,
          salonUsername:
              salon is Map ? '${salon['owner_username'] ?? ''}' : null,
          displayName: displayName,
        );
        await service.syncPushToken(token);
        if (!context.mounted) return;
        Navigator.pushNamed(context, RouteNames.salonCrmHomeScreen);
      } else {
        Navigator.pushNamed(context, RouteNames.salonCrmPatronSetupScreen);
      }
    } catch (e) {
      if (!context.mounted) return;
      Utils.closeDialog(context);
      debugPrint('SalonCrmEntry.openPatron error: $e');
      Navigator.pushNamed(context, RouteNames.salonCrmPatronSetupScreen);
    }
  }

  static void openStaffCustomerAuth(BuildContext context) {
    Navigator.pushNamed(context, RouteNames.salonCrmAltAuthScreen);
  }
}
