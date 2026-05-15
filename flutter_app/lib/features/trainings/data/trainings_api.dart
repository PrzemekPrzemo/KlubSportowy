import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../config/api_config.dart';
import '../../../core/api_client.dart';
import '../../../core/api_client_provider.dart';
import '../domain/training.dart';

class TrainingsApi {
  TrainingsApi(this._client);
  final ApiClient _client;

  Future<List<Training>> list() async {
    if (ApiConfig.useMockData) {
      await Future<void>.delayed(const Duration(milliseconds: 400));
      final now = DateTime.now();
      return [
        Training(
          id: 1,
          title: 'Trening seniorów',
          startsAt: now.copyWith(hour: 18, minute: 0),
          endsAt: now.copyWith(hour: 20, minute: 0),
          location: 'Hala główna',
          coach: 'Marek Nowak',
          description: 'Trening taktyczny + sparing kontrolny.',
          participantsCount: 18,
        ),
        Training(
          id: 2,
          title: 'Sparing — Sokół Kraków',
          startsAt: now.add(const Duration(days: 1)).copyWith(hour: 19),
          endsAt: now.add(const Duration(days: 1)).copyWith(hour: 21),
          location: 'Boisko nr 2',
          coach: 'Marek Nowak',
          participantsCount: 22,
          rsvp: RsvpStatus.yes,
        ),
        Training(
          id: 3,
          title: 'Trening siłowy',
          startsAt: now.add(const Duration(days: 3)).copyWith(hour: 17),
          endsAt: now.add(const Duration(days: 3)).copyWith(hour: 18, minute: 30),
          location: 'Siłownia klubowa',
          coach: 'Anna Wiśniewska',
          participantsCount: 12,
        ),
      ];
    }
    final resp = await _client.get<dynamic>(ApiEndpoints.trainings);
    final list = (resp.data as List?) ?? const [];
    return list
        .map((e) => Training.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<Training> detail(int id) async {
    if (ApiConfig.useMockData) {
      final all = await list();
      return all.firstWhere((t) => t.id == id);
    }
    final resp = await _client
        .get<Map<String, dynamic>>(ApiEndpoints.trainingDetail(id));
    return Training.fromJson(resp.data!);
  }

  Future<Training> setRsvp(int id, RsvpStatus status) async {
    if (ApiConfig.useMockData) {
      final t = await detail(id);
      return t.copyWith(rsvp: status);
    }
    final resp = await _client.post<Map<String, dynamic>>(
      ApiEndpoints.trainingRsvp(id),
      data: {'status': Training.rsvpToWire(status)},
    );
    return Training.fromJson(resp.data!);
  }
}

final trainingsApiProvider = Provider<TrainingsApi>(
  (ref) => TrainingsApi(ref.watch(apiClientProvider)),
);

final trainingsListProvider =
    FutureProvider.autoDispose<List<Training>>((ref) {
  return ref.watch(trainingsApiProvider).list();
});

final trainingDetailProvider =
    FutureProvider.autoDispose.family<Training, int>((ref, id) {
  return ref.watch(trainingsApiProvider).detail(id);
});
