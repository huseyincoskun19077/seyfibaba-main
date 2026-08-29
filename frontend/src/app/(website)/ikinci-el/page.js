import { cache } from "react";
import {
  fetchSecondHandListings,
  fetchProductCategoriesForFilters,
  fetchSecondHandAgreements,
} from "@/api/secondHandPublic";
import SecondHandListSection from "@/components/SecondHand/SecondHandListSection";
import { secondHandPublicOrigin } from "@/utils/secondHandSite";

export const revalidate = 120;

const getListings = cache(async (query) => fetchSecondHandListings(query));
const getCategories = cache(async () => fetchProductCategoriesForFilters());

export async function generateMetadata() {
  const base = secondHandPublicOrigin();
  const agreements = await fetchSecondHandAgreements();
  const title = agreements?.homepage?.title || "İkinci El";
  const description =
    agreements?.homepage?.subtitle || "Doğrulanmış satıcılardan ikinci el ürün ilanları.";
  const url = `${base}/`;
  return {
    metadataBase: new URL(base),
    title,
    description,
    alternates: {
      canonical: url,
    },
    openGraph: {
      type: "website",
      url,
      title,
      description,
      siteName: "Seyfibaba İkinci El",
      locale: "tr_TR",
    },
    twitter: {
      card: "summary",
      title,
      description,
    },
  };
}

export default async function IkinciElPage({ searchParams }) {
  const sp = await searchParams;
  const query = {};
  [
    "category_id",
    "sub_category_id",
    "child_category_id",
    "city_id",
    "province",
    "district",
    "locality",
    "neighborhood",
    "condition",
    "q",
    "page",
  ].forEach(
    (key) => {
      if (sp[key] !== undefined && sp[key] !== null && String(sp[key]).trim() !== "") {
        query[key] = sp[key];
      }
    }
  );

  const [data, catData, agreements] = await Promise.all([
    getListings(query),
    getCategories(),
    fetchSecondHandAgreements(),
  ]);

  return (
    <SecondHandListSection
      data={data}
      query={query}
      filterCategories={(catData?.categories || []).filter(
        (c) =>
          !`${c?.name || ""} ${c?.slug || ""}`.toLowerCase().includes("kozmetik")
      )}
      homepage={agreements?.homepage || null}
    />
  );
}
