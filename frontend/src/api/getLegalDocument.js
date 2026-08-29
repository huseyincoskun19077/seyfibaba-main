import apiRoutes from "@/appConfig/apiRoutes";

export default async function getLegalDocument(slug) {
  try {
    const res = await fetch(`${apiRoutes.legalDocumentShow}/${slug}`, {
      next: { revalidate: 300 },
    });

    if (!res.ok) {
      return null;
    }

    const data = await res.json();
    return data?.document || null;
  } catch {
    return null;
  }
}

export async function getLegalDocumentsList() {
  try {
    const res = await fetch(apiRoutes.legalDocuments, {
      next: { revalidate: 300 },
    });

    if (!res.ok) {
      return [];
    }

    const data = await res.json();
    return data?.documents || [];
  } catch {
    return [];
  }
}
