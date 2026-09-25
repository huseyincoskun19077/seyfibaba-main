import 'dart:convert';
import 'dart:math';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../../../core/remote_urls.dart';

/// Story görüntülenme kaydı (admin istatistikleri için).
class StoryViewTracker {
  StoryViewTracker._();

  static const _guestKeyPref = 'story_guest_key_v1';

  static Future<String> guestKey() async {
    final prefs = await SharedPreferences.getInstance();
    var key = prefs.getString(_guestKeyPref);
    if (key == null || key.isEmpty) {
      key =
          '${DateTime.now().millisecondsSinceEpoch}-${Random().nextInt(1 << 30)}';
      await prefs.setString(_guestKeyPref, key);
    }
    return key;
  }

  static Future<void> track({
    required int storyId,
    required String platform,
    int productIndex = 0,
    int productsTotal = 0,
    bool completed = false,
    String? accessToken,
  }) async {
    if (storyId <= 0) return;
    try {
      final guest = await guestKey();
      final headers = <String, String>{
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      };
      final token = (accessToken ?? '').trim();
      if (token.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }
      await http
          .post(
            Uri.parse('${RemoteUrls.baseUrl}story-view'),
            headers: headers,
            body: jsonEncode({
              'story_id': storyId,
              'platform': platform,
              'guest_key': token.isEmpty ? guest : null,
              'product_index': productIndex,
              'products_total': productsTotal,
              'completed': completed,
            }),
          )
          .timeout(const Duration(seconds: 8));
    } catch (_) {}
  }
}
