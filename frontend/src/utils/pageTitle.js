/** layout.js template zaten " | Seyfibaba" ekler; tekrarı önle */
export function stripSiteSuffix(title) {
  if (!title) return title;
  return String(title)
    .replace(/\s*\|\s*Seyfibaba(\s*Pazaryeri)?\s*$/i, "")
    .trim();
}

export function buildPageTitle(title) {
  return stripSiteSuffix(title);
}
