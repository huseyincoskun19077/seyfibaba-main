import apiRoutes from "@/appConfig/apiRoutes";

const PUBLIC_CONTENT_REVALIDATE = 60;

const emptyProductsPayload = () => ({
  products: { data: [], total: 0, next_page_url: null },
  categories: [],
  brands: [],
  activeVariants: [],
  shopPage: { filter_price_range: 100000 },
  seoSetting: null,
});

const appendListParam = (params, key, value) => {
  if (!value) return;

  const values = Array.isArray(value)
    ? value
    : String(value)
        .split(",")
        .map((item) => item.trim())
        .filter(Boolean);

  values.forEach((item) => {
    params.append(`${key}[]`, item);
  });
};

const buildQueryFromObject = (query = {}) => {
  const params = new URLSearchParams();

  [
    "category",
    "sub_category",
    "child_category",
    "highlight",
    "brand",
    "search",
    "min_price",
    "max_price",
    "shorting_id",
  ].forEach((key) => {
    if (
      query?.[key] !== undefined &&
      query?.[key] !== null &&
      query?.[key] !== ""
    ) {
      params.set(key, query[key]);
    }
  });

  appendListParam(params, "brands", query?.brands);
  appendListParam(params, "categories", query?.categories);
  appendListParam(params, "variantItems", query?.variantItems);

  const queryString = params.toString();
  return queryString ? `?${queryString}` : "";
};

/**
 * Function to get products
 * @param {string|object} type
 * @param {string} slug
 * @param {string} searchWithCategorySlug
 * @returns {object}
 */
export default async function products(
  type,
  slug,
  searchWithCategorySlug = ""
) {
  let query = "";
  if (type && typeof type === "object" && !Array.isArray(type)) {
    query = buildQueryFromObject(type);
  } else {
    switch (type) {
      case "allProducts":
        query = "";
        break;
      case "category":
        query = `?category=${slug}`;
        break;
      case "sub_category":
        query = `?sub_category=${slug}`;
        break;
      case "child_category":
        query = `?child_category=${slug}`;
        break;
      case "highlight":
        query = `?highlight=${slug}`;
        break;
      case "brand":
        query = `?brand=${slug}`;
        break;
      case "search":
        query = `?search=${slug}`;
        break;
      case "searchWithCategory":
        query = `${searchWithCategorySlug}`;
        break;
      default:
        query = "";
        break;
    }
  }

  let res;
  try {
    res = await fetch(`${apiRoutes.products + query}`, {
      headers: {
        "Content-Type": "application/json",
      },
      next: {
        revalidate: PUBLIC_CONTENT_REVALIDATE,
      },
    });
  } catch {
    return emptyProductsPayload();
  }
  if (!res.ok) {
    return emptyProductsPayload();
  }
  try {
    const data = await res.json();
    return data || emptyProductsPayload();
  } catch {
    return emptyProductsPayload();
  }
}
