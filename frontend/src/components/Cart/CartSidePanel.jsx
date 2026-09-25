"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { useDispatch, useSelector } from "react-redux";
import {
  deleteItemAction,
  setItemQty,
} from "@/redux/features/cart/cartSlice";
import { useFlyingCart } from "@/components/Contexts/FlyingCartContext";
import useRefreshCartPrices from "@/hooks/useRefreshCartPrices";
import { getProductImageProps } from "@/utils/productImage";
import { resolveCartLineUnitPrice } from "@/utils/variantPricing";
import { buildProductPath } from "@/utils/url";

const CONTAINER_MAX = 1520;
const PANEL_STORAGE_KEY = "sb_cart_panel_open";

import { formatMoneyTR } from "@/utils/priceFormat";

function formatTl(value) {
  return formatMoneyTR(value);
}

/** container-x sağ boşluğu (margin + padding) — panel bunu aşmaz */
function measureRightGutter() {
  if (typeof window === "undefined") return 160;
  const vw = window.innerWidth;
  const sideMargin = Math.max(0, (vw - CONTAINER_MAX) / 2);
  let pad = 12;
  if (vw >= 1280) pad = 24;
  else if (vw >= 768) pad = 20;
  else if (vw >= 640) pad = 16;
  return sideMargin + pad;
}

