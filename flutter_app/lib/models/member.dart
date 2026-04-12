class Member {
  final int id;
  final int clubId;
  final String memberNumber;
  final String firstName;
  final String lastName;
  final String? email;
  final String? phone;
  final String? gender;
  final String? birthDate;
  final String joinDate;
  final String status;

  Member({
    required this.id,
    required this.clubId,
    required this.memberNumber,
    required this.firstName,
    required this.lastName,
    this.email,
    this.phone,
    this.gender,
    this.birthDate,
    required this.joinDate,
    required this.status,
  });

  String get fullName => '$firstName $lastName';

  factory Member.fromJson(Map<String, dynamic> json) {
    return Member(
      id: json['id'] ?? 0,
      clubId: json['club_id'] ?? 0,
      memberNumber: json['member_number'] ?? '',
      firstName: json['first_name'] ?? '',
      lastName: json['last_name'] ?? '',
      email: json['email'],
      phone: json['phone'],
      gender: json['gender'],
      birthDate: json['birth_date'],
      joinDate: json['join_date'] ?? '',
      status: json['status'] ?? 'aktywny',
    );
  }
}

class Club {
  final int id;
  final String name;
  final String? shortName;
  final String? city;
  final String? email;

  Club({required this.id, required this.name, this.shortName, this.city, this.email});

  factory Club.fromJson(Map<String, dynamic> json) {
    return Club(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      shortName: json['short_name'],
      city: json['city'],
      email: json['email'],
    );
  }
}
