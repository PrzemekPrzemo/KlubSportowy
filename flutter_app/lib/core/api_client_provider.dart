import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'api_client.dart';
import 'secure_storage.dart';

/// Provider singletona ApiClient. 401 → wymuszamy logout przez auth notifier.
final apiClientProvider = Provider<ApiClient>((ref) {
  final storage = ref.watch(secureStorageProvider);
  final client = ApiClient(
    Dio(),
    storage,
    onAuthFailed: () {
      // Lazy reset — auth notifier słucha i się logout-uje. Nie chcemy
      // circular dep, więc używamy keepAlive providera.
      ref.read(authFailedProvider.notifier).state++;
    },
  );
  return client;
});

/// Wewnętrzny licznik 401 — auth notifier słucha i wykonuje logout.
final authFailedProvider = StateProvider<int>((ref) => 0);
