import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../../../core/remote_urls.dart';
import 'story_view_tracker.dart';

/// Ürün görüntüleme + Size Özel yenileme bayrağı (web ile aynı mantık).
class ProductViewTracker {
  ProductViewTracker._();

  static const _dirtyKey = 'kt_size_ozel_dirty';

  static Future<void> markDirty() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_dirtyKey, '${DateTime.now().millisecondsSinceEpoch}');
    } catch (_) {}
  }

  static Future<bool> consumeDirty() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final v = prefs.getString(_dirtyKey);
      if (v == null || v.isEmpty) return false;
      await prefs.remove(_dirtyKey);
      return true;
    } catch (_) {
      return false;
    }
  }

  static Future<bool> hasDirty() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final v = prefs.getString(_dirtyKey);
      return v != null && v.isNotEmpty;
    } catch (_) {
      return false;
    }
  }

  static Future<void> track({
    required int productId,
    String? accessToken,
  }) async {
    if (productId <= 0) return;
    try {
      final token = (accessToken ?? '').trim();
      final headers = <String, String>{
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      };
      late final http.Response res;
      if (token.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
        res = await http
            .post(
              Uri.parse(RemoteUrls.userProductView),
              headers: headers,
              body: jsonEncode({'product_id': productId}),
            )
            .timeout(const Duration(seconds: 8));
      } else {
        final guest = await StoryViewTracker.guestKey();
        res = await http
            .post(
              Uri.parse(RemoteUrls.guestProductView),
              headers: headers,
              body: jsonEncode({
                'product_id': productId,
                'guest_key': guest,
                'consent': true,
              }),
            )
            .timeout(const Duration(seconds: 8));
      }
      if (res.statusCode >= 200 && res.statusCode < 300) {
        await markDirty();
      }
    } catch (_) {}
  }
}
