import { useState } from "react";
import Checkbox from "../Helpers/Checkbox";
import ServeLangItem from "../Helpers/ServeLangItem";
import FilterToggleIco from "../Helpers/icons/FilterToggleIco";

function ChevronIcon({ open }) {
  return (
    <svg
      className={`h-4 w-4 transition-transform duration-200 ${open ? "rotate-180" : ""}`}
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
    </svg>
  );
}

function SectionTitle({ children }) {
  return (
    <h2 className="mb-4 text-[13px] font-800 uppercase tracking-wide text-[#04334a]">
      {children}
    </h2>
  );
}

function FilterLabel({ htmlFor, children, strong = false }) {
  return (
    <label
      htmlFor={htmlFor}
      className={`cursor-pointer select-none text-[13px] leading-snug text-[#04334a] ${
        strong ? "font-700" : "font-500"
      }`}
    >
      {children}
    </label>
  );
}

const CHECKBOX_CLASS =
  "h-4 w-4 shrink-0 cursor-pointer rounded border-[#04334a]/25 text-[#04334a] accent-[#04334a]";

export default function ProductsFilter({
  categories,
  categoryHandler,
  subCategoryHandler,
  childCategoryHandler,
  varientHandler,
  brandsHandler,
  className,
  filterToggle,
  filterToggleHandler,
  variantsFilter,
  brands,
  clearAllFilters,
  selectedSubCategorySlug,
  selectedChildCategorySlug,
  minPriceInput = "",
  maxPriceInput = "",
  onMinPriceInputChange = () => {},
  onMaxPriceInputChange = () => {},
  onApplyPriceFilter = () => {},
  priceRangeMax = 100000,
}) {
  const [showAllBrands, setShowAllBrands] = useState(false);
  const [expandedCategories, setExpandedCategories] = useState(new Set());
  const [expandedSubCategories, setExpandedSubCategories] = useState(new Set());

  const toggleCategory = (id) => {
    setExpandedCategories((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const toggleSubCategory = (id) => {
    setExpandedSubCategories((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const getSubCategories = (cat) =>
    cat?.active_sub_categories ||
    cat?.activeSubCategories ||
    cat?.sub_categories ||
    cat?.subCategories ||
    [];

  const getChildCategories = (sub) =>
    sub?.active_child_categories ||
    sub?.activeChildCategories ||
    sub?.child_categories ||
    sub?.childCategories ||
    sub?.children ||
    [];

  return (
    <>
      {filterToggle && (
        <button
          type="button"
          className="fixed inset-0 z-[90] bg-[#04334a]/40 backdrop-blur-[1px] lg:hidden"
          aria-label="Filtreleri kapat"
          onClick={filterToggleHandler}
        />
      )}

      <div
        className={`filter-widget w-full fixed inset-0 z-[100] h-screen overflow-y-auto bg-white px-5 pt-6 pb-10 lg:relative lg:inset-auto lg:z-auto lg:h-auto lg:overflow-y-auto lg:rounded-xl lg:border lg:border-[#04334a]/10 lg:shadow-sm lg:px-5 lg:py-5 ${
          className || ""
        } ${filterToggle ? "block" : "hidden lg:block"}`}
      >
        <div className="mb-5 flex items-center justify-between border-b border-[#04334a]/10 pb-4 lg:mb-4">
          <div>
            <p className="text-[11px] font-800 uppercase tracking-widest text-[#04334a]/40">
              Filtrele
            </p>
            <p className="text-base font-800 text-[#04334a]">Ürün filtreleri</p>
          </div>
          <button
            type="button"
            onClick={clearAllFilters}
            className="rounded-lg px-2.5 py-1.5 text-[12px] font-700 text-[#04334a]/60 transition hover:bg-[#FFF8E8] hover:text-[#04334a]"
          >
            Temizle
          </button>
        </div>

        {/* Categories */}
        <div className="border-b border-[#04334a]/10 pb-6">
          <SectionTitle>
            {ServeLangItem()?.Product_categories || "Kategoriler"}
          </SectionTitle>

          <ul className="space-y-2.5">
            {categories && categories.length > 0 ? (
              categories.map((cat) => {
                const subs = getSubCategories(cat);
                const isCatOpen = expandedCategories.has(cat.id);

                return (
                  <li key={cat.id}>
                    <div className="flex items-center justify-between gap-2 rounded-lg px-1 py-0.5 hover:bg-[#F4F6F7]">
                      <div className="flex min-w-0 items-center gap-2.5">
                        <Checkbox
                          id={`cat-${cat.slug}`}
                          name={cat.id}
                          handleChange={categoryHandler}
                          checked={!!cat.selected}
                          className={CHECKBOX_CLASS}
                        />
                        <FilterLabel htmlFor={`cat-${cat.slug}`} strong>
                          {cat.name}
                        </FilterLabel>
                      </div>
                      {subs.length > 0 && (
                        <button
                          type="button"
                          onClick={() => toggleCategory(cat.id)}
                          className="shrink-0 rounded-md p-1 text-[#04334a]/45 transition hover:bg-white hover:text-[#04334a]"
                          aria-label={isCatOpen ? "Kapat" : "Alt kategorileri göster"}
                        >
                          <ChevronIcon open={isCatOpen} />
                        </button>
                      )}
                    </div>

                    {subs.length > 0 && isCatOpen && (
                      <ul className="mt-2 ml-3 space-y-2 border-l-2 border-[#FCBF49]/50 pl-3">
                        {subs.map((sub) => {
                          const children = getChildCategories(sub);
                          const isSubOpen = expandedSubCategories.has(sub.id);
                          const isSubSelected = Array.isArray(selectedSubCategorySlug)
                            ? selectedSubCategorySlug.includes(sub.slug)
                            : selectedSubCategorySlug === sub.slug;

                          return (
                            <li key={sub.id}>
                              <div className="flex items-center justify-between gap-2">
                                <div className="flex min-w-0 items-center gap-2">
                                  <Checkbox
                                    id={`sub-${sub.slug}`}
                                    name={sub.slug}
                                    handleChange={subCategoryHandler}
                                    checked={isSubSelected}
                                    className={CHECKBOX_CLASS}
                                  />
                                  <FilterLabel htmlFor={`sub-${sub.slug}`}>
                                    {sub.name}
                                  </FilterLabel>
                                </div>
                                {children.length > 0 && (
                                  <button
                                    type="button"
                                    onClick={() => toggleSubCategory(sub.id)}
                                    className="shrink-0 rounded-md p-1 text-[#04334a]/40 hover:text-[#04334a]"
                                    aria-label={
                                      isSubOpen ? "Kapat" : "Alt kategorileri göster"
                                    }
                                  >
                                    <ChevronIcon open={isSubOpen} />
                                  </button>
                                )}
                              </div>

                              {children.length > 0 && isSubOpen && (
                                <ul className="mt-1.5 ml-2 space-y-1.5 border-l border-[#04334a]/15 pl-3">
                                  {children.map((child) => {
                                    const isChildSelected =
                                      selectedChildCategorySlug === child.slug;
                                    return (
                                      <li key={child.id}>
                                        <div className="flex items-center gap-2">
                                          <Checkbox
                                            id={`child-${child.slug}`}
                                            name={child.slug}
                                            handleChange={childCategoryHandler}
                                            checked={isChildSelected}
                                            className={CHECKBOX_CLASS}
                                          />
                                          <FilterLabel htmlFor={`child-${child.slug}`}>
                                            {child.name}
                                          </FilterLabel>
                                        </div>
                                      </li>
                                    );
                                  })}
                                </ul>
                              )}
                            </li>
                          );
                        })}
                      </ul>
                    )}
                  </li>
                );
              })
            ) : (
              <li className="text-[13px] text-[#04334a]/45">Kategori bulunamadı</li>
            )}
          </ul>
        </div>

        {/* Price */}
        <div className="border-b border-[#04334a]/10 py-6">
          <SectionTitle>Fiyat Aralığı (₺)</SectionTitle>
          <div className="flex flex-col gap-2.5">
            <div className="flex gap-2">
              <input
                type="number"
                min="0"
                max={priceRangeMax}
                value={minPriceInput}
                onChange={(e) => onMinPriceInputChange(e.target.value)}
                placeholder="Min"
                className="h-10 w-1/2 rounded-lg border-0 bg-[#F4F6F7] px-3 text-[13px] text-[#04334a] placeholder:text-[#04334a]/35 focus:outline-none focus:ring-2 focus:ring-[#FCBF49]/50"
              />
              <input
                type="number"
                min="0"
                max={priceRangeMax}
                value={maxPriceInput}
                onChange={(e) => onMaxPriceInputChange(e.target.value)}
                placeholder={`Max (${priceRangeMax.toLocaleString("tr-TR")})`}
                className="h-10 w-1/2 rounded-lg border-0 bg-[#F4F6F7] px-3 text-[13px] text-[#04334a] placeholder:text-[#04334a]/35 focus:outline-none focus:ring-2 focus:ring-[#FCBF49]/50"
              />
            </div>
            <button
              type="button"
              onClick={onApplyPriceFilter}
              className="h-10 w-full rounded-lg bg-qyellow text-[13px] font-800 text-[#04334a] transition hover:brightness-95"
            >
              Fiyat filtresini uygula
            </button>
          </div>
        </div>

        {/* Brands */}
        {brands && brands.length > 0 && (
          <div className="border-b border-[#04334a]/10 py-6">
            <SectionTitle>{ServeLangItem()?.Brands || "Markalar"}</SectionTitle>
            <ul className="space-y-2.5">
              {(showAllBrands ? brands : brands.slice(0, 10)).map((brand, i) => (
                <li key={brand.id || i} className="flex items-center gap-2.5">
                  <Checkbox
                    id={`brand-${brand.id || brand.name}`}
                    name={brand.id}
                    handleChange={brandsHandler}
                    checked={!!brand.selected}
                    className={CHECKBOX_CLASS}
                  />
                  <FilterLabel htmlFor={`brand-${brand.id || brand.name}`}>
                    {brand.name}
                  </FilterLabel>
                </li>
              ))}
            </ul>
            {brands.length > 10 && (
              <button
                type="button"
                onClick={() => setShowAllBrands((v) => !v)}
                className="mt-3 text-[12px] font-700 text-[#04334a] underline underline-offset-2 hover:text-qyellow"
              >
                {showAllBrands
                  ? "Daha az göster"
                  : `Daha fazla gör (+${brands.length - 10})`}
              </button>
            )}
          </div>
        )}

        {/* Variants */}
        {variantsFilter &&
          variantsFilter.length > 0 &&
          variantsFilter.map((variant, i) => (
            <div
              key={i}
              className="border-b border-[#04334a]/10 py-6 last:border-b-0"
            >
              <SectionTitle>{variant.name}</SectionTitle>
              <ul className="space-y-2.5">
                {variant.active_variant_items &&
                  variant.active_variant_items.length > 0 &&
                  variant.active_variant_items.map((item, j) => (
                    <li key={j} className="flex items-center gap-2.5">
                      <Checkbox
                        id={`variant-${variant.name}-${item.name}`}
                        name={item.name}
                        handleChange={varientHandler}
                        checked={!!item.selected}
                        className={CHECKBOX_CLASS}
                      />
                      <FilterLabel htmlFor={`variant-${variant.name}-${item.name}`}>
                        {item.name}
                      </FilterLabel>
                    </li>
                  ))}
              </ul>
            </div>
          ))}

        <button
          onClick={clearAllFilters}
          type="button"
          className="mt-5 w-full rounded-lg border border-[#E11D48]/25 bg-[#FFF5F5] py-2.5 text-[13px] font-700 text-[#E11D48] transition hover:bg-[#FFE8E8]"
        >
          Tüm filtreleri temizle
        </button>

        <button
          onClick={filterToggleHandler}
          type="button"
          className="fixed right-4 top-4 z-[110] flex h-10 w-10 items-center justify-center rounded-lg border border-[#04334a]/15 bg-white text-[#04334a] shadow-md lg:hidden"
          aria-label="Filtreleri kapat"
        >
          <FilterToggleIco />
        </button>
      </div>
    </>
  );
}
