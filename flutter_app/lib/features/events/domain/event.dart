class ClubEvent {
  const ClubEvent({
    required this.id,
    required this.title,
    required this.startsAt,
    this.endsAt,
    this.location,
    this.description,
  });

  final int id;
  final String title;
  final DateTime startsAt;
  final DateTime? endsAt;
  final String? location;
  final String? description;

  factory ClubEvent.fromJson(Map<String, dynamic> json) => ClubEvent(
        id: json['id'] as int,
        title: json['title'] as String,
        startsAt: DateTime.parse(json['starts_at'] as String),
        endsAt: json['ends_at'] != null
            ? DateTime.parse(json['ends_at'] as String)
            : null,
        location: json['location'] as String?,
        description: json['description'] as String?,
      );
}
