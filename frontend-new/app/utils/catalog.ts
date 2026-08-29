import { resolveMediaUrl } from "~/utils/media";

export interface CatalogProduct {
  id: number;
  name: string;
  slug: string;
  brand: string;
  image: string;
  price: number;
  offerPrice?: number;
  category: string;
  stock: number;
  summary: string;
}

export interface OrderSummary {
  id: string;
  date: string;
  status: string;
  total: number;
  items: number;
}

export interface CompareFeature {
  label: string;
  left: string;
  right: string;
}

const baseProducts: CatalogProduct[] = [
  {
    id: 1,
    name: "Luna Pro Kuafor Koltugu",
    slug: "luna-pro-kuafor-koltugu",
    brand: "Seyfibaba Studio",
    image: "/uploads/custom-images/banko-4001-2026-05-09-02-09-23-5754.jpg",
    price: 18490,
    offerPrice: 16990,
    category: "Koltuklar",
    stock: 8,
    summary: "Hidrolik ayakli, premium deri dokulu, yogun kullanima uygun salon koltugu.",
  },
  {
    id: 2,
    name: "Nova Wash Yikama Seti",
    slug: "nova-wash-yikama-seti",
    brand: "Seyfibaba Studio",
    image: "/uploads/custom-images/yikama-seti5001-2026-05-09-05-10-29-9496.jpg",
    price: 24990,
    offerPrice: 22990,
    category: "Yikama Setleri",
    stock: 4,
    summary: "Seramik hazneli, ergonomik boyun destekli modern yikama unitesi.",
  },
  {
    id: 3,
    name: "Zen Bekleme Koltugu",
    slug: "zen-bekleme-koltugu",
    brand: "Seyfibaba Studio",
    image: "/uploads/custom-images/bekleme-koltuklari5001-2026-05-11-11-07-16-4663.jpg",
    price: 12990,
    category: "Bekleme Gruplari",
    stock: 11,
    summary: "Dar alanlara uygun kompakt bekleme koltugu. Kolay temizlenen kumas yuzey.",
  },
  {
    id: 4,
    name: "Aura Laborant Dolabi",
    slug: "aura-laborant-dolabi",
    brand: "Seyfibaba Studio",
    image: "/uploads/custom-images/laborant-dolabi301-2026-05-10-10-11-23-1261.jpg",
    price: 10990,
    category: "Dolaplar",
    stock: 7,
    summary: "Cekmeceli ve rafli duzen saglayan laborant dolabi. Neme dayanikli govde.",
  },
];

export const formatPrice = (value: number) =>
  new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency: "TRY",
    maximumFractionDigits: 0,
  }).format(value);

export const getCatalogProducts = (imageBase: string) =>
  baseProducts.map((product) => ({
    ...product,
    image: resolveMediaUrl(product.image, imageBase),
  }));

export const getSampleOrders = (): OrderSummary[] => [
  { id: "SF-10482", date: "11 Mayis 2026", status: "Hazirlaniyor", total: 24880, items: 2 },
  { id: "SF-10410", date: "03 Mayis 2026", status: "Kargoya Verildi", total: 12990, items: 1 },
  { id: "SF-10377", date: "28 Nisan 2026", status: "Teslim Edildi", total: 18490, items: 1 },
];

export const getCompareFeatures = (): CompareFeature[] => [
  { label: "Fiyat", left: "16.990 TL", right: "22.990 TL" },
  { label: "Teslimat", left: "7 gun", right: "10 gun" },
  { label: "Garanti", left: "2 yil", right: "2 yil" },
  { label: "Malzeme", left: "Premium deri", right: "Seramik + metal" },
  { label: "Kullanim", left: "Sac kesim", right: "Sac yikama" },
];
