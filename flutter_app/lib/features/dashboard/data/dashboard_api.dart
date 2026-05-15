import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../config/api_config.dart';
import '../../../core/api_client.dart';
import '../../../core/api_client_provider.dart';
import '../domain/dashboard_state.dart';

class DashboardApi {
  DashboardApi(this._client);
  final ApiClient _client;

  Future<DashboardData> fetch() async {
    if (ApiConfig.useMockData) {
      await Future<void>.delayed(const Duration(milliseconds: 400));
      final today = DateTime.now();
      return DashboardData(
        todayTrainings: [
          DashboardTraining(
            id: 1,
            title: 'Trening seniorów',
            startsAt: today.copyWith(hour: 18, minute: 0),
            location: 'Hala główna',
          ),
          DashboardTraining(
            id: 2,
            title: 'Sparing — Sokół Kraków',
            startsAt: today.add(const Duration(days: 1)).copyWith(hour: 19),
            location: 'Boisko nr 2',
          ),
        ],
        overdueFeesCount: 2,
        overdueFeesAmount: 320.0,
        overdueFeesCurrency: 'PLN',
        unreadNotifications: 3,
        lastNotificationHeadline: 'Nowy plan treningowy na maj',
        statsLines: [
          'Frekwencja: 87% (sezon 2024/25)',
          'Twoje gole: 12 • asysty: 5',
        ],
      );
    }
    final resp =
        await _client.get<Map<String, dynamic>>(ApiEndpoints.dashboard);
    return DashboardData.fromJson(resp.data!);
  }
}

final dashboardApiProvider =
    Provider<DashboardApi>((ref) => DashboardApi(ref.watch(apiClientProvider)));

final dashboardProvider = FutureProvider.autoDispose<DashboardData>((ref) {
  return ref.watch(dashboardApiProvider).fetch();
});
