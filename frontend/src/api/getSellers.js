import apiRoutes from "@/appConfig/apiRoutes";

export default async function getSellers() {
  try {
    const res = await fetch(`${apiRoutes.sellers}`, {
      headers: { "Content-Type": "application/json" },
      next: { revalidate: 300 },
    });
    if (!res.ok) return null;
    return await res.json();
  } catch {
    return null;
  }
}
