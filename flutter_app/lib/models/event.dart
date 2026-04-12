class SportEvent {
  final int id;
  final String name;
  final String eventDate;
  final String? location;
  final String type;
  final String status;
  final String? sportName;
  final String? description;
  final int? homeScore;
  final int? awayScore;

  SportEvent({
    required this.id,
    required this.name,
    required this.eventDate,
    this.location,
    required this.type,
    required this.status,
    this.sportName,
    this.description,
    this.homeScore,
    this.awayScore,
  });

  factory SportEvent.fromJson(Map<String, dynamic> json) {
    return SportEvent(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      eventDate: json['event_date'] ?? '',
      location: json['location'],
      type: json['type'] ?? 'wydarzenie',
      status: json['status'] ?? '',
      sportName: json['sport_name'],
      description: json['description'],
      homeScore: json['home_score'],
      awayScore: json['away_score'],
    );
  }
}
