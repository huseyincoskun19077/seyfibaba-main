export interface NavItem {
  label: string;
  to: string;
  badge?: string;
}

export const primaryNavigation: NavItem[] = [
  { label: "Anasayfa", to: "/" },
  { label: "Urunler", to: "/products" },
  { label: "Kategoriler", to: "/products?view=categories" },
  { label: "Kampanyalar", to: "/products?highlight=discounted" },
  { label: "Markalar", to: "/products?view=brands" },
  { label: "Siparisler", to: "/siparisler" },
];

export const quickActions: NavItem[] = [
  { label: "Favoriler", to: "/favoriler" },
  { label: "Karsilastirma", to: "/karsilastir" },
  { label: "Sepet", to: "/sepet", badge: "3" },
];

export const accountNavigation: NavItem[] = [
  { label: "Genel Bakis", to: "/hesabim" },
  { label: "Siparislerim", to: "/siparisler" },
  { label: "Favorilerim", to: "/favoriler" },
  { label: "Karsilastirma", to: "/karsilastir" },
  { label: "Adresler", to: "/hesabim/adresler" },
  { label: "Profil Ayarlari", to: "/hesabim/profil" },
];

export const mobileNavigation: NavItem[] = [
  { label: "Anasayfa", to: "/" },
  { label: "Urunler", to: "/products" },
  { label: "Favoriler", to: "/favoriler" },
  { label: "Sepet", to: "/sepet" },
  { label: "Hesabim", to: "/hesabim" },
];

export const categoryNavigation: NavItem[] = [
  { label: "Berber Koltuklari", to: "/products?category=koltuklar" },
  { label: "Yikama Setleri", to: "/products?category=yikama-setleri" },
  { label: "Bekleme Gruplari", to: "/products?category=bekleme-gruplari" },
  { label: "Tezgah ve Bankolar", to: "/products?category=tezgahlar" },
  { label: "Dolap ve Raflar", to: "/products?category=dolaplar" },
  { label: "Salon Aksesuar", to: "/products?category=aksesuarlar" },
];

export const utilityHighlights = [
  "Ayni gun teklif hazirlama",
  "Turkiye geneli sevkiyat",
  "Kurumsal fatura ve proje destegi",
];

export const trustHighlights = [
  {
    title: "Hizli Sevkiyat",
    text: "Stokta olan urunlerde sureci hizlandiran operasyon yapisi.",
  },
  {
    title: "Proje Destegi",
    text: "Salon kurulumlari icin urun secimi ve teklif toplama destegi.",
  },
  {
    title: "Guvenli Odeme",
    text: "Iyzico ve alternatif odeme akislarina uygun checkout altyapisi.",
  },
];
