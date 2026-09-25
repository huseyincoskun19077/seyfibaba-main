/** layout.js template zaten " | Kuaför Tedarik" ekler; tekrarı önle */
export function stripSiteSuffix(title) {
  if (!title) return title;
  return String(title)
    .replace(/\s*\|\s*Kuaför Tedarik(\s*Pazaryeri)?\s*$/i, "")
    .trim();
}

export function buildPageTitle(title) {
  return stripSiteSuffix(title);
}
