class SellerAdminMessage {
  SellerAdminMessage({
    required this.id,
    required this.subject,
    required this.message,
    required this.createdAt,
  });

  final int id;
  final String subject;
  final String message;
  final String createdAt;

  factory SellerAdminMessage.fromMap(Map<String, dynamic> map) {
    return SellerAdminMessage(
      id: int.tryParse('${map['id']}') ?? 0,
      subject: '${map['subject'] ?? ''}',
      message: '${map['message'] ?? ''}',
      createdAt: '${map['created_at'] ?? ''}',
    );
  }
}
