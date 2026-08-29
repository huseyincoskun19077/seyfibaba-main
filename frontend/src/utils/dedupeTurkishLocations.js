/**
 * Ayni il/ilce adinin farkli yazimlari (i vs ı) yuzunden tekrar eden kayitlari birlestirir.
 */
export function dedupeTurkishLocations(items) {
  if (!Array.isArray(items)) {
    return [];
  }

  const byKey = new Map();

  for (const item of items) {
    const name = String(item?.name || "").trim();
    if (!name) {
      continue;
    }

    const key = name.toLocaleLowerCase("tr-TR");
    const turkishCharScore = (text) =>
      (text.match(/[ğüşıöçĞÜŞİÖÇ]/g) || []).length;

    if (!byKey.has(key)) {
      byKey.set(key, item);
      continue;
    }

    const existing = byKey.get(key);
    if (turkishCharScore(name) > turkishCharScore(String(existing?.name || ""))) {
      byKey.set(key, item);
    }
  }

  return Array.from(byKey.values()).sort((a, b) =>
    String(a.name).localeCompare(String(b.name), "tr-TR")
  );
}
