enum RsvpStatus { yes, maybe, no, unknown }

class Training {
  const Training({
    required this.id,
    required this.title,
    required this.startsAt,
    required this.endsAt,
    this.location,
    this.coach,
    this.description,
    this.participantsCount = 0,
    this.rsvp = RsvpStatus.unknown,
  });

  final int id;
  final String title;
  final DateTime startsAt;
  final DateTime endsAt;
  final String? location;
  final String? coach;
  final String? description;
  final int participantsCount;
  final RsvpStatus rsvp;

  Training copyWith({RsvpStatus? rsvp}) => Training(
        id: id,
        title: title,
        startsAt: startsAt,
        endsAt: endsAt,
        location: location,
        coach: coach,
        description: description,
        participantsCount: participantsCount,
        rsvp: rsvp ?? this.rsvp,
      );

  factory Training.fromJson(Map<String, dynamic> json) => Training(
        id: json['id'] as int,
        title: json['title'] as String,
        startsAt: DateTime.parse(json['starts_at'] as String),
        endsAt: DateTime.parse(json['ends_at'] as String),
        location: json['location'] as String?,
        coach: json['coach'] as String?,
        description: json['description'] as String?,
        participantsCount: json['participants_count'] as int? ?? 0,
        rsvp: _parseRsvp(json['rsvp'] as String?),
      );

  static RsvpStatus _parseRsvp(String? raw) => switch (raw) {
        'yes' => RsvpStatus.yes,
        'maybe' => RsvpStatus.maybe,
        'no' => RsvpStatus.no,
        _ => RsvpStatus.unknown,
      };

  static String rsvpToWire(RsvpStatus s) => switch (s) {
        RsvpStatus.yes => 'yes',
        RsvpStatus.maybe => 'maybe',
        RsvpStatus.no => 'no',
        RsvpStatus.unknown => 'unknown',
      };
}
