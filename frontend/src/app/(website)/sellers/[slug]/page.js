import { redirect } from "next/navigation";

export default async function SellersAliasPage({ params, searchParams }) {
  const { slug } = await params;
  const sp = await searchParams;
  const qs = new URLSearchParams();
  if (sp && typeof sp === "object") {
    Object.entries(sp).forEach(([key, value]) => {
      if (value === undefined || value === null) return;
      if (Array.isArray(value)) {
        value.forEach((v) => qs.append(key, String(v)));
      } else {
        qs.set(key, String(value));
      }
    });
  }
  const query = qs.toString();
  redirect(query ? `/seller/${slug}?${query}` : `/seller/${slug}`);
}
