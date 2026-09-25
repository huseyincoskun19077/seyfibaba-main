import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

/// Story izleme durumu — ürün id ile kaldığı yerden devam.
class StoryProgressStore {
  StoryProgressStore._();

  static const _key = 'story_watch_progress_v4';

  static Future<Map<String, dynamic>> _all() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_key);
    if (raw == null || raw.isEmpty) {
      final legacy = prefs.getString('story_watch_progress_v3');
      if (legacy != null && legacy.isNotEmpty) {
        await prefs.setString(_key, legacy);
        return _decode(legacy);
      }
      return {};
    }
    return _decode(raw);
  }

  static Map<String, dynamic> _decode(String raw) {
    try {
      final decoded = jsonDecode(raw);
      if (decoded is Map) return Map<String, dynamic>.from(decoded);
    } catch (_) {}
    return {};
  }

  static Future<void> _save(Map<String, dynamic> data) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_key, jsonEncode(data));
  }

  static String fingerprint(List<int> productIds) {
    final sorted = [...productIds]..sort();
    return sorted.join(',');
  }

  static bool _flag(dynamic v) => v == true || '$v' == '1';

  static Future<StoryWatchState> getState(
    int storyId, {
    String? productFingerprint,
  }) async {
    final all = await _all();
    final entry = all['$storyId'];
    if (entry is! Map) {
      return const StoryWatchState();
    }

    final savedFp = '${entry['fp'] ?? ''}';
    final index = int.tryParse('${entry['index'] ?? 0}') ?? 0;
    final lastProductId = int.tryParse('${entry['last_product_id'] ?? 0}') ?? 0;
    final completed = _flag(entry['completed']);
    final watched = _flag(entry['watched']) ||
        _flag(entry['seen_once']) ||
        completed;

    final fpChanged = productFingerprint != null &&
        productFingerprint.isNotEmpty &&
        savedFp.isNotEmpty &&
        savedFp != productFingerprint;

    // Yeni ürün seti → sarı halka + baştan
    if (fpChanged) {
      return StoryWatchState(
        index: 0,
        lastProductId: 0,
        completed: false,
        seenOnce: false,
        fingerprint: savedFp,
        fingerprintChanged: true,
      );
    }

    return StoryWatchState(
      index: index < 0 ? 0 : index,
      lastProductId: lastProductId,
      completed: completed,
      seenOnce: watched,
      fingerprint: savedFp,
      fingerprintChanged: false,
    );
  }

  /// Halka: izlendiyse mavi; ürün seti değiştiyse sarı.
  static Future<bool> isUnwatched(
    int storyId, {
    String? productFingerprint,
  }) async {
    final state =
        await getState(storyId, productFingerprint: productFingerprint);
    return !state.seenOnce;
  }

  static Future<void> saveProgress({
    required int storyId,
    required int index,
    required int lastProductId,
    required bool completed,
    required String productFingerprint,
    bool watched = false,
  }) async {
    final all = await _all();
    final prev = all['$storyId'];

    var wasWatched = watched || completed;
    if (prev is Map) {
      final prevFp = '${prev['fp'] ?? ''}';
      final fpChanged = prevFp.isNotEmpty &&
          productFingerprint.isNotEmpty &&
          prevFp != productFingerprint;
      if (fpChanged) {
        // Yeni ürünler: eski watched sıfırlanır (completed bu çağrıda true değilse)
        wasWatched = watched || completed;
      } else {
        wasWatched = wasWatched ||
            _flag(prev['watched']) ||
            _flag(prev['seen_once']) ||
            _flag(prev['completed']);
      }
    }

    all['$storyId'] = {
      'index': index,
      'last_product_id': lastProductId,
      'completed': completed,
      'watched': wasWatched || completed,
      'seen_once': wasWatched || completed,
      'fp': productFingerprint.isNotEmpty
          ? productFingerprint
          : (prev is Map ? '${prev['fp'] ?? ''}' : ''),
      'updated_at': DateTime.now().toIso8601String(),
    };
    await _save(all);
  }

  static Future<void> markCompleted({
    required int storyId,
    required int index,
    required int lastProductId,
    required String productFingerprint,
  }) async {
    await saveProgress(
      storyId: storyId,
      index: index,
      lastProductId: lastProductId,
      completed: true,
      watched: true,
      productFingerprint: productFingerprint,
    );
  }

  static Future<void> markLinkWatched(int storyId) async {
    await saveProgress(
      storyId: storyId,
      index: 0,
      lastProductId: 0,
      completed: true,
      watched: true,
      productFingerprint: 'link',
    );
  }
}

class StoryWatchState {
  final int index;
  final int lastProductId;
  final bool completed;
  final bool seenOnce;
  final String fingerprint;
  final bool fingerprintChanged;

  const StoryWatchState({
    this.index = 0,
    this.lastProductId = 0,
    this.completed = false,
    this.seenOnce = false,
    this.fingerprint = '',
    this.fingerprintChanged = false,
  });
}
