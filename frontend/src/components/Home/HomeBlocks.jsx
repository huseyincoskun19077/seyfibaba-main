"use client";

import Link from "next/link";
import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";
import { buildProductPath } from "@/utils/url";
import ProductCard from "../Helpers/Cards/ProductCard";
import CategorySection from "./CategorySection";
import BrandSection from "./BrandSection";
import CampaignCountDown from "./CampaignCountDown";
import PriceDisplay from "@/components/Shared/PriceDisplay";

function seeAllHref(block) {
  if (block?.see_all_url) return block.see_all_url;
  const map = {
    popular: "/products?highlight=popular_category",
    discounted: "/products?highlight=discounted",
    featured: "/products?highlight=featured_product",
    new: "/products?highlight=new_arrival",
    best: "/products?highlight=best_product",
  };
  if (block?.type === "all_products") return "/products";
  return map[block?.feed] || "/products";
}

function ProductStrip({ title, products = [], href }) {
  if (!products.length) return null;
  return (
    <section className="w-full">
      <div className="container-x mx-auto">
        <div className="flex items-center justify-between gap-3 mb-3 md:mb-4">
          <h2 className="text-lg md:text-xl font-800 text-[#04334a]">{title}</h2>
          {href ? (
            <Link
              href={href}
              className="text-xs md:text-sm font-700 text-[#04334a] bg-qyellow px-3 py-1.5 rounded-lg hover:brightness-95 shrink-0"
            >
              Tümünü gör
            </Link>
          ) : null}
        </div>
        <div className="flex gap-3 overflow-x-auto pb-2 scrollbar-none">
          {products.map((p) => {
            const hasOffer =
              Number(p.offer_price) > 0 &&
              Number(p.offer_price) < Number(p.price);
            return (
              <Link
                key={p.id}
                href={buildProductPath(p.slug)}
                className="w-[150px] md:w-[180px] shrink-0 rounded-2xl bg-white border border-[#04334a]/10 overflow-hidden"
              >
                <div className="aspect-square bg-neutral-50">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img
                    src={resolveProductImageUrl(p.thumb_image)}
                    alt=""
                    className="h-full w-full object-cover"
                  />
                </div>
                <div className="p-2.5">
                  <p className="text-[12px] font-600 text-[#04334a] line-clamp-2 min-h-[32px]">
                    {p.short_name || p.name}
                  </p>
                  <PriceDisplay
                    price={p.price}
                    offerPrice={hasOffer ? p.offer_price : null}
                    size="sm"
                    layout="stack"
                  />
                </div>
              </Link>
            );
          })}
        </div>
      </div>
    </section>
  );
}

function CampaignStrip({ campaigns = [] }) {
  if (!campaigns.length) return null;
  return (
    <section className="w-full">
      <div className="container-x mx-auto">
        <div className="flex gap-3 overflow-x-auto md:grid md:grid-cols-3 md:overflow-visible pb-1">
          {campaigns.map((c) => {
            const href = c.link || "#";
            const src = c.image?.startsWith("http")
              ? c.image
              : `${appConfig.BASE_URL}${c.image}`;
            return (
              <Link
                key={c.id}
                href={href}
                className="min-w-[85%] sm:min-w-[60%] md:min-w-0 shrink-0 rounded-2xl overflow-hidden border border-[#04334a]/10 bg-white shadow-sm hover:shadow-md transition"
              >
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src={src}
                  alt={c.title || "Kampanya"}
                  className="w-full h-[140px] md:h-[180px] object-cover"
                />
              </Link>
            );
          })}
        </div>
      </div>
    </section>
  );
}

function AllProductsGrid({ title, products = [], formatProduct }) {
  if (!products.length) return null;
  return (
    <section id="home-all-products" className="mobile-floating-safe">
      <div className="container-x mx-auto">
        <div className="flex items-center justify-between gap-2 mb-3">
          <h2 className="text-xl font-semibold text-qblack">{title || "Tüm Ürünler"}</h2>
        </div>
        <div className="w-full grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2 sm:gap-2.5 xl:gap-3">
          {products.slice(0, 12).map((item) => (
            <div key={item.id} className="min-w-0">
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
  );
}

/**
 * Admin home_blocks sırasına göre anasayfa gövdesi.
 * Ardışık kampanya blokları tek şeritte birleştirilir.
 */
export default function HomeBlocks({
  blocks = [],
  homepage,
  formatProduct,
  downloadData,
}) {
  if (!Array.isArray(blocks) || blocks.length === 0) return null;

  const nodes = [];
  let i = 0;
  while (i < blocks.length) {
    const b = blocks[i];
    if (b.type === "campaign") {
      const group = [];
      while (i < blocks.length && blocks[i].type === "campaign") {
        group.push(blocks[i]);
        i += 1;
      }
      nodes.push(<CampaignStrip key={`camp-${group[0].id}`} campaigns={group} />);
      continue;
    }

    if (b.type === "product_feed") {
      nodes.push(
        <ProductStrip
          key={b.id}
          title={b.title}
          products={b.products || []}
          href={seeAllHref(b)}
        />
      );
    } else if (b.type === "all_products") {
      nodes.push(
        <AllProductsGrid
          key={b.id}
          title={b.title}
          products={b.products?.length ? b.products : homepage?.allProducts || []}
          formatProduct={formatProduct}
        />
      );
    } else if (b.type === "category_grid") {
      nodes.push(
        <div key={b.id} className="bg-white/50">
          <CategorySection
            categories={
              b.categories?.length
                ? b.categories
                : homepage?.homepage_categories
            }
            sectionTitle={b.title || "Kategoriler"}
          />
        </div>
      );
    } else if (b.type === "brands") {
      nodes.push(
        <BrandSection
          key={b.id}
          brands={homepage?.brands || []}
          sectionTitle={b.title || "Markalar"}
        />
      );
    } else if (b.type === "flash_sale") {
      nodes.push(
        <CampaignCountDown
          key={b.id}
          className="md:mb-4 mb-2 md:mt-5 mt-3"
          downloadData={downloadData}
          flashSaleData={homepage?.flashSale}
          lastDate={homepage?.flashSale?.end_time}
        />
      );
    }
    i += 1;
  }

  return <div className="space-y-4 md:space-y-7">{nodes}</div>;
}
