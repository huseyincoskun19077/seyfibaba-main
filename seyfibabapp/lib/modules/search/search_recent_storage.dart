import 'package:shared_preferences/shared_preferences.dart';

class SearchRecentStorage {
  SearchRecentStorage._();

  static const _key = 'recent_product_searches';
  static const _secondHandKey = 'recent_second_hand_searches';
  static const _maxItems = 8;

  static Future<List<String>> load() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getStringList(_key) ?? [];
  }

  static Future<void> add(String query) async {
    final trimmed = query.trim();
    if (trimmed.length < 2) return;

    final prefs = await SharedPreferences.getInstance();
    final current = prefs.getStringList(_key) ?? [];
    final updated = [
      trimmed,
      ...current.where((e) => e.toLowerCase() != trimmed.toLowerCase()),
    ].take(_maxItems).toList();
    await prefs.setStringList(_key, updated);
  }

  static Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_key);
  }

  static Future<List<String>> loadSecondHand() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getStringList(_secondHandKey) ?? [];
  }

  static Future<void> addSecondHand(String query) async {
    final trimmed = query.trim();
    if (trimmed.length < 2) return;

    final prefs = await SharedPreferences.getInstance();
    final current = prefs.getStringList(_secondHandKey) ?? [];
    final updated = [
      trimmed,
      ...current.where((e) => e.toLowerCase() != trimmed.toLowerCase()),
    ].take(_maxItems).toList();
    await prefs.setStringList(_secondHandKey, updated);
  }

  static Future<void> clearSecondHand() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_secondHandKey);
  }
}

const kPopularSearchTerms = [
  'kuaför koltuğu',
  'fön makinesi',
  'berber makası',
  'saç boyası',
  'yıkama seti',
  'manikür seti',
];
