/// Mini-snapshot dla dashboard: liczniki i 3 najbliższe treningi.
class DashboardData {
  const DashboardData({
    required this.todayTrainings,
    required this.overdueFeesCount,
    required this.overdueFeesAmount,
    required this.overdueFeesCurrency,
    required this.unreadNotifications,
    required this.lastNotificationHeadline,
    this.statsLines = const [],
  });

  final List<DashboardTraining> todayTrainings;
  final int overdueFeesCount;
  final double overdueFeesAmount;
  final String overdueFeesCurrency;
  final int unreadNotifications;
  final String? lastNotificationHeadline;
  final List<String> statsLines;

  factory DashboardData.fromJson(Map<String, dynamic> json) => DashboardData(
        todayTrainings: (json['today_trainings'] as List? ?? [])
            .map((e) =>
                DashboardTraining.fromJson(e as Map<String, dynamic>))
            .toList(),
        overdueFeesCount: json['overdue_fees_count'] as int? ?? 0,
        overdueFeesAmount:
            (json['overdue_fees_amount'] as num?)?.toDouble() ?? 0.0,
        overdueFeesCurrency:
            json['overdue_fees_currency'] as String? ?? 'PLN',
        unreadNotifications: json['unread_notifications'] as int? ?? 0,
        lastNotificationHeadline:
            json['last_notification_headline'] as String?,
        statsLines: (json['stats_lines'] as List? ?? [])
            .map((e) => e.toString())
            .toList(),
      );
}

class DashboardTraining {
  const DashboardTraining({
    required this.id,
    required this.title,
    required this.startsAt,
    this.location,
  });

  final int id;
  final String title;
  final DateTime startsAt;
  final String? location;

  factory DashboardTraining.fromJson(Map<String, dynamic> json) =>
      DashboardTraining(
        id: json['id'] as int,
        title: json['title'] as String,
        startsAt: DateTime.parse(json['starts_at'] as String),
        location: json['location'] as String?,
      );
}
