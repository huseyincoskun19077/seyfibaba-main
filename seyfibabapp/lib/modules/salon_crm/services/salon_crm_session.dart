import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

class SalonCrmSession {
  SalonCrmSession._();

  static const _key = 'salon_crm_session_v1';
  static const _linkKey = 'salon_crm_linked_salon_v1';

  static Future<void> save({
    required String token,
    required String role,
    String? salonName,
    String? salonUsername,
    String? joinCode,
    String? displayName,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      _key,
      jsonEncode({
        'token': token,
        'role': role,
        'salon_name': salonName,
        'salon_username': salonUsername,
        'join_code': joinCode,
        'display_name': displayName,
      }),
    );
  }

  static Future<Map<String, String>?> read() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_key);
    if (raw == null || raw.isEmpty) return null;
    try {
      final map = jsonDecode(raw);
      if (map is! Map) return null;
      final token = '${map['token'] ?? ''}';
      if (token.isEmpty) return null;
      return {
        'token': token,
        'role': '${map['role'] ?? ''}',
        'salon_name': '${map['salon_name'] ?? ''}',
        'salon_username': '${map['salon_username'] ?? ''}',
        'join_code': '${map['join_code'] ?? ''}',
        'display_name': '${map['display_name'] ?? ''}',
      };
    } catch (_) {
      return null;
    }
  }

  static Future<String?> token() async => (await read())?['token'];

  static Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_key);
  }

  static Future<void> saveLinkedSalon({
    required String joinCode,
    required String salonName,
    String? salonUsername,
    int? salonId,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      _linkKey,
      jsonEncode({
        'join_code': joinCode,
        'salon_name': salonName,
        'salon_username': salonUsername,
        'salon_id': salonId,
      }),
    );
  }

  static Future<Map<String, String>?> readLinkedSalon() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_linkKey);
    if (raw == null || raw.isEmpty) return null;
    try {
      final map = jsonDecode(raw);
      if (map is! Map) return null;
      final code = '${map['join_code'] ?? ''}'.trim();
      if (code.isEmpty) return null;
      return {
        'join_code': code,
        'salon_name': '${map['salon_name'] ?? ''}',
        'salon_username': '${map['salon_username'] ?? ''}',
        'salon_id': '${map['salon_id'] ?? ''}',
      };
    } catch (_) {
      return null;
    }
  }

  static Future<void> clearLinkedSalon() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_linkKey);
  }

  static Future<void> clearCustomerAll() async {
    await clear();
    await clearLinkedSalon();
  }
}
