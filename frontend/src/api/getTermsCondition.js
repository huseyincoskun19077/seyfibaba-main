import apiRoutes from "@/appConfig/apiRoutes";

export default async function getTermsCondition() {
  try {
    const res = await fetch(`${apiRoutes.termsCondition}`, {
      headers: { "Content-Type": "application/json" },
      next: { revalidate: 3600 },
    });
    if (!res.ok) return null;
    return await res.json();
  } catch {
    return null;
  }
}
