import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import 'api_client.dart';

/// Push notification service — registers FCM token with backend.
///
/// Firebase setup required:
///   1. Add google-services.json (Android) / GoogleService-Info.plist (iOS)
///   2. Add firebase_messaging + firebase_core to pubspec.yaml
///   3. Call NotificationService.init() in main.dart
///
/// For now this is a stub that can be activated when Firebase is configured.
class NotificationService {
  static String? _fcmToken;

  /// Initialize push notifications.
  /// Call after successful login.
  static Future<void> init(int memberId) async {
    try {
      // Firebase messaging would be initialized here:
      // final messaging = FirebaseMessaging.instance;
      // await messaging.requestPermission();
      // _fcmToken = await messaging.getToken();
      //
      // For now, generate a placeholder token for testing:
      _fcmToken = 'fcm_placeholder_${DateTime.now().millisecondsSinceEpoch}';

      if (_fcmToken != null) {
        await _registerToken(memberId, _fcmToken!);
      }

      // Listen for token refresh:
      // messaging.onTokenRefresh.listen((token) => _registerToken(memberId, token));

      // Handle foreground messages:
      // FirebaseMessaging.onMessage.listen(_handleForegroundMessage);
    } catch (e) {
      print('NotificationService init failed: $e');
    }
  }

  static Future<void> _registerToken(int memberId, String token) async {
    try {
      await ApiClient.post(
        '${ApiConfig.baseUrl}${ApiConfig.apiPrefix}/devices/register',
        body: {
          'member_id': memberId,
          'token': token,
          'platform': _detectPlatform(),
        },
      );
    } catch (e) {
      print('Token registration failed: $e');
    }
  }

  static Future<void> unregister() async {
    if (_fcmToken == null) return;
    try {
      await ApiClient.post(
        '${ApiConfig.baseUrl}${ApiConfig.apiPrefix}/devices/unregister',
        body: {'token': _fcmToken},
      );
    } catch (_) {}
  }

  static String _detectPlatform() {
    // In real app: Platform.isIOS ? 'ios' : 'android'
    return 'android';
  }
}
