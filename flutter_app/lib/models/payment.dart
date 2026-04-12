class Payment {
  final int id;
  final double amount;
  final String paymentDate;
  final int periodYear;
  final int? periodMonth;
  final String method;
  final String? feeName;
  final String? sportName;
  final String? firstName;
  final String? lastName;

  Payment({
    required this.id,
    required this.amount,
    required this.paymentDate,
    required this.periodYear,
    this.periodMonth,
    required this.method,
    this.feeName,
    this.sportName,
    this.firstName,
    this.lastName,
  });

  factory Payment.fromJson(Map<String, dynamic> json) {
    return Payment(
      id: json['id'] ?? 0,
      amount: double.tryParse(json['amount']?.toString() ?? '0') ?? 0,
      paymentDate: json['payment_date'] ?? '',
      periodYear: json['period_year'] ?? 0,
      periodMonth: json['period_month'],
      method: json['method'] ?? '',
      feeName: json['fee_name'],
      sportName: json['sport_name'],
      firstName: json['first_name'],
      lastName: json['last_name'],
    );
  }
}
