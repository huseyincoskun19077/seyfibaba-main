import { serverApiGet } from "@/utils/serverApiFetch";

const EMPTY_HOMEPAGE = {
  sliders: [],
  services: [],
  homepage_categories: [],
  popularCategories: [],
  popularCategoryProducts: [],
  featuredCategories: [],
  featuredCategoryProducts: [],
  topRatedProducts: [],
  newArrivalProducts: [],
  bestProducts: [],
  allProducts: [],
  brands: [],
  section_title: [],
  flashSale: null,
  flashSaleProducts: [],
};

export default async function home() {
  try {
    const res = await serverApiGet("");

    if (!res.ok) {
      return EMPTY_HOMEPAGE;
    }

    try {
      return await res.json();
    } catch {
      return EMPTY_HOMEPAGE;
    }
  } catch {
    return EMPTY_HOMEPAGE;
  }
}
