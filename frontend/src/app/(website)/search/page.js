import products from "@/api/products";
import AllProductPage from "@/components/AllProductPage";
import { cache } from "react";
import JsonLd, {
  generateBreadcrumbSchema,
  generateItemListSchema,
} from "@/components/Helpers/JsonLd";
import { buildPageTitle } from "@/utils/pageTitle";

export const revalidate = 300;

const resolveSearchQuery = (searchParamsObj = {}) => {
  const query = {};

  [
    "search",
    "category",
    "sub_category",
    "child_category",
    "brand",
    "brands",
    "categories",
    "variantItems",
    "min_price",
    "max_price",
    "shorting_id",
  ].forEach((key) => {
    if (searchParamsObj?.[key] !== undefined) {
      query[key] = searchParamsObj[key];
    }
  });

  return query;
};

export const getSearchProductsData = cache(async (query) => {
  return await products(query);
});

// generate seo metadata
export async function generateMetadata({ searchParams }) {
  const searchParamsObj = await searchParams;
  const data = await getSearchProductsData(resolveSearchQuery(searchParamsObj));
  const { seoSetting } = data;
  return {
    title: buildPageTitle(seoSetting?.seo_title || "Arama Sonuçları"),
    description: seoSetting?.seo_description,
    alternates: {
      canonical: "/search",
    },
    robots: { index: false, follow: true },
  };
}

// main page
export default async function SearchProductsPage({ searchParams }) {
  const searchParamsObj = await searchParams;
  const data = await getSearchProductsData(resolveSearchQuery(searchParamsObj));
  const itemListSchema = generateItemListSchema(data?.products?.data || []);
  const breadcrumbSchema = generateBreadcrumbSchema([
    { name: "Anasayfa", item: "/" },
    { name: "Arama Sonuclari", item: "/search" },
  ]);

  return (
    <>
      <JsonLd data={itemListSchema} />
      <JsonLd data={breadcrumbSchema} />
      <AllProductPage response={data} />
    </>
  );
}
