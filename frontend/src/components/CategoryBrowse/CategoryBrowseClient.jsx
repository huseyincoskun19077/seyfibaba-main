"use client";

import Link from "next/link";
import { useMemo } from "react";
import { useSelector } from "react-redux";
import { displayTurkishLabel } from "@/utils/turkishDisplay";

function getSubs(category) {
  if (!category) return [];
  return (
    category.active_sub_categories ||
    category.activeSubCategories ||
    category.sub_categories ||
    category.subCategories ||
    []
  );
}

function getChildren(sub) {
  if (!sub) return [];
  return (
    sub.active_child_categories ||
    sub.activeChildCategories ||
    sub.child_categories ||
    sub.childCategories ||
    []
  );
}

function Tile({ href, title, highlight = false }) {
  return (
    <Link
      href={href}
      className={`flex min-h-[108px] items-center justify-center rounded-2xl border px-2 py-3 text-center transition active:scale-[0.98] ${
        highlight
          ? "border-qyellow bg-qyellow/20 text-qblack"
          : "border-gray-200 bg-white text-qblack hover:border-amber-200 hover:bg-amber-50/40"
      }`}
    >
      <span className="text-xs font-700 leading-snug line-clamp-3">
        {displayTurkishLabel(title)}
      </span>
    </Link>
  );
}

export default function CategoryBrowseClient({ categorySlug, subSlug = null }) {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const productCategories = websiteSetup?.payload?.productCategories;

  const { category, subCategory, items, allProductsHref, title, backHref } =
    useMemo(() => {
      const list = Array.isArray(productCategories) ? productCategories : [];
      const cat =
        list.find((c) => String(c.slug) === String(categorySlug)) || null;
      const subs = getSubs(cat);

      if (!subSlug) {
        return {
          category: cat,
          subCategory: null,
          items: subs.map((s) => ({
            key: s.id || s.slug,
            title: s.name,
            href: `/kategori/${encodeURIComponent(categorySlug)}/${encodeURIComponent(s.slug)}`,
          })),
          allProductsHref: cat
            ? `/products?category=${encodeURIComponent(cat.slug)}`
            : "/products",
          title: cat?.name || "Kategori",
          backHref: "/",
        };
      }

      const sub =
        subs.find((s) => String(s.slug) === String(subSlug)) || null;
      const children = getChildren(sub);
      return {
        category: cat,
        subCategory: sub,
        items: children.map((ch) => ({
          key: ch.id || ch.slug,
          title: ch.name,
          href: `/products?child_category=${encodeURIComponent(ch.slug)}`,
        })),
        allProductsHref: sub
          ? `/products?sub_category=${encodeURIComponent(sub.slug)}`
          : cat
            ? `/products?category=${encodeURIComponent(cat.slug)}`
            : "/products",
        title: sub?.name || "Alt kategori",
        backHref: cat
          ? `/kategori/${encodeURIComponent(cat.slug)}`
          : "/",
      };
    }, [productCategories, categorySlug, subSlug]);

  if (!category) {
    return (
      <div className="container-x mx-auto py-16 px-4 text-center">
        <p className="text-qblack font-700 mb-3">Kategori bulunamadı</p>
        <Link href="/products" className="text-sm font-700 text-qyellow underline">
          Tüm ürünlere git
        </Link>
      </div>
    );
  }

  if (subSlug && !subCategory) {
    return (
      <div className="container-x mx-auto py-16 px-4 text-center">
        <p className="text-qblack font-700 mb-3">Alt kategori bulunamadı</p>
        <Link href={backHref} className="text-sm font-700 text-qyellow underline">
          Geri dön
        </Link>
      </div>
    );
  }

  return (
    <div className="w-full min-h-[60vh] bg-white pb-16">
      <div className="sticky top-0 z-20 border-b border-gray-100 bg-white/95 backdrop-blur">
        <div className="container-x mx-auto flex h-14 items-center justify-between gap-3 px-4">
          <div className="flex min-w-0 items-center gap-2">
            <Link
              href={backHref}
              className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-qblack hover:bg-gray-100"
              aria-label="Geri"
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden>
                <path d="M15 18l-6-6 6-6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </Link>
            <h1 className="truncate text-base font-800 text-qblack">
              {displayTurkishLabel(title)}
            </h1>
          </div>
          <Link
            href={allProductsHref}
            className="shrink-0 text-sm font-800 text-emerald-700 hover:underline"
          >
            Tüm Ürünler
          </Link>
        </div>
      </div>

      <div className="container-x mx-auto px-4 pt-5">
        <div className="grid grid-cols-3 gap-2.5 sm:gap-3">
          {items.map((item) => (
            <Tile key={item.key} href={item.href} title={item.title} />
          ))}
          <Tile href={allProductsHref} title="Tüm Ürünler" highlight />
        </div>

        {items.length === 0 ? (
          <p className="mt-6 text-center text-sm text-qgray">
            Alt kategori yok. Sağ üstten tüm ürünlere gidebilirsiniz.
          </p>
        ) : null}
      </div>
    </div>
  );
}
