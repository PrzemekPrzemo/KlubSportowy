class ApiConfig {
  // Change this to your server URL
  static const String baseUrl = 'http://localhost:8080';
  static const String apiPrefix = '/api/v1';
  static const Duration timeout = Duration(seconds: 15);

  static String get authLoginUrl => '$baseUrl$apiPrefix/auth/login';
  static String get membersUrl => '$baseUrl$apiPrefix/members';
  static String get eventsUrl => '$baseUrl$apiPrefix/events';
  static String get eventsUpcomingUrl => '$baseUrl$apiPrefix/events/upcoming';
  static String get paymentsUrl => '$baseUrl$apiPrefix/payments';
  static String get paymentsSummaryUrl => '$baseUrl$apiPrefix/payments/summary';
  static String get sportsUrl => '$baseUrl$apiPrefix/sports';
}