export default function CartSidePanel() {
  const dispatch = useDispatch();
  const { cart } = useSelector((state) => state.cart);
  const {
    isCartDrawerOpen,
    openCartDrawer,
    closeCartDrawer,
  } = useFlyingCart();

  const items = cart?.cartProducts || [];
  useRefreshCartPrices(items, {
    enabled: Boolean(items.length) && isCartDrawerOpen,
    notify: false,
  });

  const [mounted, setMounted] = useState(false);
  const [panelWidth, setPanelWidth] = useState(140);
  const prevQtyRef = useRef(null);

  useEffect(() => {
    setMounted(true);
    try {
      if (sessionStorage.getItem(PANEL_STORAGE_KEY) === "1") {
        openCartDrawer();
      }
    } catch {
      /* ignore */
    }
  }, [openCartDrawer]);

  useEffect(() => {
    if (!mounted) return;
    try {
      sessionStorage.setItem(
        PANEL_STORAGE_KEY,
        isCartDrawerOpen ? "1" : "0"
      );
    } catch {
      /* ignore */
    }
  }, [isCartDrawerOpen, mounted]);

  useEffect(() => {
    const update = () => {
      const gutter = measureRightGutter();
      // Sağ boşluğun içine çek; max 140px — ürün ızgarasının üstüne binmesin
      setPanelWidth(gutter >= 100 ? Math.min(gutter, 140) : 112);
    };
    update();
    window.addEventListener("resize", update);
    return () => window.removeEventListener("resize", update);
  }, []);

  const totalQty = useMemo(
    () => items.reduce((sum, item) => sum + (Number(item.qty) || 1), 0),
    [items]
  );

  const subtotal = useMemo(
    () =>
      items.reduce((sum, item) => {
        const unit = Number(resolveCartLineUnitPrice(item) || 0);
        return sum + unit * (Number(item.qty) || 1);
      }, 0),
    [items]
  );

  // Sepete ürün eklenince aç; açık kalsın (küçültmeyene kadar)
  useEffect(() => {
    if (!mounted) return;
    if (prevQtyRef.current === null) {
      prevQtyRef.current = totalQty;
      return;
    }
    if (totalQty > prevQtyRef.current) {
      openCartDrawer();
    }
    prevQtyRef.current = totalQty;
  }, [totalQty, mounted, openCartDrawer]);

  if (!mounted) return null;

  const compact = true;

  return (
    <aside
      className={`fixed top-0 right-0 z-[80] flex h-full flex-col border-l border-[#04334a]/10 bg-white shadow-[-8px_0_24px_rgba(4,51,74,0.08)] transition-transform duration-300 ease-out ${
        isCartDrawerOpen ? "translate-x-0" : "translate-x-full"
      }`}
      style={{ width: panelWidth }}
      role="complementary"
      aria-label="Sepet"
      aria-hidden={!isCartDrawerOpen}
    >
      <div
        className={`flex flex-col gap-2 border-b border-[#04334a]/10 ${
          compact ? "px-2 pt-3 pb-2" : "px-3 pt-4 pb-3"
        }`}
      >
        <Link
          href="/checkout"
          className={`flex items-center justify-center rounded-md font-800 transition ${
            compact ? "h-9 px-1 text-[11px] leading-tight text-center" : "h-10 text-xs"
          } ${
            items.length
              ? "bg-qyellow text-[#04334a] hover:brightness-95"
              : "pointer-events-none bg-[#04334a]/15 text-[#04334a]/40"
          }`}
        >
          {compact
            ? `Ödemeye Geç${items.length ? ` (${totalQty})` : ""}`
            : `Ödemeye Geç${items.length ? ` (${totalQty} ürün)` : ""}`}
        </Link>
        <Link
          href="/cart"
          className={`flex items-center justify-center rounded-md border-2 border-[#04334a] font-800 text-[#04334a] hover:bg-[#04334a]/[0.04] transition ${
            compact ? "h-9 text-[11px]" : "h-10 text-xs"
          }`}
        >
          Sepete Git
        </Link>
      </div>

      <div className={`border-b border-[#04334a]/08 ${compact ? "px-2 py-2" : "px-3 py-3"}`}>
        <p className={`font-800 text-[#04334a] ${compact ? "text-[11px]" : "text-sm"}`}>
          Ara Toplam
        </p>
        <div className={`mt-1.5 rounded-md bg-qyellow/25 ${compact ? "px-2 py-1.5" : "px-2.5 py-2"}`}>
          <p
            className={`font-800 text-[#04334a] tabular-nums leading-tight ${
              compact ? "text-sm" : "text-base"
            }`}
          >
            {formatTl(subtotal)}
          </p>
        </div>
      </div>

      <div className={`flex-1 overflow-y-auto ${compact ? "px-2 py-2" : "px-3 py-3"}`}>
        {!items.length ? (
          <p className="py-8 text-center text-[11px] text-[#04334a]/45">
            Sepetiniz boş
          </p>
        ) : (
          <ul className="space-y-3">
            {items.map((item) => {
              const unit = Number(resolveCartLineUnitPrice(item) || 0);
              const lineTotal = unit * (Number(item.qty) || 1);
              const name = item?.product?.name || "Ürün";
              const slug = item?.product?.slug;
              const qty = Math.max(1, Number(item.qty) || 1);
              const imgSize = compact ? 56 : 64;

              return (
                <li
                  key={`${item.product_id}-${JSON.stringify(
                    (item.variants || []).map((v) => v.variant_item_id)
                  )}`}
                  className="flex flex-col gap-1.5"
                >
                  <div className="relative mx-auto" style={{ width: imgSize, height: imgSize }}>
                    <span className="absolute -left-0.5 -top-0.5 z-10 flex h-3.5 w-3.5 items-center justify-center rounded-[2px] bg-qyellow text-[#04334a]">
                      <svg width="8" height="8" viewBox="0 0 12 12" fill="none" aria-hidden>
                        <path
                          d="M2 6.2L4.6 9 10 3"
                          stroke="currentColor"
                          strokeWidth="2"
                          strokeLinecap="round"
                          strokeLinejoin="round"
                        />
                      </svg>
                    </span>
                    <div className="relative h-full w-full overflow-hidden rounded border border-[#04334a]/10 bg-neutral-50">
                      {item?.product?.thumb_image ? (
                        <Image
                          fill
                          sizes={`${imgSize}px`}
                          {...getProductImageProps(item.product.thumb_image)}
                          alt={name}
                          className="object-contain p-0.5"
                        />
                      ) : null}
                    </div>
                  </div>

                  {slug ? (
                    <Link
                      href={buildProductPath(slug)}
                      className="line-clamp-2 text-center text-[10px] font-600 leading-snug text-[#04334a] hover:underline"
                    >
                      {name}
                    </Link>
                  ) : (
                    <p className="line-clamp-2 text-center text-[10px] font-600 leading-snug text-[#04334a]">
                      {name}
                    </p>
                  )}
                  <p className="text-center text-xs font-800 text-[#04334a] tabular-nums">
                    {formatTl(lineTotal)}
                  </p>
                  {(() => {
                    const saleUnit = Math.max(
                      1,
                      parseInt(item?.product?.sale_unit_qty, 10) || 1
                    );
                    return (
                      <p className="text-center text-[10px] text-[#04334a]/55">
                        İçindeki miktar: {saleUnit} adet
                      </p>
                    );
                  })()}
                  <div className="flex items-center justify-center gap-1.5">
                    <select
                      value={qty}
                      onChange={(e) =>
                        dispatch(
                          setItemQty({
                            productId: item.product_id,
                            qty: e.target.value,
                          })
                        )
                      }
                      className="h-7 min-w-[44px] rounded border border-[#04334a]/20 bg-white px-1 text-[11px] font-700 text-[#04334a] outline-none"
                      aria-label="Adet"
                    >
                      {Array.from({ length: 10 }, (_, i) => i + 1).map((n) => (
                        <option key={n} value={n}>
                          {n}
                        </option>
                      ))}
                      {qty > 10 ? <option value={qty}>{qty}</option> : null}
                    </select>
                    <button
                      type="button"
                      onClick={() => dispatch(deleteItemAction(item.product_id))}
                      className="inline-flex h-7 w-7 items-center justify-center rounded text-[#04334a]/40 hover:bg-[#04334a]/[0.06] hover:text-[#04334a]"
                      aria-label="Ürünü sil"
                    >
                      <svg
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="1.8"
                        aria-hidden
                      >
                        <path d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-9 0v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V7M10 11v6M14 11v6" />
                      </svg>
                    </button>
                  </div>
                </li>
              );
            })}
          </ul>
        )}
      </div>

      <div className={`border-t border-[#04334a]/10 ${compact ? "px-2 py-2" : "px-3 py-3"}`}>
        <button
          type="button"
          onClick={closeCartDrawer}
          className="inline-flex w-full items-center justify-center gap-1.5 text-[11px] font-700 text-[#04334a]/55 hover:text-[#04334a] transition"
        >
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden>
            <rect
              x="4"
              y="4"
              width="16"
              height="16"
              rx="2"
              stroke="currentColor"
              strokeWidth="1.8"
            />
            <path
              d="M10 8l4 4-4 4"
              stroke="currentColor"
              strokeWidth="1.8"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </svg>
          Küçült
        </button>
      </div>
    </aside>
  );
}
