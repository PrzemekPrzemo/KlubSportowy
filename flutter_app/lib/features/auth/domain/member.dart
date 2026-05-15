/// Klub do którego przypisany jest zawodnik. Po zalogowaniu user wybiera
/// jeden (jeśli ma multi-club identity).
class Club {
  const Club({
    required this.id,
    required this.name,
    this.logoUrl,
    this.brandColorHex,
  });

  final int id;
  final String name;
  final String? logoUrl;
  final String? brandColorHex;

  factory Club.fromJson(Map<String, dynamic> json) => Club(
        id: json['id'] as int,
        name: json['name'] as String,
        logoUrl: json['logo_url'] as String?,
        brandColorHex: json['brand_color'] as String?,
      );
}

/// Zawodnik / członek klubu.
class Member {
  const Member({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.email,
    required this.club,
    this.evidenceNumber,
    this.sport,
    this.phone,
    this.address,
    this.avatarUrl,
  });

  final int id;
  final String firstName;
  final String lastName;
  final String email;
  final Club club;
  final String? evidenceNumber;
  final String? sport;
  final String? phone;
  final String? address;
  final String? avatarUrl;

  String get fullName => '$firstName $lastName';

  String get initials {
    final f = firstName.isNotEmpty ? firstName[0] : '';
    final l = lastName.isNotEmpty ? lastName[0] : '';
    return '$f$l'.toUpperCase();
  }

  factory Member.fromJson(Map<String, dynamic> json) => Member(
        id: json['id'] as int,
        firstName: json['first_name'] as String? ?? '',
        lastName: json['last_name'] as String? ?? '',
        email: json['email'] as String? ?? '',
        evidenceNumber: json['evidence_number'] as String?,
        sport: json['sport'] as String?,
        phone: json['phone'] as String?,
        address: json['address'] as String?,
        avatarUrl: json['avatar_url'] as String?,
        club: Club.fromJson(json['club'] as Map<String, dynamic>),
      );

  Member copyWith({
    String? phone,
    String? address,
  }) =>
      Member(
        id: id,
        firstName: firstName,
        lastName: lastName,
        email: email,
        club: club,
        evidenceNumber: evidenceNumber,
        sport: sport,
        phone: phone ?? this.phone,
        address: address ?? this.address,
        avatarUrl: avatarUrl,
      );
}
