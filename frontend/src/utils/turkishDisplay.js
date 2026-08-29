/**
 * Eski veritabani / iceriklerde ASCII kalan Turkce etiketleri goruntuleme icin duzeltir.
 * URL slug'lari ve urun adlarina uygulanmamali — yalnizca kullaniciya gosterilen etiketler.
 */
export function displayTurkishLabel(value) {
  if (typeof value !== "string" || !value.trim()) {
    return value || "";
  }

  return value
    .replace(/\bKuafor\b/g, "Kuaför")
    .replace(/\bkuafor\b/g, "kuaför")
    .replace(/\bUrunler\b/g, "Ürünler")
    .replace(/\burunler\b/g, "ürünler")
    .replace(/\bUrun\b/g, "Ürün")
    .replace(/\burun\b/g, "ürün")
    .replace(/\bGor\b/g, "Gör")
    .replace(/\bgor\b/g, "gör")
    .replace(/\bGoster\b/g, "Göster")
    .replace(/\bgoster\b/g, "göster")
    .replace(/\bMagaza\b/g, "Mağaza")
    .replace(/\bmagaza\b/g, "mağaza")
    .replace(/\bSatici\b/g, "Satıcı")
    .replace(/\bsatici\b/g, "satıcı")
    .replace(/\bKoltugu\b/g, "Koltuğu")
    .replace(/\bkoltugu\b/g, "koltuğu")
    .replace(/\bTezgahi\b/g, "Tezgahı")
    .replace(/\btezgahi\b/g, "tezgahı")
    .replace(/\bTum\b/g, "Tüm")
    .replace(/\btum\b/g, "tüm")
    .replace(/\bIlgili\b/g, "İlgili")
    .replace(/\bilgili\b/g, "ilgili")
    .replace(/\bDegerlendirme\b/g, "Değerlendirme")
    .replace(/\bdegerlendirme\b/g, "değerlendirme")
    .replace(/\bDevamini\b/g, "Devamını")
    .replace(/\bdevamini\b/g, "devamını");
}
