import 'package:shared_preferences/shared_preferences.dart';

import '../../setting/model/announcement_modal_model.dart';

/// Uygulama oturumu + "bir daha gösterme" saklama.
class HomePromoStore {
  HomePromoStore._();

  static const _dismissKey = 'home_promo_dismiss_until';
  static bool _shownThisSession = false;

  static bool get shownThisSession => _shownThisSession;

  static void markSessionShown() => _shownThisSession = true;

  static Future<bool> isDismissed() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_dismissKey);
    if (raw == null || raw.isEmpty) return false;
    final until = DateTime.tryParse(raw);
    if (until == null) return false;
    return DateTime.now().isBefore(until);
  }

  static Future<void> dismissForDays(int days) async {
    final prefs = await SharedPreferences.getInstance();
    final until = DateTime.now().add(Duration(days: days.clamp(1, 365)));
    await prefs.setString(_dismissKey, until.toIso8601String());
    markSessionShown();
  }

  static Future<bool> shouldShow(AnnouncementModalModel? modal) async {
    if (modal == null) return false;
    if (!modal.isActive || !modal.showOnMobileApp || !modal.hasImage) {
      return false;
    }
    if (_shownThisSession) return false;
    if (await isDismissed()) return false;
    return true;
  }
}
