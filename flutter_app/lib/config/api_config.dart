/// Konfiguracja API + tryb mock dla developmentu bez backendu.
class ApiConfig {
  ApiConfig._();

  /// Tryb mock — jeśli `true`, repozytoria zwracają hardcoded dane zamiast
  /// strzelać do prawdziwego API. Włączany domyślnie, żeby UI dało się
  /// developować bez backendu.
  ///
  /// Wyłącz przy buildzie: `flutter run --dart-define=USE_MOCK=false`.
  static const bool useMockData =
      bool.fromEnvironment('USE_MOCK', defaultValue: true);

  /// Base URL backendu. Domyślnie produkcyjny portal — można nadpisać:
  /// `flutter run --dart-define=API_BASE=https://staging.clubdesk.pl/api/mobile/v1`
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE',
    defaultValue: 'https://portal.clubdesk.pl/api/mobile/v1',
  );

  /// Timeout dla requestów HTTP.
  static const Duration connectTimeout = Duration(seconds: 10);
  static const Duration receiveTimeout = Duration(seconds: 15);
}

/// Pełna lista endpointów (zgodna z PR `claude/mobile-api-v1`).
class ApiEndpoints {
  ApiEndpoints._();

  // Auth
  static const String login = '/auth/login';
  static const String logout = '/auth/logout';
  static const String refresh = '/auth/refresh';
  static const String forgotPassword = '/auth/forgot-password';
  static const String selectClub = '/auth/select-club';

  // Member
  static const String me = '/me';
  static String memberProfile(int id) => '/members/$id';

  // Dashboard
  static const String dashboard = '/dashboard';

  // Trainings
  static const String trainings = '/trainings';
  static String trainingDetail(int id) => '/trainings/$id';
  static String trainingRsvp(int id) => '/trainings/$id/rsvp';

  // Fees
  static const String fees = '/fees';
  static String feeDetail(int id) => '/fees/$id';
  static String feeCheckout(int id) => '/fees/$id/checkout';

  // Events
  static const String events = '/events';

  // Notifications
  static const String notifications = '/notifications';
  static String notificationRead(int id) => '/notifications/$id/read';

  // Push
  static const String pushRegister = '/push/register';
}
