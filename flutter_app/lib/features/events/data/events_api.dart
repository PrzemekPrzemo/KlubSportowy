import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../config/api_config.dart';
import '../../../core/api_client.dart';
import '../../../core/api_client_provider.dart';
import '../domain/event.dart';

class EventsApi {
  EventsApi(this._client);
  final ApiClient _client;

  Future<List<ClubEvent>> list() async {
    if (ApiConfig.useMockData) {
      await Future<void>.delayed(const Duration(milliseconds: 350));
      final now = DateTime.now();
      return [
        ClubEvent(
          id: 1,
          title: 'Walne zebranie członków',
          startsAt: now.add(const Duration(days: 14)).copyWith(hour: 18),
          location: 'Sala konferencyjna klubu',
          description: 'Coroczne walne zebranie z głosowaniem nad budżetem.',
        ),
        ClubEvent(
          id: 2,
          title: 'Festyn rodzinny',
          startsAt: now.add(const Duration(days: 30)).copyWith(hour: 12),
          location: 'Park klubowy',
        ),
      ];
    }
    final resp = await _client.get<dynamic>(ApiEndpoints.events);
    final list = (resp.data as List?) ?? const [];
    return list
        .map((e) => ClubEvent.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}

final eventsApiProvider =
    Provider<EventsApi>((ref) => EventsApi(ref.watch(apiClientProvider)));

final eventsListProvider = FutureProvider.autoDispose<List<ClubEvent>>(
  (ref) => ref.watch(eventsApiProvider).list(),
);
