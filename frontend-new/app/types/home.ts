export interface SliderItem {
  id?: number;
  image?: string | null;
  product_slug?: string | null;
  title_one?: string | null;
  title_two?: string | null;
  badge?: string | null;
  status?: number | string | null;
}

export interface CategoryItem {
  id: number;
  name: string;
  slug: string;
  image?: string | null;
  icon?: string | null;
  description?: string | null;
}

export interface ProductItem {
  id: number;
  name: string;
  slug: string;
  thumb_image?: string | null;
  price?: number | string | null;
  offer_price?: number | string | null;
  averageRating?: number | string | null;
  vendor_id?: number | string | null;
}

export interface ServiceItem {
  id: number;
  title: string;
  description?: string | null;
  icon?: string | null;
}

export interface BrandItem {
  id: number;
  name: string;
  slug?: string | null;
  logo?: string | null;
}

export interface FlashSaleItem {
  status?: number | string | null;
  offer?: number | string | null;
  end_time?: string | null;
}

export interface HomePayload {
  sliders: SliderItem[];
  services: ServiceItem[];
  homepage_categories: CategoryItem[];
  popularCategoryProducts: ProductItem[];
  featuredCategoryProducts: ProductItem[];
  topRatedProducts: ProductItem[];
  newArrivalProducts: ProductItem[];
  bestProducts: ProductItem[];
  allProducts: ProductItem[];
  brands: BrandItem[];
  section_title: Array<{ key: string; custom?: string | null; default?: string | null }>;
  flashSale: FlashSaleItem | null;
  sliderBannerOne?: SliderItem | null;
  sliderBannerTwo?: SliderItem | null;
  flashSaleSidebarBanner?: SliderItem | null;
}

export const EMPTY_HOMEPAGE: HomePayload = {
  sliders: [],
  services: [],
  homepage_categories: [],
  popularCategoryProducts: [],
  featuredCategoryProducts: [],
  topRatedProducts: [],
  newArrivalProducts: [],
  bestProducts: [],
  allProducts: [],
  brands: [],
  section_title: [],
  flashSale: null,
  sliderBannerOne: null,
  sliderBannerTwo: null,
  flashSaleSidebarBanner: null,
};
