export const LEGAL_SLUGS = {
  TERMS: "terms",
  PRIVACY_POLICY: "privacy-policy",
  PRIVACY_AGREEMENT: "privacy-agreement",
  KVKK_AYDINLATMA: "kvkk-aydinlatma",
  KVKK_ACIK_RIZA: "kvkk-acik-riza",
  KVKK_BASVURU: "kvkk-basvuru",
  DISTANCE_SALES: "distance-sales",
  PRE_INFORMATION: "pre-information",
  DELIVERY_RETURN: "delivery-return",
  SELLER_TERMS: "seller-terms",
  PROHIBITED_PRODUCTS: "prohibited-products",
  SECOND_HAND_RULES: "second-hand-rules",
  COMMISSION_POLICY: "commission-policy",
  PAYOUT_INFO: "payout-info",
};

export const LEGAL_ROUTES = Object.fromEntries(
  Object.entries(LEGAL_SLUGS).map(([key, slug]) => [key, `/legal/${slug}`])
);

export const FOOTER_LEGAL_LINKS = [
  { slug: LEGAL_SLUGS.TERMS, label: "Şartlar ve Koşullar" },
  { slug: LEGAL_SLUGS.PRIVACY_POLICY, label: "Gizlilik Politikası" },
  { slug: LEGAL_SLUGS.PRIVACY_AGREEMENT, label: "Gizlilik Sözleşmesi" },
  { slug: LEGAL_SLUGS.KVKK_AYDINLATMA, label: "KVKK Aydınlatma Metni" },
  { slug: LEGAL_SLUGS.KVKK_ACIK_RIZA, label: "KVKK Açık Rıza Metni" },
  { slug: LEGAL_SLUGS.KVKK_BASVURU, label: "KVKK Başvuru Formu" },
  { slug: LEGAL_SLUGS.DISTANCE_SALES, label: "Mesafeli Satış Sözleşmesi" },
  { slug: LEGAL_SLUGS.PRE_INFORMATION, label: "Ön Bilgilendirme Formu" },
  { slug: LEGAL_SLUGS.DELIVERY_RETURN, label: "Teslimat ve İade Şartları" },
  { slug: LEGAL_SLUGS.SELLER_TERMS, label: "Satıcı Şartları ve Koşulları" },
  { slug: LEGAL_SLUGS.PROHIBITED_PRODUCTS, label: "Yasaklı Ürünler Politikası" },
  { slug: LEGAL_SLUGS.SECOND_HAND_RULES, label: "İkinci El İlan Kuralları" },
];

export const FOOTER_CORPORATE_LINKS = [
  { href: "/about", label: "Hakkımızda" },
  { href: "/salon-crm", label: "Salon CRM" },
  { href: "/contact", label: "İletişim" },
  { href: "/faq", label: "Sıkça Sorulan Sorular (SSS)" },
];

export const PROFILE_LEGAL_LINKS = FOOTER_LEGAL_LINKS.filter(
  (item) => item.slug !== LEGAL_SLUGS.SELLER_TERMS && item.slug !== LEGAL_SLUGS.PROHIBITED_PRODUCTS
);

export const SELLER_LEGAL_LINKS = [
  { slug: LEGAL_SLUGS.SELLER_TERMS, label: "Satıcı Şartları" },
  { slug: LEGAL_SLUGS.DELIVERY_RETURN, label: "Teslimat ve İade" },
  { slug: LEGAL_SLUGS.PROHIBITED_PRODUCTS, label: "Yasaklı Ürünler" },
  { slug: LEGAL_SLUGS.COMMISSION_POLICY, label: "Komisyon Politikası" },
  { slug: LEGAL_SLUGS.PAYOUT_INFO, label: "Hakediş Bilgileri" },
  { href: "/faq", label: "SSS" },
];

export const SIGNUP_REQUIRED_CONSENTS = [
  { slug: LEGAL_SLUGS.TERMS, linkLabel: "Şartlar ve Koşullar", label: "'ı okudum ve kabul ediyorum." },
  { slug: LEGAL_SLUGS.PRIVACY_POLICY, linkLabel: "Gizlilik Politikası", label: "'nı okudum." },
  { slug: LEGAL_SLUGS.KVKK_AYDINLATMA, linkLabel: "KVKK Aydınlatma Metni", label: "'ni okudum." },
];

