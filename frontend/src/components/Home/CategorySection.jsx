import React from "react";
import Image from "next/image";
import Link from "next/link";
import appConfig from "@/appConfig";
import { displayTurkishLabel } from "@/utils/turkishDisplay";

function CategoryCard({ item, selectedCategorySlug, onSelectCategory }) {
  const isSelected = selectedCategorySlug === item.slug;
  const productsHref = {
    pathname: "/products",
    query: { category: item.slug },
  };
  const browseHref = `/kategori/${encodeURIComponent(item.slug)}`;
  const alt = `${displayTurkishLabel(item.name)} kategori ikonu`;

  const frameStyle = {
    position: "relative",
    width: "100%",
    aspectRatio: "1 / 1",
    borderRadius: 20,
    overflow: "hidden",
    border: "1px solid #E8E8E8",
    background: "#fff",
    boxShadow: "0 6px 20px rgba(0,0,0,0.06)",
  };

  return (
    <div style={{ width: "100%", minWidth: 0 }} className="cursor-pointer group">
      <Link
        className="md:hidden block w-full text-left"
        href={browseHref}
        onClick={() => onSelectCategory?.(item.slug)}
      >
        <div style={frameStyle}>
          {item.image && (
            <Image
              fill
              sizes="25vw"
              className="object-cover"
              src={appConfig.BASE_URL + item.image}
              alt={alt}
              loading="lazy"
            />
          )}
        </div>
        <p
          className={`text-[11px] sm:text-sm text-center mt-2 px-0.5 leading-snug line-clamp-2 ${
            isSelected ? "text-qblack font-700" : "text-qgray"
          }`}
        >
          {displayTurkishLabel(item.name)}
        </p>
      </Link>

      <Link className="hidden md:block w-full" href={productsHref}>
        <div style={{ ...frameStyle, borderRadius: 28 }}>
          {item.image && (
            <Image
              fill
              sizes="(max-width: 1200px) 22vw, 280px"
              className="object-cover transition-transform duration-300 group-hover:scale-[1.03]"
              src={appConfig.BASE_URL + item.image}
              alt={alt}
              loading="lazy"
            />
          )}
        </div>
        <p className="text-sm lg:text-base text-qgray text-center mt-3 font-600 group-hover:text-qgreen transition line-clamp-2">
          {displayTurkishLabel(item.name)}
        </p>
      </Link>
    </div>
  );
}

function CategorySection({
  sectionTitle: _sectionTitle,
  categories,
  selectedCategorySlug = "",
  onSelectCategory = () => {},
}) {
  const visible = (categories || []).slice(0, 4);

  return (
    <div
      data-aos="fade-up"
      className="category-section-wrapper w-full mobile-floating-safe mobile-floating-safe--categories"
    >
      <div className="container-x mx-auto pb-2 md:pb-4">
        <div className="mb-3 md:hidden flex justify-end">
          <Link
            href="/products"
            className={`inline-flex items-center justify-center rounded-full px-4 py-2.5 text-sm font-600 transition ${
              selectedCategorySlug
                ? "bg-qyellow text-qblack"
                : "bg-qblack text-white"
            }`}
          >
            Tüm Ürünler
          </Link>
        </div>

        <div className="grid grid-cols-4 gap-2 sm:gap-3 md:gap-4 w-full">
          {visible.map((item, i) => (
            <CategoryCard
              key={item.id || item.slug || `cat-${i}`}
              item={item}
              selectedCategorySlug={selectedCategorySlug}
              onSelectCategory={onSelectCategory}
            />
          ))}
        </div>
      </div>
    </div>
  );
}

export default CategorySection;
