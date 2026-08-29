import apiRoutes from "@/appConfig/apiRoutes";
import { notFound } from "next/navigation";

const buildSellerQuery = (query = {}) => {
  const params = new URLSearchParams();

  [
    "category",
    "sub_category",
    "child_category",
    "brand",
    "search",
    "min_price",
    "max_price",
    "per_page",
  ].forEach((key) => {
    if (
      query?.[key] !== undefined &&
      query?.[key] !== null &&
      query?.[key] !== ""
    ) {
      params.set(key, query[key]);
    }
  });

  ["brands", "categories", "variantItems"].forEach((key) => {
    const value = query?.[key];
    if (!value) {
      return;
    }

    const values = Array.isArray(value)
      ? value
      : String(value)
          .split(",")
          .map((item) => item.trim())
          .filter(Boolean);

    values.forEach((item) => {
      params.append(`${key}[]`, item);
    });
  });

  const queryString = params.toString();
  return queryString ? `?${queryString}` : "";
};

export default async function sellerDetails(seller, query = {}) {
  const queryString = buildSellerQuery(query);
  const res = await fetch(`${apiRoutes.sellers}/${seller}${queryString}`, {
    headers: {
      "Content-Type": "application/json",
    },
    cache: "no-store",
  });
  if (!res.ok) {
    notFound();
  }
  try {
    const data = await res.json();
    return data;
  } catch (error) {
    notFound();
  }
}
