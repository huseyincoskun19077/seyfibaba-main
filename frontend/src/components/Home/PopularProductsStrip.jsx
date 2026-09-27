"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import appConfig from "@/appConfig";
import auth from "@/utils/auth";
import { resolveProductImageUrl } from "@/utils/productImage";
import { buildProductPath } from "@/utils/url";
import PriceDisplay from "@/components/Shared/PriceDisplay";
import {
  consumePersonalizedDirty,
  PERSONALIZED_DIRTY_KEY,
} from "@/hooks/useProductViewTracker";

function ProductSlideCard({ product }) {
  const hasOffer =
    product.offer_price &&
    Number(product.offer_price) > 0 &&
    Number(product.offer_price) < Number(product.price);

  return (
    <Link
      href={buildProductPath(product.slug)}
      className="w-[160px] md:w-[200px] shrink-0 rounded-2xl bg-white border border-[#04334a]/10 overflow-hidden hover:shadow-md transition-shadow"
      data-product-id={product.id}
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

function isHomePath(pathname) {
  const p = String(pathname || "").replace(/\/+$/, "") || "/";
  return p === "/" || p === "";
}

/**
 * Anasayfa Size Özel / Popüler şerit.
 * Ürün gezintisi sonrası ana sayfaya dönüşte (pathname + dirty bayrak + pageshow) yeniden çeker.
 */
export default function PopularProductsStrip({ products: fallbackProducts = [] }) {
  const pathname = usePathname() || "";
  const [paused, setPaused] = useState(false);
  const [title, setTitle] = useState("Popüler ürünler");
  const [list, setList] = useState([]);
  const [lastFetchAt, setLastFetchAt] = useState(null);
  const prevPathRef = useRef(pathname);

  const loadPersonalized = useCallback(
    async (cancelledRef, reason = "mount") => {
      try {
        const token = auth()?.access_token;
        const qs = new URLSearchParams({
          limit: "12",
          scope: "home",
          _ts: String(Date.now()),
        });
        if (token) qs.set("token", token);
        const res = await fetch(
          `${appConfig.BASE_URL}api/personalized-products?${qs.toString()}`,
          {
            headers: {
              Accept: "application/json",
              ...(token ? { Authorization: `Bearer ${token}` } : {}),
            },
            cache: "no-store",
          }
        );
        if (!res.ok) throw new Error("fetch failed");
        const data = await res.json();
        const products = Array.isArray(data?.products) ? data.products : [];
        if (cancelledRef.current) return;
        consumePersonalizedDirty();
        setLastFetchAt({ at: Date.now(), reason, count: products.length });
        if (products.length) {
          setList(products.slice(0, 12));
          setTitle(
            String(data?.title || "").trim() ||
              (data?.source === "personalized" ||
              data?.source === "recommendation"
                ? "Size Özel"
                : "Popüler ürünler")
          );
          return;
        }
      } catch {
        /* fallback */
      }
      if (cancelledRef.current) return;
      const fb = Array.isArray(fallbackProducts)
        ? fallbackProducts.slice(0, 12)
        : [];
      setList(fb);
      setTitle("Popüler ürünler");
    },
    [fallbackProducts]
  );

  // Ana sayfa yolu / ürün sayfasından dönüş
  useEffect(() => {
    const cancelledRef = { current: false };
    const prev = prevPathRef.current;
    prevPathRef.current = pathname;

    const cameFromProduct =
      typeof prev === "string" &&
      (prev.includes("/urun/") || prev.includes("/product/"));
    const onHome = isHomePath(pathname);
    const dirty =
      typeof window !== "undefined" &&
      !!sessionStorage.getItem(PERSONALIZED_DIRTY_KEY);

    if (onHome) {
      const reason = cameFromProduct
        ? "return_from_product"
        : dirty
          ? "dirty_flag"
          : "home_path";
      loadPersonalized(cancelledRef, reason);
    }

    return () => {
      cancelledRef.current = true;
    };
  }, [loadPersonalized, pathname]);

  useEffect(() => {
    const cancelledRef = { current: false };
    const refreshIfHome = (reason) => {
      if (!isHomePath(pathname)) return;
      if (
        reason === "visibility" &&
        document.visibilityState !== "visible"
      ) {
        return;
      }
      loadPersonalized(cancelledRef, reason);
    };
    const onVisible = () => refreshIfHome("visibility");
    const onPageShow = () => refreshIfHome("pageshow");
    document.addEventListener("visibilitychange", onVisible);
    window.addEventListener("pageshow", onPageShow);
    window.addEventListener("focus", onPageShow);
    return () => {
      cancelledRef.current = true;
      document.removeEventListener("visibilitychange", onVisible);
      window.removeEventListener("pageshow", onPageShow);
      window.removeEventListener("focus", onPageShow);
    };
  }, [loadPersonalized, pathname]);

  const loop = useMemo(() => {
    if (!list.length) return [];
    return [0, 1].flatMap((copy) =>
      list.map((product, index) => ({
        product,
        key: `${copy}-${product.id}-${index}`,
      }))
    );
  }, [list]);

  if (!list.length) return null;

  const idSignature = list.map((p) => p.id).join(",");

  return (
    <section
      className="w-full"
      data-size-ozel-ids={idSignature}
      data-size-ozel-fetched-at={lastFetchAt?.at || ""}
      data-size-ozel-fetch-reason={lastFetchAt?.reason || ""}
    >
      <div className="container-x mx-auto">
        <div className="flex items-center justify-between gap-3 mb-3 md:mb-4">
          <h2 className="text-lg md:text-xl font-800 text-[#04334a]">{title}</h2>
          <Link
            href="/products?highlight=sana_ozel"
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
