import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../config/api_config.dart';
import '../../../core/api_client.dart';
import '../../../core/api_client_provider.dart';
import '../../../core/exceptions.dart';
import '../domain/member.dart';

class LoginResult {
  const LoginResult({
    required this.accessToken,
    this.refreshToken,
    this.member,
    this.clubs = const [],
  });

  /// Wiele klubów → `member == null`, `clubs.isNotEmpty`.
  /// Jeden klub → `member != null`.
  final String accessToken;
  final String? refreshToken;
  final Member? member;
  final List<Club> clubs;

  bool get needsClubChoice => member == null && clubs.isNotEmpty;
}

class AuthApi {
  AuthApi(this._client);
  final ApiClient _client;

  Future<LoginResult> login({
    required String email,
    required String password,
  }) async {
    if (ApiConfig.useMockData) {
      await Future<void>.delayed(const Duration(milliseconds: 500));
      if (email == 'fail@test.com') {
        throw const AuthException('Nieprawidłowy email lub hasło');
      }
      return LoginResult(
        accessToken: 'mock-access-token',
        refreshToken: 'mock-refresh-token',
        member: _mockMember(),
      );
    }

    final resp = await _client.post<Map<String, dynamic>>(
      ApiEndpoints.login,
      data: {'email': email, 'password': password},
    );
    final data = resp.data ?? const {};
    final clubsRaw = data['clubs'] as List?;
    return LoginResult(
      accessToken: data['access_token'] as String,
      refreshToken: data['refresh_token'] as String?,
      member: data['member'] != null
          ? Member.fromJson(data['member'] as Map<String, dynamic>)
          : null,
      clubs: clubsRaw
              ?.map((e) => Club.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
    );
  }

  Future<LoginResult> selectClub({
    required String tempToken,
    required int clubId,
  }) async {
    if (ApiConfig.useMockData) {
      await Future<void>.delayed(const Duration(milliseconds: 300));
      return LoginResult(
        accessToken: 'mock-access-token-$clubId',
        refreshToken: 'mock-refresh-token',
        member: _mockMember(clubId: clubId),
      );
    }
    final resp = await _client.post<Map<String, dynamic>>(
      ApiEndpoints.selectClub,
      data: {'temp_token': tempToken, 'club_id': clubId},
    );
    final data = resp.data!;
    return LoginResult(
      accessToken: data['access_token'] as String,
      refreshToken: data['refresh_token'] as String?,
      member: Member.fromJson(data['member'] as Map<String, dynamic>),
    );
  }

  Future<Member> fetchMe() async {
    if (ApiConfig.useMockData) {
      await Future<void>.delayed(const Duration(milliseconds: 200));
      return _mockMember();
    }
    final resp = await _client.get<Map<String, dynamic>>(ApiEndpoints.me);
    return Member.fromJson(resp.data!);
  }

  Future<Member> updateProfile({
    required int memberId,
    String? phone,
    String? address,
  }) async {
    if (ApiConfig.useMockData) {
      await Future<void>.delayed(const Duration(milliseconds: 400));
      return _mockMember().copyWith(phone: phone, address: address);
    }
    final resp = await _client.patch<Map<String, dynamic>>(
      ApiEndpoints.memberProfile(memberId),
      data: {
        if (phone != null) 'phone': phone,
        if (address != null) 'address': address,
      },
    );
    return Member.fromJson(resp.data!);
  }

  Future<void> forgotPassword(String email) async {
    if (ApiConfig.useMockData) {
      await Future<void>.delayed(const Duration(milliseconds: 400));
      return;
    }
    await _client.post<void>(
      ApiEndpoints.forgotPassword,
      data: {'email': email},
    );
  }

  Future<void> logout() async {
    if (ApiConfig.useMockData) {
      return;
    }
    try {
      await _client.post<void>(ApiEndpoints.logout);
    } catch (_) {
      // ignore — i tak czyścimy storage lokalnie
    }
  }

  Member _mockMember({int clubId = 1}) => Member(
        id: 42,
        firstName: 'Jan',
        lastName: 'Kowalski',
        email: 'jan.kowalski@test.com',
        evidenceNumber: 'ZW-2024-042',
        sport: 'Piłka nożna',
        phone: '+48 600 100 200',
        address: 'ul. Sportowa 1, 00-001 Warszawa',
        club: Club(
          id: clubId,
          name: clubId == 1 ? 'KS Orły Warszawa' : 'AKS Sokół Kraków',
          brandColorHex: clubId == 1 ? '#1976D2' : '#D32F2F',
        ),
      );
}

final authApiProvider = Provider<AuthApi>((ref) {
  return AuthApi(ref.watch(apiClientProvider));
});
