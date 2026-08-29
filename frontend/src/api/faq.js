import apiRoutes from "@/appConfig/apiRoutes";

export default async function faq() {
  try {
    const res = await fetch(`${apiRoutes.faq}`, {
      headers: { "Content-Type": "application/json" },
      next: { revalidate: 300 },
    });
    if (!res.ok) return null;
    return await res.json();
  } catch {
    return null;
  }
}
