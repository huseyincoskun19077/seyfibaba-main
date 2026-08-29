import apiRoutes from "@/appConfig/apiRoutes";

const REVALIDATE = 300;

export default async function about() {
  try {
    const res = await fetch(`${apiRoutes.about}`, {
      headers: { "Content-Type": "application/json" },
      next: { revalidate: REVALIDATE },
    });
    if (!res.ok) return null;
    return await res.json();
  } catch {
    return null;
  }
}
