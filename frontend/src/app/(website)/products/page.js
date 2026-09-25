import products from "@/api/products";
import AllProductPage from "@/components/AllProductPage";
import { cache } from "react";
import JsonLd, {
  generateItemListSchema,
  generateBreadcrumbSchema,
} from "@/components/Helpers/JsonLd";
import { buildPageTitle } from "@/utils/pageTitle";

export const revalidate = 60;

const HIGHLIGHT_TITLES = {
  popular_category: "Popüler Ürünler",
  top_product: "Öne Çıkan Ürünler",
  new_arrival: "Yeni Gelenler",
  featured_product: "Vitrin Ürünleri",
  best_product: "Çok Satanlar",
  discounted: "İndirimli Ürünler",
};

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

function resolveListingTitle(query = {}, data = {}) {
  if (query?.highlight && HIGHLIGHT_TITLES[query.highlight]) {
    return HIGHLIGHT_TITLES[query.highlight];
  }
  const activeCategory = data?.categories?.find(
    (item) => item.slug === query?.category
  );
  if (activeCategory?.name) {
    return `${activeCategory.name} Ürünleri`;
  }
  if (query?.search) {
    return `"${query.search}" araması`;
  }
  return "Tüm Ürünler";
}

export const getProductsData = cache(async (query) => {
  return await products(query);
});

export async function generateMetadata({ searchParams }) {
  const searchParamsObj = await searchParams;
  const query = resolveProductsQuery(searchParamsObj);
  const data = await getProductsData(query);
  const listingTitle = resolveListingTitle(query, data);
  const activeCategory = data?.categories?.find(
    (item) => item.slug === query?.category
  );

  return {
    title: buildPageTitle(listingTitle),
    description:
      activeCategory?.description ||
      data?.seoSetting?.seo_description ||
      "Kuaför Tedarik ürün kataloğu — kuaför, berber ve güzellik salonu malzemeleri.",
    alternates: {
      canonical: "/products",
    },
  };
}

export default async function Products({ searchParams }) {
  const searchParamsObj = await searchParams;
  const query = resolveProductsQuery(searchParamsObj);
  const searchType = resolvePrimarySearchType(query);
  const data = await getProductsData(query);
  const listingTitle = resolveListingTitle(query, data);

  const itemListSchema = generateItemListSchema(data?.products?.data || []);
  const breadcrumbItems = [
    { name: "Anasayfa", item: "/" },
    { name: "Ürünler", item: "/products" },
  ];
  if (searchType.slug && searchType.type !== "allProducts") {
    breadcrumbItems.push({
      name: listingTitle,
      item: `/products?${searchType.type}=${searchType.slug}`,
    });
  }
  const breadcrumbSchema = generateBreadcrumbSchema(breadcrumbItems);

  return (
    <>
      <JsonLd data={itemListSchema} />
      <JsonLd data={breadcrumbSchema} />
      <AllProductPage response={data} listingTitle={listingTitle} />
    </>
  );
}
