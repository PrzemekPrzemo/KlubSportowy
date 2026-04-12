import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

/// Offline cache using SharedPreferences (simple key-value).
///
/// For production: migrate to sqflite for structured queries.
/// This implementation caches JSON responses for offline access.
class OfflineCache {
  static const _prefix = 'cache_';
  static const _syncPrefix = 'sync_';
  static const _defaultTtl = Duration(minutes: 15);

  /// Cache data with type key.
  static Future<void> set(String type, dynamic data) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('$_prefix$type', jsonEncode(data));
    await prefs.setString('$_syncPrefix$type', DateTime.now().toIso8601String());
  }

  /// Get cached data. Returns null if not cached.
  static Future<dynamic> get(String type) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString('$_prefix$type');
    if (raw == null) return null;
    return jsonDecode(raw);
  }

  /// Check if cache needs refresh (older than TTL).
  static Future<bool> needsSync(String type, {Duration? ttl}) async {
    final prefs = await SharedPreferences.getInstance();
    final lastSync = prefs.getString('$_syncPrefix$type');
    if (lastSync == null) return true;
    final syncTime = DateTime.parse(lastSync);
    final maxAge = ttl ?? _defaultTtl;
    return DateTime.now().difference(syncTime) > maxAge;
  }

  /// Get last sync time for a type.
  static Future<DateTime?> lastSync(String type) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString('$_syncPrefix$type');
    return raw != null ? DateTime.parse(raw) : null;
  }

  /// Clear all cached data.
  static Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    final keys = prefs.getKeys().where((k) => k.startsWith(_prefix) || k.startsWith(_syncPrefix));
    for (final key in keys) {
      await prefs.remove(key);
    }
  }

  /// Clear specific type.
  static Future<void> clearType(String type) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('$_prefix$type');
    await prefs.remove('$_syncPrefix$type');
  }
}
