import 'exceptions.dart';

/// Sealed Result type — używamy gdy chcemy zwrócić błąd bez throw.
/// Domyślnie wolimy AsyncValue z Riverpod, ale Result przydaje się
/// dla repository methods które chcą zwrócić error data zamiast wyjątku.
sealed class Result<T> {
  const Result();

  factory Result.success(T value) = Success<T>;
  factory Result.failure(AppException exception) = Failure<T>;

  bool get isSuccess => this is Success<T>;
  bool get isFailure => this is Failure<T>;

  T? get valueOrNull => switch (this) {
        Success<T>(:final value) => value,
        Failure<T>() => null,
      };

  AppException? get exceptionOrNull => switch (this) {
        Success<T>() => null,
        Failure<T>(:final exception) => exception,
      };

  R when<R>({
    required R Function(T value) success,
    required R Function(AppException exception) failure,
  }) =>
      switch (this) {
        Success<T>(:final value) => success(value),
        Failure<T>(:final exception) => failure(exception),
      };
}

final class Success<T> extends Result<T> {
  const Success(this.value);
  final T value;
}

final class Failure<T> extends Result<T> {
  const Failure(this.exception);
  final AppException exception;
}
