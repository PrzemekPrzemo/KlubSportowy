enum FeeStatus { overdue, pending, paid }

class Fee {
  const Fee({
    required this.id,
    required this.title,
    required this.amount,
    required this.currency,
    required this.dueDate,
    required this.status,
    this.description,
    this.paidAt,
  });

  final int id;
  final String title;
  final double amount;
  final String currency;
  final DateTime dueDate;
  final FeeStatus status;
  final String? description;
  final DateTime? paidAt;

  factory Fee.fromJson(Map<String, dynamic> json) => Fee(
        id: json['id'] as int,
        title: json['title'] as String,
        amount: (json['amount'] as num).toDouble(),
        currency: json['currency'] as String? ?? 'PLN',
        dueDate: DateTime.parse(json['due_date'] as String),
        status: _parseStatus(json['status'] as String?),
        description: json['description'] as String?,
        paidAt: json['paid_at'] != null
            ? DateTime.parse(json['paid_at'] as String)
            : null,
      );

  static FeeStatus _parseStatus(String? raw) => switch (raw) {
        'paid' => FeeStatus.paid,
        'overdue' => FeeStatus.overdue,
        _ => FeeStatus.pending,
      };
}
