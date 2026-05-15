import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Wrapper na flutter_secure_storage. Trzyma tokeny w platformowym keychainie.
class SecureStorage {
  SecureStorage([FlutterSecureStorage? storage])
      : _storage = storage ??
            const FlutterSecureStorage(
              aOptions: AndroidOptions(encryptedSharedPreferences: true),
              iOptions: IOSOptions(
                accessibility: KeychainAccessibility.first_unlock,
              ),
            );

  final FlutterSecureStorage _storage;

  static const _kAccessToken = 'access_token';
  static const _kRefreshToken = 'refresh_token';
  static const _kClubId = 'club_id';
  static const _kMemberId = 'member_id';

  Future<String?> getAccessToken() => _storage.read(key: _kAccessToken);
  Future<String?> getRefreshToken() => _storage.read(key: _kRefreshToken);
  Future<String?> getClubId() => _storage.read(key: _kClubId);
  Future<String?> getMemberId() => _storage.read(key: _kMemberId);

  Future<void> saveTokens({
    required String accessToken,
    String? refreshToken,
  }) async {
    await _storage.write(key: _kAccessToken, value: accessToken);
    if (refreshToken != null) {
      await _storage.write(key: _kRefreshToken, value: refreshToken);
    }
  }

  Future<void> saveSession({
    required String clubId,
    required String memberId,
  }) async {
    await _storage.write(key: _kClubId, value: clubId);
    await _storage.write(key: _kMemberId, value: memberId);
  }

  Future<void> clear() async {
    await _storage.deleteAll();
  }
}

final secureStorageProvider = Provider<SecureStorage>((ref) => SecureStorage());
