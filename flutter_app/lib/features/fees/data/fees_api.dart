import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../config/api_config.dart';
import '../../../core/api_client.dart';
import '../../../core/api_client_provider.dart';
import '../domain/fee.dart';

class FeesApi {
  FeesApi(this._client);
  final ApiClient _client;

  Future<List<Fee>> list() async {
    if (ApiConfig.useMockData) {
      await Future<void>.delayed(const Duration(milliseconds: 400));
      final now = DateTime.now();
      return [
        Fee(
          id: 1,
          title: 'Składka członkowska — kwiecień',
          amount: 150,
          currency: 'PLN',
          dueDate: now.subtract(const Duration(days: 5)),
          status: FeeStatus.overdue,
        ),
        Fee(
          id: 2,
          title: 'Składka członkowska — marzec',
          amount: 170,
          currency: 'PLN',
          dueDate: now.subtract(const Duration(days: 35)),
          status: FeeStatus.overdue,
        ),
        Fee(
          id: 3,
          title: 'Składka członkowska — maj',
          amount: 150,
          currency: 'PLN',
          dueDate: now.add(const Duration(days: 10)),
          status: FeeStatus.pending,
        ),
        Fee(
          id: 4,
          title: 'Obóz letni 2024',
          amount: 1200,
          currency: 'PLN',
          dueDate: now.add(const Duration(days: 30)),
          status: FeeStatus.pending,
        ),
        Fee(
          id: 5,
          title: 'Składka — luty',
          amount: 150,
          currency: 'PLN',
          dueDate: now.subtract(const Duration(days: 60)),
          status: FeeStatus.paid,
          paidAt: now.subtract(const Duration(days: 55)),
        ),
      ];
    }
    final resp = await _client.get<dynamic>(ApiEndpoints.fees);
    final list = (resp.data as List?) ?? const [];
    return list.map((e) => Fee.fromJson(e as Map<String, dynamic>)).toList();
  }

  /// Zwraca URL do checkoutu Stripe/P24. UI otwiera go w przeglądarce.
  Future<String> checkoutUrl(int feeId) async {
    if (ApiConfig.useMockData) {
      await Future<void>.delayed(const Duration(milliseconds: 200));
      return 'https://example.com/mock-checkout/$feeId';
    }
    final resp = await _client
        .post<Map<String, dynamic>>(ApiEndpoints.feeCheckout(feeId));
    return resp.data!['checkout_url'] as String;
  }
}

final feesApiProvider =
    Provider<FeesApi>((ref) => FeesApi(ref.watch(apiClientProvider)));

final feesListProvider = FutureProvider.autoDispose<List<Fee>>(
  (ref) => ref.watch(feesApiProvider).list(),
);
