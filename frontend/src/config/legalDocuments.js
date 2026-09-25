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
  { slug: LEGAL_SLUGS.TERMS, label: "Üyelik / Alıcı Sözleşmesi" },
  { slug: LEGAL_SLUGS.PRIVACY_POLICY, label: "Ticari Kimlik Beyanı (Alıcı)" },
  {
    slug: LEGAL_SLUGS.PRIVACY_AGREEMENT,
    label: "Kişisel Verilerin Korunmasına Yönelik Protokol (Satıcı)",
  },
  {
    slug: LEGAL_SLUGS.KVKK_AYDINLATMA,
    label: "Kişisel Verilerinizin Korunması ve İşlenmesi",
  },
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
  { href: "/yardim", label: "Sıkça Sorulan Sorular (SSS)" },
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
  { href: "/yardim", label: "SSS" },
];

export const SIGNUP_REQUIRED_CONSENTS = [
  { slug: LEGAL_SLUGS.TERMS, linkLabel: "Üyelik / Alıcı Sözleşmesi", label: "" },
  { slug: LEGAL_SLUGS.PRIVACY_POLICY, linkLabel: "Ticari Kimlik Beyanı", label: "" },
  {
    slug: LEGAL_SLUGS.KVKK_AYDINLATMA,
    linkLabel: "Kişisel Verilerinizin Korunması ve İşlenmesi",
    label: "",
  },
];

/** Üyelikte otomatik kabul — checkbox yok; belgeler tıklanabilir. */
export const SIGNUP_AUTO_ACCEPT_NOTICE = {
  prefix: "Bir hesap oluşturduğunuzda, Kuaför Tedarik'in ",
  links: [
    { slug: LEGAL_SLUGS.TERMS, label: "Üyelik / Alıcı Sözleşmesi" },
    { slug: LEGAL_SLUGS.PRIVACY_POLICY, label: "Ticari Kimlik Beyanı" },
    {
      slug: LEGAL_SLUGS.KVKK_AYDINLATMA,
      label: "Kişisel Verilerinizin Korunması ve İşlenmesi",
    },
  ],
  suffix: " metinlerini kabul etmiş olursunuz.",
};

export const SIGNUP_OPTIONAL_CONSENTS = [
  {
    key: "marketing_commercial",
    slug: LEGAL_SLUGS.KVKK_ACIK_RIZA,
    required: false,
    prefix:
      "Bana özel indirim kuponları, kampanyalar dahil olmak üzere tüm ticari elektronik iletilerin ",
    links: [
      { slug: LEGAL_SLUGS.KVKK_AYDINLATMA, label: "Aydınlatma Metni" },
    ],
    label:
      " kapsamında gönderilmesini ve kişisel verilerimin işlenmesini kabul ediyorum.",
  },
];

export const SELLER_REGISTER_REQUIRED_CONSENTS = [
  {
    key: "seller-register-kvkk-protocol",
    slugs: [LEGAL_SLUGS.PRIVACY_AGREEMENT],
    links: [
      {
        slug: LEGAL_SLUGS.PRIVACY_AGREEMENT,
        label: "Kişisel Verilerin Korunmasına Yönelik Protokol",
      },
    ],
    label: "'ü okudum ve onaylıyorum.",
  },
];

export const SELLER_REGISTER_OPTIONAL_CONSENTS = [];

export const CHECKOUT_REQUIRED_CONSENTS = [
  {
    key: "checkout-pre-information",
    slug: LEGAL_SLUGS.PRE_INFORMATION,
    dynamic: true,
    linkLabel: "Ön Bilgilendirme Koşulları",
    label: "'nı okudum, onaylıyorum.",
  },
  {
    key: "checkout-distance-sales",
    slug: LEGAL_SLUGS.DISTANCE_SALES,
    dynamic: true,
    linkLabel: "Ticari Nitelikli Mesafeli Satış Sözleşmesi",
    label: "'ni okudum, onaylıyorum.",
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
