import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../config/api_config.dart';
import 'exceptions.dart';
import 'secure_storage.dart';

/// Dio-based klient HTTP z interceptors:
///  - auth token injection (Bearer)
///  - logging w debug
///  - 401 refresh-and-retry (best effort)
///  - mapping na typed AppException
class ApiClient {
  ApiClient(this._dio, this._storage, {VoidCallback? onAuthFailed})
      : _onAuthFailed = onAuthFailed {
    _dio
      ..options.baseUrl = ApiConfig.apiBaseUrl
      ..options.connectTimeout = ApiConfig.connectTimeout
      ..options.receiveTimeout = ApiConfig.receiveTimeout
      ..options.headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      };

    _dio.interceptors.add(_AuthInterceptor(_storage, _dio, _onAuthFailed));
  }

  final Dio _dio;
  final SecureStorage _storage;
  final VoidCallback? _onAuthFailed;

  Dio get dio => _dio;

  Future<Response<T>> get<T>(String path, {Map<String, dynamic>? query}) =>
      _wrap(() => _dio.get<T>(path, queryParameters: query));

  Future<Response<T>> post<T>(String path, {Object? data, Map<String, dynamic>? query}) =>
      _wrap(() => _dio.post<T>(path, data: data, queryParameters: query));

  Future<Response<T>> patch<T>(String path, {Object? data}) =>
      _wrap(() => _dio.patch<T>(path, data: data));

  Future<Response<T>> delete<T>(String path) => _wrap(() => _dio.delete<T>(path));

  Future<Response<T>> _wrap<T>(Future<Response<T>> Function() request) async {
    try {
      return await request();
    } on DioException catch (e) {
      throw _mapDioError(e);
    }
  }

  AppException _mapDioError(DioException e) {
    if (e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.type == DioExceptionType.sendTimeout ||
        e.type == DioExceptionType.connectionError) {
      return NetworkException('Brak połączenia z serwerem', cause: e);
    }

    final status = e.response?.statusCode;
    final body = e.response?.data;
    String? message;
    String? errorCode;

    if (body is Map<String, dynamic>) {
      message = body['message']?.toString() ?? body['error']?.toString();
      errorCode = body['code']?.toString();
    }

    if (status == 401) {
      return AuthException(message ?? 'Sesja wygasła', cause: e);
    }

    if (status == 422 && body is Map<String, dynamic>) {
      final errors = body['errors'];
      final fieldErrors = <String, String>{};
      if (errors is Map) {
        errors.forEach((k, v) {
          fieldErrors[k.toString()] =
              v is List ? v.first.toString() : v.toString();
        });
      }
      return ValidationException(message ?? 'Błąd walidacji',
          fieldErrors: fieldErrors);
    }

    return ApiException(
      message ?? 'Wystąpił błąd serwera (HTTP $status)',
      statusCode: status,
      errorCode: errorCode,
      cause: e,
    );
  }
}

typedef VoidCallback = void Function();

class _AuthInterceptor extends Interceptor {
  _AuthInterceptor(this._storage, this._dio, this._onAuthFailed);

  final SecureStorage _storage;
  final Dio _dio;
  final VoidCallback? _onAuthFailed;
  bool _refreshing = false;

  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    if (!options.headers.containsKey('Authorization')) {
      final token = await _storage.getAccessToken();
      if (token != null && token.isNotEmpty) {
        options.headers['Authorization'] = 'Bearer $token';
      }
    }
    handler.next(options);
  }

  @override
  Future<void> onError(
    DioException err,
    ErrorInterceptorHandler handler,
  ) async {
    final status = err.response?.statusCode;
    final isRefreshCall = err.requestOptions.path.contains('/auth/refresh');

    if (status == 401 && !_refreshing && !isRefreshCall) {
      _refreshing = true;
      try {
        final refresh = await _storage.getRefreshToken();
        if (refresh == null || refresh.isEmpty) {
          _onAuthFailed?.call();
          return handler.next(err);
        }

        final resp = await _dio.post<Map<String, dynamic>>(
          '/auth/refresh',
          data: {'refresh_token': refresh},
          options: Options(headers: {'Authorization': null}),
        );
        final newAccess = resp.data?['access_token'] as String?;
        final newRefresh = resp.data?['refresh_token'] as String?;
        if (newAccess == null) {
          _onAuthFailed?.call();
          return handler.next(err);
        }
        await _storage.saveTokens(
          accessToken: newAccess,
          refreshToken: newRefresh,
        );

        // Retry oryginalnego requestu z nowym tokenem
        final retryOpts = err.requestOptions;
        retryOpts.headers['Authorization'] = 'Bearer $newAccess';
        final retry = await _dio.fetch<dynamic>(retryOpts);
        return handler.resolve(retry);
      } on DioException {
        _onAuthFailed?.call();
      } finally {
        _refreshing = false;
      }
    }

    handler.next(err);
  }
}
