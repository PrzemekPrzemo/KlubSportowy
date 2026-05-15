import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api_client_provider.dart';
import '../../../core/exceptions.dart';
import '../../../core/secure_storage.dart';
import '../../settings/data/settings_repository.dart';
import '../domain/auth_state.dart';
import '../domain/member.dart';
import 'auth_api.dart';

class AuthNotifier extends Notifier<AuthState> {
  late final AuthApi _api;
  late final SecureStorage _storage;

  @override
  AuthState build() {
    _api = ref.read(authApiProvider);
    _storage = ref.read(secureStorageProvider);

    // Reakcja na 401 z interceptora → logout.
    ref.listen<int>(authFailedProvider, (prev, next) {
      if (next > (prev ?? 0)) {
        logout(message: 'Sesja wygasła');
      }
    });

    // Bootstrap z tokenu w secure storage (async, nie blokuje build).
    Future.microtask(_restore);

    return const AuthLoading();
  }

  Future<void> _restore() async {
    final token = await _storage.getAccessToken();
    if (token == null || token.isEmpty) {
      state = const Unauthenticated();
      return;
    }
    try {
      final me = await _api.fetchMe();
      _applyBranding(me);
      state = Authenticated(member: me);
    } on AuthException {
      await _storage.clear();
      state = const Unauthenticated();
    } on AppException {
      // Network/server problem — pozwól userowi spróbować jeszcze raz w UI.
      state = const Unauthenticated();
    }
  }

  Future<void> login({
    required String email,
    required String password,
  }) async {
    state = const AuthLoading();
    try {
      final result = await _api.login(email: email, password: password);

      if (result.needsClubChoice) {
        state = MultiClubChoice(
          tempToken: result.accessToken,
          clubs: result.clubs,
        );
        return;
      }

      await _persistSession(result);
      _applyBranding(result.member!);
      state = Authenticated(member: result.member!);
    } on AppException catch (e) {
      state = Unauthenticated(message: e.message);
      rethrow;
    }
  }

  Future<void> selectClub(int clubId) async {
    final current = state;
    if (current is! MultiClubChoice) return;

    state = const AuthLoading();
    try {
      final result = await _api.selectClub(
        tempToken: current.tempToken,
        clubId: clubId,
      );
      await _persistSession(result);
      _applyBranding(result.member!);
      state = Authenticated(member: result.member!);
    } on AppException catch (e) {
      state = Unauthenticated(message: e.message);
      rethrow;
    }
  }

  Future<void> logout({String? message}) async {
    try {
      await _api.logout();
    } catch (_) {/* ignore */}
    await _storage.clear();
    state = Unauthenticated(message: message);
  }

  Future<void> refreshMe() async {
    if (state is! Authenticated) return;
    try {
      final me = await _api.fetchMe();
      state = Authenticated(member: me);
    } catch (_) {/* keep current */}
  }

  void updateMember(Member member) {
    state = Authenticated(member: member);
  }

  Future<void> _persistSession(LoginResult result) async {
    await _storage.saveTokens(
      accessToken: result.accessToken,
      refreshToken: result.refreshToken,
    );
    if (result.member != null) {
      await _storage.saveSession(
        clubId: result.member!.club.id.toString(),
        memberId: result.member!.id.toString(),
      );
    }
  }

  void _applyBranding(Member member) {
    final hex = member.club.brandColorHex;
    if (hex == null) return;
    final color = _parseHex(hex);
    if (color != null) {
      ref.read(settingsProvider.notifier).setSeedColor(color);
    }
  }

  Color? _parseHex(String hex) {
    var cleaned = hex.replaceAll('#', '');
    if (cleaned.length == 6) cleaned = 'FF$cleaned';
    final intVal = int.tryParse(cleaned, radix: 16);
    return intVal != null ? Color(intVal) : null;
  }
}

final authStateProvider =
    NotifierProvider<AuthNotifier, AuthState>(AuthNotifier.new);
