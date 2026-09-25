class LegalDocumentItem {
  final String slug;
  final String title;

  const LegalDocumentItem({required this.slug, required this.title});
}

class LegalDocumentsCatalog {
  static const profileLinks = [
    LegalDocumentItem(slug: 'terms', title: 'Üyelik / Alıcı Sözleşmesi'),
    LegalDocumentItem(slug: 'privacy-policy', title: 'Ticari Kimlik Beyanı / Gizlilik (Alıcı)'),
    LegalDocumentItem(
      slug: 'privacy-agreement',
      title: 'Kişisel Verilerin Korunmasına Yönelik Protokol (Satıcı)',
    ),
    LegalDocumentItem(
      slug: 'kvkk-aydinlatma',
      title: 'Kişisel Verilerinizin Korunması ve İşlenmesi',
    ),
    LegalDocumentItem(slug: 'kvkk-acik-riza', title: 'KVKK Açık Rıza Metni'),
    LegalDocumentItem(slug: 'kvkk-basvuru', title: 'KVKK Başvuru Formu'),
    LegalDocumentItem(
      slug: 'pre-information',
      title: 'Ön Bilgilendirme Koşulları (genel metin)',
    ),
    LegalDocumentItem(
      slug: 'distance-sales',
      title: 'Ticari Nitelikli Mesafeli Satış Sözleşmesi (genel metin)',
    ),
    LegalDocumentItem(slug: 'delivery-return', title: 'Teslimat ve İade Şartları'),
    LegalDocumentItem(slug: 'seller-terms', title: 'Satıcı Şartları ve Koşulları'),
    LegalDocumentItem(slug: 'prohibited-products', title: 'Yasaklı Ürünler Politikası'),
    LegalDocumentItem(slug: 'second-hand-rules', title: 'İkinci El İlan Kuralları'),
    LegalDocumentItem(slug: 'commission-policy', title: 'Komisyon Politikası'),
    LegalDocumentItem(slug: 'payout-info', title: 'Hakediş Bilgileri'),
  ];

  static const checkoutRequired = [
    LegalDocumentItem(slug: 'pre-information', title: 'Ön Bilgilendirme Formu'),
    LegalDocumentItem(slug: 'distance-sales', title: 'Mesafeli Satış Sözleşmesi'),
    LegalDocumentItem(slug: 'terms', title: 'Şartlar ve Koşullar'),
    LegalDocumentItem(slug: 'privacy-policy', title: 'Gizlilik Politikası'),
  ];

  static const sellerRegisterLinks = [
    LegalDocumentItem(
      slug: 'privacy-agreement',
      title: 'Kişisel Verilerin Korunmasına Yönelik Protokol',
    ),
  ];
}
