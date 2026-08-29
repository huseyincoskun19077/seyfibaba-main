"use client";
import { useEffect, useState } from "react";
import dynamic from "next/dynamic";
import Link from "next/link";
import Image from "next/image";
import { usePathname } from "next/navigation";
import Banner from "./Banner";
import CategorySection from "./CategorySection";
import { isFlashSaleActive } from "@/utils/flashSale";
import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";
import ProductCard from "../Helpers/Cards/ProductCard";
import HomeSlider from "../Slider/HomeSlider";

const Ads = dynamic(() => import("./Ads"), { ssr: false });
const ViewMoreTitle = dynamic(() => import("../Helpers/ViewMoreTitle"));
const SectionStyleTwo = dynamic(() => import("../Helpers/SectionStyleTwo"));
const BrandSection = dynamic(() => import("./BrandSection"));
const CampaignCountDown = dynamic(() => import("./CampaignCountDown"));

export default function Home({ homepageData }) {
  const pathname = usePathname() || "";
  const getsectionTitles = homepageData?.section_title;

  const getTurkishSectionTitle = (key, value) => {
    if (
      key === "Trending_Category" ||
      /kategori/i.test(String(value || "")) ||
      /trending\s*category/i.test(String(value || ""))
    ) {
      return "Kategoriler";
    }
    const titleMap = {
      Shop_by_Brand: "Markalara Göre Alışveriş",
      Popular_Category: "En popüler ürünler",
      Top_Rated_Products: "En popüler ürünler",
    };
    return titleMap[key] || value;
  };

  let sectionTitles = {};
  if (getsectionTitles && getsectionTitles.length > 0) {
    getsectionTitles.forEach((item) => {
      sectionTitles[item.key] = getTurkishSectionTitle(
        item.key,
        item.custom ? item.custom : item.default
      );
    });
  }

  const homepage = homepageData;
  const mobileSliderSettings = {
    pagination: {
      clickable: true,
    },
    loop: true,
    autoplay: {
      delay: 3200,
      speed: 900,
      disableOnInteraction: false,
    },
    effect: "fade",
  };

  // Tüm Ürünler — API'den gelen genel liste (vitrin bayrağı gerekmez); yoksa eski birleşik vitrin listesi
  const mergedVitrineProducts = [
    ...(homepage?.newArrivalProducts || []),
    ...(homepage?.topRatedProducts || []),
    ...(homepage?.bestProducts || []),
    ...(homepage?.popularCategoryProducts || []),
    ...(homepage?.featuredCategoryProducts || []),
  ];
  const uniqueProducts = mergedVitrineProducts.filter(
    (product, index, self) => index === self.findIndex((p) => p.id === product.id)
  );
  const homepageAllProducts =
    homepage?.allProducts?.length > 0 ? homepage.allProducts : uniqueProducts;
  const discountedProducts = (homepage?.discountedProducts?.length
    ? homepage.discountedProducts
    : homepageAllProducts.filter(
        (p) => Number(p?.offer_price || 0) > 0 && Number(p?.offer_price) < Number(p?.price || 0)
      )
  ).filter(
    (p) => Number(p?.offer_price || 0) > 0 && Number(p?.offer_price) < Number(p?.price || 0)
  );
  const appDownloadBanner =
    homepage?.flashSaleSidebarBanner &&
    Number(homepage.flashSaleSidebarBanner.status) === 1
      ? homepage.flashSaleSidebarBanner
      : null;
  const hasAppDownloadLinks = Boolean(
    appDownloadBanner?.play_store?.trim() || appDownloadBanner?.app_store?.trim()
  );
  const showCampaignSection =
    (homepage?.flashSale && isFlashSaleActive(homepage.flashSale)) || hasAppDownloadLinks;
  const [selectedMobileCategorySlug, setSelectedMobileCategorySlug] = useState("");

  const ITEMS_PER_PAGE = 8;

  const selectedMobileCategory = (homepage?.homepage_categories || []).find(
    (category) => category.slug === selectedMobileCategorySlug
  );
  const mobileFilteredProducts =
    selectedMobileCategory?.id
      ? homepageAllProducts.filter(
          (product) => Number(product.category_id) === Number(selectedMobileCategory.id)
        )
      : homepageAllProducts;
  const visibleProducts = mobileFilteredProducts.slice(0, ITEMS_PER_PAGE);

  const formatProduct = (item) => ({
    id: item.id,
    title: item.name,
    slug: item.slug,
    image: resolveProductImageUrl(item.thumb_image),
    price: item.price,
    offer_price: item.offer_price,
    campaingn_product: null,
    vendor_id: Number(item.vendor_id),
    review: parseInt(item.averageRating),
    variants: item.active_variants ? item.active_variants : [],
    sale_unit_qty: item.sale_unit_qty,
  });

  return (
    <div className="w-full pt-1 pb-12 md:pb-16 space-y-4 md:space-y-7 bg-[#fdfdfd]">
      <Ads />

      {/* Mobil: slider + kategoriler — sıkı aralık */}
      <div className="md:hidden space-y-3">
        {homepage?.sliders?.length > 0 && (
          <section>
            <div className="container-x mx-auto">
              <div className="w-full h-[160px] sm:h-[180px] rounded-3xl overflow-hidden">
                <HomeSlider images={homepage.sliders} settings={mobileSliderSettings} />
              </div>
            </div>
          </section>
        )}
        <section className="bg-white/50">
          <CategorySection
            categories={homepage?.homepage_categories}
            sectionTitle="Kategoriler"
            selectedCategorySlug={selectedMobileCategorySlug}
            onSelectCategory={(slug) => {
              setSelectedMobileCategorySlug(slug);
              if (typeof window !== "undefined") {
                window.requestAnimationFrame(() => {
                  const section = document.getElementById("home-all-products");
                  if (section) {
                    section.scrollIntoView({ behavior: "smooth", block: "start" });
                  }
                });
              }
            }}
          />
        </section>
      </div>

      {/* Desktop Slider / Banner */}
      {homepage?.sliders?.length > 0 && (
        <div className="container-x mx-auto hidden md:block">
          <Banner
            images={homepage.sliders}
            services={homepage.services}
            sidebarImgOne={
              homepage.sliderBannerOne &&
              parseInt(homepage.sliderBannerOne.status) === 1
                ? homepage.sliderBannerOne
                : null
            }
            sidebarImgTwo={
              homepage.sliderBannerTwo &&
              parseInt(homepage.sliderBannerTwo.status) === 1
                ? homepage.sliderBannerTwo
                : null
            }
            className="banner-wrapper shadow-xl rounded-3xl overflow-hidden"
          />
        </div>
      )}

      {/* Dinamik section'lar — admin paneldeki sıraya göre render */}
      {/* Tum Urunler — 8 urun + tumune git */}
      {homepageAllProducts.length > 0 && (
        <section id="home-all-products" className="mobile-floating-safe">
          <div className="container-x mx-auto">
            <div className="flex flex-col items-start gap-2 mb-3 max-md:pr-14 md:flex-row md:items-center md:justify-between">
              <h2 className="text-xl font-semibold text-qblack max-md:leading-snug">
                {selectedMobileCategory?.name
                  ? `${selectedMobileCategory.name} Ürünleri`
                  : "Tüm Ürünler"}
              </h2>
            </div>
            <div className="w-full grid grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 xl:gap-5 gap-2.5">
              {visibleProducts.map((item) => (
                <div key={`${pathname}-${item.id}`} data-aos="fade-up">
                  <ProductCard datas={formatProduct(item)} compact />
                </div>
              ))}
            </div>
            <div className="w-full flex justify-center mt-5 md:mt-8">
              <Link
                href="/products"
                className="inline-flex items-center justify-center h-[44px] px-6 rounded-full bg-qblack text-white text-sm font-700 hover:bg-qyellow hover:text-qblack transition-colors"
              >
                Tüm Ürünleri Gör
              </Link>
            </div>
          </div>
        </section>
      )}

      {/* İndirimli ürünler — admin sırasından bağımsız sabit bölüm */}
      {discountedProducts.length > 0 && (
        <section>
          <ViewMoreTitle
            seeMoreUrl="/products?highlight=discounted"
            categoryTitle="İndirimli Ürünler"
          >
            <SectionStyleTwo products={discountedProducts.slice(0, 2)} />
          </ViewMoreTitle>
        </section>
      )}

      {(getsectionTitles || []).map((section) => {
        const key = section.key;
        const title = sectionTitles[key] || section.custom || section.default;

        switch (key) {
          case "Trending_Category":
            return (
              <section key={key} className="hidden md:block py-4 md:py-6 bg-white/50">
                <CategorySection
                  categories={homepage?.homepage_categories}
                  sectionTitle={title}
                  selectedCategorySlug={selectedMobileCategorySlug}
                  onSelectCategory={(slug) => {
                    setSelectedMobileCategorySlug(slug);
                    if (typeof window !== "undefined") {
                      window.requestAnimationFrame(() => {
                        const section = document.getElementById("home-all-products");
                        if (section) {
                          section.scrollIntoView({ behavior: "smooth", block: "start" });
                        }
                      });
                    }
                  }}
                />
              </section>
            );

          case "Popular_Category":
            return homepage?.popularCategoryProducts?.length > 0 ? (
              <section key={key}>
                <ViewMoreTitle
                  seeMoreUrl="/products?highlight=popular_category"
                  categoryTitle={title}
                >
                  <SectionStyleTwo products={homepage.popularCategoryProducts} />
                </ViewMoreTitle>
              </section>
            ) : null;

          case "Top_Rated_Products":
            return null;

          case "Best_Seller":
            // Best Seller = En çok satan satıcılar — şu an gizli
            return null;

          case "Shop_by_Brand":
            return homepage?.brands?.length > 0 ? (
              <section key={key} className="py-5 md:py-8 bg-gray-50/50 border-y border-gray-100">
                <BrandSection
                  brands={homepage.brands}
                  sectionTitle={title}
                  className="brand-section-wrapper"
                />
              </section>
            ) : null;

          case "New_Arrivals":
            return homepage?.newArrivalProducts?.length > 0 ? (
              <section key={key}>
                <ViewMoreTitle seeMoreUrl="/products?highlight=new_arrival" categoryTitle={title}>
                  <SectionStyleTwo products={homepage.newArrivalProducts} />
                </ViewMoreTitle>
              </section>
            ) : null;

          case "Best_Products":
            return homepage?.bestProducts?.length > 0 ? (
              <section key={key}>
                <ViewMoreTitle seeMoreUrl="/products?highlight=best_product" categoryTitle={title}>
                  <SectionStyleTwo products={homepage.bestProducts} />
                </ViewMoreTitle>
              </section>
            ) : null;

          case "Featured_Products":
            return (
              <div key={key}>
                {homepage?.featuredCategoryProducts?.length > 0 && (
                  <section>
                    <ViewMoreTitle
                      seeMoreUrl="/products?highlight=featured_product"
                      categoryTitle={title}
                    >
                      <SectionStyleTwo
                        products={homepage.featuredCategoryProducts}
                      />
                    </ViewMoreTitle>
                  </section>
                )}
                {/* Mobil uygulama — Öne Çıkan Ürünler'in hemen altında */}
                {showCampaignSection && (
                  <section>
                    <CampaignCountDown
                      className="md:mb-4 mb-2 md:mt-5 mt-3"
                      flashSaleData={homepage.flashSale}
                      downloadData={hasAppDownloadLinks ? appDownloadBanner : null}
                      lastDate={homepage.flashSale?.end_time}
                    />
                  </section>
                )}
              </div>
            );

          default:
            return null;
        }
      })}

    </div>
  );
}
