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
    borderRadius: 28,
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
              sizes="50vw"
              className="object-cover"
              src={appConfig.BASE_URL + item.image}
              alt={alt}
              loading="lazy"
            />
          )}
        </div>
        <p
          className={`text-sm text-center mt-2.5 px-1 leading-snug ${
            isSelected ? "text-qblack font-700" : "text-qgray"
          }`}
        >
          {displayTurkishLabel(item.name)}
        </p>
      </Link>

      <Link className="hidden md:block w-full" href={productsHref}>
        <div style={frameStyle}>
          {item.image && (
            <Image
              fill
              sizes="(max-width: 1200px) 40vw, 360px"
              className="object-cover transition-transform duration-300 group-hover:scale-[1.03]"
              src={appConfig.BASE_URL + item.image}
              alt={alt}
              loading="lazy"
            />
          )}
        </div>
        <p className="text-base md:text-lg text-qgray text-center mt-3 font-600 group-hover:text-qgreen transition">
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
  const topRow = visible.slice(0, 2);
  const bottomRow = visible.slice(2, 4);

  const rowStyle = {
    display: "grid",
    gridTemplateColumns: "1fr 1fr",
    gap: 12,
    width: "100%",
  };

  const wrapStyle = {
    display: "flex",
    flexDirection: "column",
    gap: 12,
    width: "100%",
    maxWidth: 720,
    margin: "0 auto",
  };

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

        <div style={wrapStyle} className="md:!max-w-[860px] md:!gap-4">
          <div style={rowStyle} className="md:!gap-4">
            {topRow.map((item, i) => (
              <CategoryCard
                key={item.id || item.slug || `top-${i}`}
                item={item}
                selectedCategorySlug={selectedCategorySlug}
                onSelectCategory={onSelectCategory}
              />
            ))}
          </div>

          {bottomRow.length > 0 && (
            <div style={rowStyle} className="md:!gap-4">
              {bottomRow.map((item, i) => (
                <CategoryCard
                  key={item.id || item.slug || `bottom-${i}`}
                  item={item}
                  selectedCategorySlug={selectedCategorySlug}
                  onSelectCategory={onSelectCategory}
                />
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export default CategorySection;