export const SIGNUP_OPTIONAL_CONSENTS = [
  { key: "marketing_email", slug: LEGAL_SLUGS.KVKK_ACIK_RIZA, linkLabel: "Kampanya ve indirim e-postaları", label: " almak istiyorum.", required: false },
  { key: "marketing_sms", slug: LEGAL_SLUGS.KVKK_ACIK_RIZA, linkLabel: "SMS", label: " almak istiyorum.", required: false },
  { key: "marketing_push", slug: LEGAL_SLUGS.KVKK_ACIK_RIZA, linkLabel: "Push bildirim", label: " almak istiyorum.", required: false },
];

export const SELLER_REGISTER_REQUIRED_CONSENTS = [
  {
    key: "seller-register-terms",
    slugs: [LEGAL_SLUGS.SELLER_TERMS, LEGAL_SLUGS.PRIVACY_POLICY],
    links: [
      { slug: LEGAL_SLUGS.SELLER_TERMS, label: "Satıcı Şartları ve Koşulları" },
      { slug: LEGAL_SLUGS.PRIVACY_POLICY, label: "Gizlilik Politikası" },
    ],
    label: " metinlerini okudum ve kabul ediyorum.",
  },
  {
    key: "seller-register-kvkk",
    slugs: [LEGAL_SLUGS.KVKK_AYDINLATMA],
    links: [{ slug: LEGAL_SLUGS.KVKK_AYDINLATMA, label: "KVKK Aydınlatma Metni" }],
    label: "'ni okudum ve kabul ediyorum.",
  },
];

export const SELLER_REGISTER_OPTIONAL_CONSENTS = [
  { key: "commercial_email", slug: LEGAL_SLUGS.KVKK_ACIK_RIZA, linkLabel: "Ticari elektronik ileti", label: " almak istiyorum.", required: false },
];

export const CHECKOUT_REQUIRED_CONSENTS = [
  {
    key: "checkout-sales",
    slugs: [LEGAL_SLUGS.PRE_INFORMATION, LEGAL_SLUGS.DISTANCE_SALES],
    links: [
      { slug: LEGAL_SLUGS.PRE_INFORMATION, label: "Ön Bilgilendirme Formu" },
      { slug: LEGAL_SLUGS.DISTANCE_SALES, label: "Mesafeli Satış Sözleşmesi" },
    ],
    label: " metinlerini okudum ve kabul ediyorum.",
  },
  {
    key: "checkout-terms",
    slugs: [LEGAL_SLUGS.TERMS, LEGAL_SLUGS.PRIVACY_POLICY],
    links: [
      { slug: LEGAL_SLUGS.TERMS, label: "Şartlar ve Koşullar" },
      { slug: LEGAL_SLUGS.PRIVACY_POLICY, label: "Gizlilik Politikası" },
    ],
    label: " metinlerini okudum ve kabul ediyorum.",
  },
];

export const SECOND_HAND_REQUIRED_CONSENTS = [
  { slug: LEGAL_SLUGS.SECOND_HAND_RULES, linkLabel: "İkinci El İlan Kuralları", label: "'nı okudum ve kabul ediyorum." },
];

export const PRODUCT_DETAIL_LEGAL_LINKS = [
  { slug: LEGAL_SLUGS.DELIVERY_RETURN, label: "Teslimat ve İade Şartları" },
  { slug: LEGAL_SLUGS.DISTANCE_SALES, label: "Mesafeli Satış Sözleşmesi" },
  { slug: null, label: "Satıcı Bilgileri", anchor: "seller-info" },
  { slug: LEGAL_SLUGS.PRE_INFORMATION, label: "Ön Bilgilendirme Formu" },
];

export function legalPath(slug) {
  return `/legal/${slug}`;
}

export const LEGAL_SLUG_LIST = Object.values(LEGAL_SLUGS);
