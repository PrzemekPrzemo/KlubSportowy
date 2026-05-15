class AppNotification {
  const AppNotification({
    required this.id,
    required this.title,
    required this.body,
    required this.createdAt,
    required this.read,
    this.deepLink,
  });

  final int id;
  final String title;
  final String body;
  final DateTime createdAt;
  final bool read;
  final String? deepLink;

  AppNotification copyWith({bool? read}) => AppNotification(
        id: id,
        title: title,
        body: body,
        createdAt: createdAt,
        read: read ?? this.read,
        deepLink: deepLink,
      );

  factory AppNotification.fromJson(Map<String, dynamic> json) =>
      AppNotification(
        id: json['id'] as int,
        title: json['title'] as String,
        body: json['body'] as String? ?? '',
        createdAt: DateTime.parse(json['created_at'] as String),
        read: json['read'] as bool? ?? false,
        deepLink: json['deep_link'] as String?,
      );
}
