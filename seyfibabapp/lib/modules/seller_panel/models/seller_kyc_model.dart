class SellerKycBundle {
  SellerKycBundle({
    required this.documents,
    required this.kycStatus,
    required this.iban,
    required this.taxNumber,
    this.sellerType = '',
    this.tcIdentity = '',
    this.address = '',
    this.taxOffice = '',
    this.legalCompanyTitle = '',
    this.message,
  });

  final List<SellerKycDocument> documents;
  final String kycStatus;
  final String iban;
  final String taxNumber;
  final String sellerType;
  final String tcIdentity;
  final String address;
  final String taxOffice;
  final String legalCompanyTitle;
  final String? message;

  factory SellerKycBundle.fromMap(Map<String, dynamic> map) {
    final status = map['status'];
    final statusMap =
        status is Map ? Map<String, dynamic>.from(status) : <String, dynamic>{};
    final docs = map['documents'];
    return SellerKycBundle(
      documents: docs is List
          ? docs
              .whereType<Map>()
              .map(
                (e) => SellerKycDocument.fromMap(Map<String, dynamic>.from(e)),
              )
              .toList()
          : const [],
      kycStatus:
          '${statusMap['kyc_status'] ?? map['kyc_status'] ?? 'not_submitted'}',
      iban: '${statusMap['iban'] ?? ''}',
      taxNumber: '${statusMap['tax_number'] ?? ''}',
      sellerType: '${statusMap['seller_type'] ?? ''}',
      tcIdentity: '${statusMap['tc_identity'] ?? ''}',
      address: '${statusMap['address'] ?? ''}',
      taxOffice: '${statusMap['tax_office'] ?? ''}',
      legalCompanyTitle: '${statusMap['legal_company_title'] ?? ''}',
      message: map['message']?.toString(),
    );
  }
}

class SellerKycDocument {
  SellerKycDocument({
    required this.id,
    required this.documentType,
    required this.originalName,
    required this.status,
    required this.adminNote,
    required this.createdAt,
  });

  final int id;
  final String documentType;
  final String originalName;
  final String status;
  final String adminNote;
  final String createdAt;

  String get typeLabel => switch (documentType) {
        'tax_certificate' => 'Vergi Levhası',
        'identity_front' => 'Kimlik Ön',
        'identity_back' => 'Kimlik Arka',
        'address_proof' => 'Adres Belgesi',
        'bank_statement' => 'Banka Özeti',
        'iban_document' => 'IBAN Belgesi',
        _ => documentType,
      };

  String get statusLabel => switch (status) {
        'pending' => 'Bekliyor',
        'approved' => 'Onaylı',
        'rejected' => 'Reddedildi',
        _ => status,
      };

  bool get canDelete => status == 'pending';

  factory SellerKycDocument.fromMap(Map<String, dynamic> map) {
    return SellerKycDocument(
      id: int.tryParse('${map['id']}') ?? 0,
      documentType: '${map['document_type'] ?? ''}',
      originalName: '${map['original_name'] ?? ''}',
      status: '${map['status'] ?? ''}',
      adminNote: '${map['admin_note'] ?? ''}',
      createdAt: '${map['created_at'] ?? ''}',
    );
  }
}
