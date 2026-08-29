import apiRoutes from "@/appConfig/apiRoutes";

export default async function contact() {
  try {
    const res = await fetch(`${apiRoutes.contactUs}`, {
      headers: { "Content-Type": "application/json" },
      next: { revalidate: 300 },
    });
    if (!res.ok) return null;
    return await res.json();
  } catch {
    return null;
  }
}
