import products from "@/api/products";
import AllProductPage from "@/components/AllProductPage";
import { cache } from "react";
import JsonLd, { generateItemListSchema, generateBreadcrumbSchema } from "@/components/Helpers/JsonLd";
import { buildPageTitle } from "@/utils/pageTitle";

export const revalidate = 300;

const resolveProductsQuery = (searchParamsObj = {}) => {
  const query = {};

  [
    "category",
    "sub_category",
    "child_category",
    "highlight",
    "brand",
    "search",
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

const resolvePrimarySearchType = (query = {}) => {
  const orderedKeys = [
    "category",
    "sub_category",
    "child_category",
    "highlight",
    "brand",
    "search",
  ];

  const activeKey = orderedKeys.find((key) => query?.[key]);
  return {
    type: activeKey || "allProducts",
    slug: activeKey ? query[activeKey] : "",
  };
};

export const getProductsData = cache(async (query) => {
  return await products(query);
});

// generate seo metadata
export async function generateMetadata({ searchParams }) {
  const searchParamsObj = await searchParams;
  const query = resolveProductsQuery(searchParamsObj);
  const data = await getProductsData(query);
  const { seoSetting } = data;
  const activeCategory = data?.categories?.find(
    (item) => item.slug === query?.category
  );

  return {
    title: buildPageTitle(
      activeCategory?.name
        ? `${activeCategory.name} Ürünleri`
        : seoSetting?.seo_title || "Tüm Ürünler"
    ),
    description:
      activeCategory?.description ||
      seoSetting?.seo_description,
    alternates: {
      canonical: "/products",
    },
  };
}

// main page
export default async function Products({ searchParams }) {
  const searchParamsObj = await searchParams;
  const query = resolveProductsQuery(searchParamsObj);
  const searchType = resolvePrimarySearchType(query);
  const data = await getProductsData(query);
  
  const itemListSchema = generateItemListSchema(data?.products?.data || []);
  const breadcrumbItems = [
    { name: "Anasayfa", item: "/" },
    { name: "Ürünler", item: "/products" }
  ];
  if (searchType.slug && searchType.type !== 'allProducts') {
    breadcrumbItems.push({ name: searchType.slug });
  }
  const breadcrumbSchema = generateBreadcrumbSchema(breadcrumbItems);

  return (
    <>
      <JsonLd data={itemListSchema} />
      <JsonLd data={breadcrumbSchema} />
      <AllProductPage response={data} />
    </>
  );
}
