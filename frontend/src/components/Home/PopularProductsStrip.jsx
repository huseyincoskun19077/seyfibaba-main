"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import { resolveProductImageUrl } from "@/utils/productImage";
import { buildProductPath } from "@/utils/url";
import PriceDisplay from "@/components/Shared/PriceDisplay";

function ProductSlideCard({ product }) {
  const hasOffer =
    product.offer_price &&
    Number(product.offer_price) > 0 &&
    Number(product.offer_price) < Number(product.price);

  return (
    <Link
      href={buildProductPath(product.slug)}
      className="w-[160px] md:w-[200px] shrink-0 rounded-2xl bg-white border border-[#04334a]/10 overflow-hidden hover:shadow-md transition-shadow"
    >
      <div className="aspect-square bg-neutral-50">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src={resolveProductImageUrl(product.thumb_image)}
          alt=""
          className="h-full w-full object-cover"
        />
      </div>
      <div className="p-3">
        <p className="text-[13px] font-600 text-[#04334a] line-clamp-2 min-h-[36px]">
          {product.short_name || product.name}
        </p>
        <div className="mt-2">
          <PriceDisplay
            price={product.price}
            offerPrice={hasOffer ? product.offer_price : null}
            size="sm"
            layout="stack"
          />
        </div>
      </div>
    </Link>
  );
}

export default function PopularProductsStrip({ products = [] }) {
  const [paused, setPaused] = useState(false);

  const list = useMemo(() => {
    if (!Array.isArray(products) || products.length === 0) return [];
    return products.slice(0, 16);
  }, [products]);

  const loop = useMemo(() => {
    if (!list.length) return [];
    // Sonsuz sola kayma için iki kopya
    return [0, 1].flatMap((copy) =>
      list.map((product, index) => ({
        product,
        key: `${copy}-${product.id}-${index}`,
      }))
    );
  }, [list]);

  if (!list.length) return null;

  return (
    <section className="w-full">
      <div className="container-x mx-auto">
        <div className="flex items-center justify-between gap-3 mb-3 md:mb-4">
          <h2 className="text-lg md:text-xl font-800 text-[#04334a]">
            Popüler ürünler
          </h2>
          <Link
            href="/products?highlight=popular_category"
            className="text-xs md:text-sm font-700 text-[#04334a] bg-qyellow px-3 py-1.5 rounded-lg hover:brightness-95 shrink-0"
          >
            Tümünü gör
          </Link>
        </div>

        <div
          className="overflow-hidden rounded-2xl border border-[#04334a]/10 bg-white py-3 md:py-4"
          onMouseEnter={() => setPaused(true)}
          onMouseLeave={() => setPaused(false)}
        >
          <div
            className={`sb-stories-marquee gap-3 md:gap-4 px-3 md:px-4 ${
              paused ? "is-paused" : ""
            }`}
            style={{ animationDuration: "40s" }}
          >
            {loop.map(({ product, key }) => (
              <ProductSlideCard key={key} product={product} />
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
