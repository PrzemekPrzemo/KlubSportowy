import '../config/api_config.dart';
import 'api_client.dart';
import 'offline_cache.dart';

/// Sync service — fetches data from API and caches locally.
/// Used by screens to show cached data when offline.
class SyncService {
  /// Sync all data types. Call on app foreground resume.
  static Future<void> syncAll() async {
    await _syncType('events_upcoming', ApiConfig.eventsUpcomingUrl, {'limit': '30'});
    await _syncType('payments_summary', ApiConfig.paymentsSummaryUrl, null);
    await _syncType('payments', ApiConfig.paymentsUrl, {'page': '1'});
  }

  /// Sync specific type if stale.
  static Future<void> syncIfNeeded(String type, String url, {Map<String, String>? params}) async {
    if (await OfflineCache.needsSync(type)) {
      await _syncType(type, url, params);
    }
  }

  /// Get data — try API first, fallback to cache.
  static Future<Map<String, dynamic>?> getData(String type, String url, {Map<String, String>? params}) async {
    try {
      final resp = await ApiClient.get(url, queryParams: params);
      await OfflineCache.set(type, resp);
      return resp;
    } catch (e) {
      // Offline — try cache
      final cached = await OfflineCache.get(type);
      if (cached != null && cached is Map<String, dynamic>) {
        return cached;
      }
      return null;
    }
  }

  static Future<void> _syncType(String type, String url, Map<String, String>? params) async {
    try {
      final resp = await ApiClient.get(url, queryParams: params);
      await OfflineCache.set(type, resp);
    } catch (e) {
      // Offline — skip sync silently
    }
  }
}
