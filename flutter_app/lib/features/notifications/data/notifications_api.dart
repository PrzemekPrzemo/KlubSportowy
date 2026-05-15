import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../config/api_config.dart';
import '../../../core/api_client.dart';
import '../../../core/api_client_provider.dart';
import '../domain/notification.dart';

class NotificationsApi {
  NotificationsApi(this._client);
  final ApiClient _client;

  Future<List<AppNotification>> list() async {
    if (ApiConfig.useMockData) {
      await Future<void>.delayed(const Duration(milliseconds: 300));
      final now = DateTime.now();
      return [
        AppNotification(
          id: 1,
          title: 'Nowy plan treningowy na maj',
          body: 'Sprawdź harmonogram treningów na maj 2024.',
          createdAt: now.subtract(const Duration(hours: 3)),
          read: false,
        ),
        AppNotification(
          id: 2,
          title: 'Składka kwietniowa — przypomnienie',
          body: 'Termin płatności minął 5 dni temu.',
          createdAt: now.subtract(const Duration(days: 1)),
          read: false,
          deepLink: '/fees/1',
        ),
        AppNotification(
          id: 3,
          title: 'Sparing zatwierdzony',
          body: 'Sparing z Sokołem Kraków w środę 19:00.',
          createdAt: now.subtract(const Duration(days: 4)),
          read: true,
        ),
      ];
    }
    final resp = await _client.get<dynamic>(ApiEndpoints.notifications);
    final list = (resp.data as List?) ?? const [];
    return list
        .map((e) => AppNotification.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<void> markRead(int id) async {
    if (ApiConfig.useMockData) return;
    await _client.post<void>(ApiEndpoints.notificationRead(id));
  }
}

final notificationsApiProvider = Provider<NotificationsApi>(
  (ref) => NotificationsApi(ref.watch(apiClientProvider)),
);

final notificationsListProvider =
    FutureProvider.autoDispose<List<AppNotification>>(
  (ref) => ref.watch(notificationsApiProvider).list(),
);
