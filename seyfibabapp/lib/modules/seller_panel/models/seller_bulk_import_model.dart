class SellerBulkImportModel {
  SellerBulkImportModel({
    required this.id,
    required this.status,
    required this.totalRows,
    required this.processedRows,
    required this.successCount,
    required this.errorCount,
    required this.originalName,
    required this.errorLog,
    required this.createdAt,
  });

  final int id;
  final String status;
  final int totalRows;
  final int processedRows;
  final int successCount;
  final int errorCount;
  final String originalName;
  final List<String> errorLog;
  final String createdAt;

  bool get isDone =>
      status == 'completed' || status == 'failed' || status == 'partial';

  factory SellerBulkImportModel.fromMap(Map<String, dynamic> map) {
    final log = map['error_log'];
    List<String> errors = const [];
    if (log is List) {
      errors = log.map((e) => '$e').toList();
    } else if (log is String && log.isNotEmpty) {
      errors = [log];
    }
    return SellerBulkImportModel(
      id: int.tryParse('${map['id']}') ?? 0,
      status: '${map['status'] ?? ''}',
      totalRows: int.tryParse('${map['total_rows'] ?? 0}') ?? 0,
      processedRows: int.tryParse('${map['processed_rows'] ?? 0}') ?? 0,
      successCount: int.tryParse('${map['success_count'] ?? 0}') ?? 0,
      errorCount: int.tryParse('${map['error_count'] ?? 0}') ?? 0,
      originalName: '${map['original_name'] ?? map['file_name'] ?? ''}',
      errorLog: errors,
      createdAt: '${map['created_at'] ?? ''}',
    );
  }
}
