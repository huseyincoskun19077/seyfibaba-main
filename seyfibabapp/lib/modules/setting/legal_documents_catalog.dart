class LegalDocumentItem {
  final String slug;
  final String title;

  const LegalDocumentItem({required this.slug, required this.title});
}

class LegalDocumentsCatalog {
  static const profileLinks = [
    LegalDocumentItem(slug: 'terms', title: 'Şartlar ve Koşullar'),
    LegalDocumentItem(slug: 'privacy-policy', title: 'Gizlilik Politikası'),
    LegalDocumentItem(slug: 'privacy-agreement', title: 'Gizlilik Sözleşmesi'),
    LegalDocumentItem(slug: 'kvkk-aydinlatma', title: 'KVKK Aydınlatma Metni'),
    LegalDocumentItem(slug: 'kvkk-acik-riza', title: 'KVKK Açık Rıza Metni'),
    LegalDocumentItem(slug: 'kvkk-basvuru', title: 'KVKK Başvuru Formu'),
    LegalDocumentItem(slug: 'distance-sales', title: 'Mesafeli Satış Sözleşmesi'),
    LegalDocumentItem(slug: 'pre-information', title: 'Ön Bilgilendirme Formu'),
    LegalDocumentItem(slug: 'delivery-return', title: 'Teslimat ve İade Şartları'),
    LegalDocumentItem(slug: 'second-hand-rules', title: 'İkinci El İlan Kuralları'),
  ];

  static const checkoutRequired = [
    LegalDocumentItem(slug: 'pre-information', title: 'Ön Bilgilendirme Formu'),
    LegalDocumentItem(slug: 'distance-sales', title: 'Mesafeli Satış Sözleşmesi'),
    LegalDocumentItem(slug: 'terms', title: 'Şartlar ve Koşullar'),
    LegalDocumentItem(slug: 'privacy-policy', title: 'Gizlilik Politikası'),
  ];
}
