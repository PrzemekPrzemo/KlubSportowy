/// Bazowy typ wyjątku aplikacji — łatwiej łapać i renderować.
sealed class AppException implements Exception {
  const AppException(this.message, {this.cause});
  final String message;
  final Object? cause;

  @override
  String toString() => '$runtimeType: $message';
}

class NetworkException extends AppException {
  const NetworkException(super.message, {super.cause});
}

class ApiException extends AppException {
  const ApiException(super.message, {this.statusCode, this.errorCode, super.cause});

  final int? statusCode;
  final String? errorCode;
}

class AuthException extends AppException {
  const AuthException(super.message, {super.cause});
}

class ValidationException extends AppException {
  const ValidationException(super.message, {this.fieldErrors = const {}});
  final Map<String, String> fieldErrors;
}
