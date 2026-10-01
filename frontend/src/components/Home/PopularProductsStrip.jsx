"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import appConfig from "@/appConfig";
import auth from "@/utils/auth";
import { resolveProductImageUrl } from "@/utils/productImage";
import { buildProductPath } from "@/utils/url";
import PriceDisplay from "@/components/Shared/PriceDisplay";
import { hasMarketingConsent } from "@/components/Helpers/Consent";
import {
  consumePersonalizedDirty,
  getGuestKey,
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
      className="rounded-2xl bg-white border border-[#04334a]/10 overflow-hidden hover:shadow-md transition-shadow"
      data-product-id={product.id}
    >
      <div className="relative aspect-square bg-neutral-50">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src={resolveProductImageUrl(product.thumb_image)}
          alt=""
          className="h-full w-full object-cover"
        />
        {hasOffer ? (
          <span className="absolute right-1.5 top-1.5 z-10 rounded-md bg-[#E11D48] px-1.5 py-0.5 text-[10px] font-800 uppercase tracking-wide text-white shadow-sm">
            İndirimli
          </span>
        ) : null}
      </div>
      <div className="p-3">
        <p className="text-[13px] font-600 text-[#04334a] line-clamp-2 min-h-[36px]">
          {product.short_name || product.name}
        </p>
        <div className="mt-2 min-h-[40px]">
          <PriceDisplay
            price={product.price}
            offerPrice={hasOffer ? product.offer_price : null}
            size="sm"
            layout="stack"
            showSavings={false}
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
 * Anasayfa Size Özel / Popüler şerit — sabit grid (otomatik kayma yok).
 */
function takeStripProducts(products) {
  return Array.isArray(products) ? products.slice(0, 12) : [];
}

export default function PopularProductsStrip({ products: fallbackProducts = [] }) {
  const pathname = usePathname() || "";
  const [title, setTitle] = useState("Popüler ürünler");
  const [list, setList] = useState(() => takeStripProducts(fallbackProducts));
  const [lastFetchAt, setLastFetchAt] = useState(null);
  const prevPathRef = useRef(pathname);

  const loadPersonalized = useCallback(
    async (cancelledRef, reason = "mount") => {
      const token = auth()?.access_token;
      if (!token && !hasMarketingConsent()) {
        if (!cancelledRef.current) {
          setList(takeStripProducts(fallbackProducts));
          setTitle("Popüler ürünler");
        }
        return;
      }
      try {
        const token = auth()?.access_token;
        const qs = new URLSearchParams({
          limit: "12",
          scope: "home",
          _ts: String(Date.now()),
        });
        if (token) {
          qs.set("token", token);
        } else if (hasMarketingConsent()) {
          const guestKey = getGuestKey();
          if (guestKey) {
            qs.set("guest_key", guestKey);
            qs.set("consent", "1");
          }
        }
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
      const fb = takeStripProducts(fallbackProducts);
      setList(fb);
      setTitle("Popüler ürünler");
    },
    [fallbackProducts]
  );

  useEffect(() => {
    const cancelledRef = { current: false };
    const prev = prevPathRef.current;
    prevPathRef.current = pathname;
    const onHome = isHomePath(pathname);
    const dirty =
      typeof window !== "undefined" &&
      !!sessionStorage.getItem(PERSONALIZED_DIRTY_KEY);
    if (!onHome) {
      return () => {
        cancelledRef.current = true;
      };
    }
    if (!auth()?.access_token && !hasMarketingConsent()) {
      setList(takeStripProducts(fallbackProducts));
      return () => {
        cancelledRef.current = true;
      };
    }
    const reason = dirty ? "dirty_flag" : "home_path";
    if (String(prev || "").includes("/urun/") || String(prev || "").includes("/product/")) {
      loadPersonalized(cancelledRef, "return_from_product");
    } else {
      loadPersonalized(cancelledRef, reason);
    }
    return () => {
      cancelledRef.current = true;
    };
  }, [fallbackProducts, loadPersonalized, pathname]);

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

        <div className="rounded-2xl border border-[#04334a]/10 bg-white p-3 md:p-4">
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 md:gap-4">
            {list.map((product, index) => (
              <ProductSlideCard
                key={`${product.id}-${index}`}
                product={product}
              />
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
