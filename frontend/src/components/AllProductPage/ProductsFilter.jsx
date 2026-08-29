import { useState } from "react";
import Checkbox from "../Helpers/Checkbox";
import ServeLangItem from "../Helpers/ServeLangItem";
import FilterToggleIco from "../Helpers/icons/FilterToggleIco";

function ChevronIcon({ open }) {
  return (
    <svg
      className={`w-4 h-4 transition-transform duration-200 ${open ? "rotate-180" : ""}`}
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
    </svg>
  );
}

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
  selectedSubCategorySlug,   // array of slugs
  selectedChildCategorySlug,
  minPriceInput = "",
  maxPriceInput = "",
  onMinPriceInputChange = () => {},
  onMaxPriceInputChange = () => {},
  onApplyPriceFilter = () => {},
  priceRangeMax = 100000,
}) {
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
      {/* Mobile overlay backdrop */}
      {filterToggle && (
        <button
          type="button"
          className="fixed inset-0 z-[90] bg-black/50 lg:hidden"
          aria-label="Filtreleri kapat"
          onClick={filterToggleHandler}
        />
      )}

      <div
        className={`filter-widget w-full fixed inset-0 z-[100] h-screen overflow-y-auto bg-white px-[30px] pt-[40px] lg:relative lg:inset-auto lg:z-auto lg:h-auto lg:overflow-y-auto ${
          className || ""
        } ${filterToggle ? "block" : "hidden lg:block"}`}
      >
        {/* ─── Category Tree ─── */}
        <div className="filter-subject-item pb-10 border-b border-qgray-border">
          <div className="subject-title mb-[30px]">
            <h2 className="text-black text-base font-500">
              {ServeLangItem()?.Product_categories || "Kategoriler"}
            </h2>
          </div>

          <ul>
            {categories && categories.length > 0 ? (
              categories.map((cat) => {
                const subs = getSubCategories(cat);
                const isCatOpen = expandedCategories.has(cat.id);

                return (
                  <li key={cat.id} className="mb-3">
                    {/* Main category row */}
                    <div className="flex items-center justify-between">
                      <div className="flex items-center space-x-[14px]">
                        <Checkbox
                          id={`cat-${cat.slug}`}
                          name={cat.id}
                          handleChange={categoryHandler}
                          checked={!!cat.selected}
                        />
                        <label
                          htmlFor={`cat-${cat.slug}`}
                          className="text-xs font-bold capitalize cursor-pointer select-none"
                        >
                          {cat.name}
                        </label>
                      </div>
                      {subs.length > 0 && (
                        <button
                          type="button"
                          onClick={() => toggleCategory(cat.id)}
                          className="p-1 text-qgray hover:text-qblack transition-colors"
                          aria-label={isCatOpen ? "Kapat" : "Alt kategorileri göster"}
                        >
                          <ChevronIcon open={isCatOpen} />
                        </button>
                      )}
                    </div>

                    {/* Sub-categories (2nd level) */}
                    {subs.length > 0 && isCatOpen && (
                      <ul className="mt-2 ml-5 border-l-2 border-qgray-border pl-3">
                        {subs.map((sub) => {
                          const children = getChildCategories(sub);
                          const isSubOpen = expandedSubCategories.has(sub.id);
                          const isSubSelected = Array.isArray(selectedSubCategorySlug)
                            ? selectedSubCategorySlug.includes(sub.slug)
                            : selectedSubCategorySlug === sub.slug;

                          return (
                            <li key={sub.id} className="mb-2">
                              <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-[10px]">
                                  <Checkbox
                                    id={`sub-${sub.slug}`}
                                    name={sub.slug}
                                    handleChange={subCategoryHandler}
                                    checked={isSubSelected}
                                  />
                                  <label
                                    htmlFor={`sub-${sub.slug}`}
                                    className="text-xs capitalize cursor-pointer select-none"
                                  >
                                    {sub.name}
                                  </label>
                                </div>
                                {children.length > 0 && (
                                  <button
                                    type="button"
                                    onClick={() => toggleSubCategory(sub.id)}
                                    className="p-1 text-qgray hover:text-qblack transition-colors"
                                    aria-label={isSubOpen ? "Kapat" : "Alt kategorileri göster"}
                                  >
                                    <ChevronIcon open={isSubOpen} />
                                  </button>
                                )}
                              </div>

                              {/* Child categories (3rd level) */}
                              {children.length > 0 && isSubOpen && (
                                <ul className="mt-1 ml-4 border-l-2 border-qgray-border pl-3">
                                  {children.map((child) => {
                                    const isChildSelected =
                                      selectedChildCategorySlug === child.slug;
                                    return (
                                      <li key={child.id} className="mb-2">
                                        <div className="flex items-center space-x-[10px]">
                                          <Checkbox
                                            id={`child-${child.slug}`}
                                            name={child.slug}
                                            handleChange={childCategoryHandler}
                                            checked={isChildSelected}
                                          />
                                          <label
                                            htmlFor={`child-${child.slug}`}
                                            className="text-xs capitalize cursor-pointer select-none"
                                          >
                                            {child.name}
                                          </label>
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
              <li className="text-xs text-qgray">Kategori bulunamadı</li>
            )}
          </ul>
        </div>


        {/* ─── Price Filter ─── */}
        <div className="filter-subject-item pb-10 border-b border-qgray-border mt-10">
          <div className="subject-title mb-[20px]">
            <h2 className="text-black text-base font-500">Fiyat Aralığı (₺)</h2>
          </div>
          <div className="flex flex-col gap-3">
            <div className="flex gap-2">
              <input
                type="number"
                min="0"
                max={priceRangeMax}
                value={minPriceInput}
                onChange={(e) => onMinPriceInputChange(e.target.value)}
                placeholder="Min"
                className="w-1/2 h-10 px-3 border border-qgray-border rounded text-xs"
              />
              <input
                type="number"
                min="0"
                max={priceRangeMax}
                value={maxPriceInput}
                onChange={(e) => onMaxPriceInputChange(e.target.value)}
                placeholder={`Max (${priceRangeMax.toLocaleString("tr-TR")})`}
                className="w-1/2 h-10 px-3 border border-qgray-border rounded text-xs"
              />
            </div>
            <button
              type="button"
              onClick={onApplyPriceFilter}
              className="w-full h-10 bg-qyellow text-qblack text-sm font-semibold rounded"
            >
              Fiyat filtresini uygula
            </button>
          </div>
        </div>

        {/* ─── Brands ─── */}
        {brands && brands.length > 0 && (
          <div className="filter-subject-item pb-10 border-b border-qgray-border mt-10">
            <div className="subject-title mb-[30px]">
              <h2 className="text-black text-base font-500">
                {ServeLangItem()?.Brands || "Markalar"}
              </h2>
            </div>
            <ul>
              {brands.map((brand, i) => (
                <li key={i} className="item flex justify-between items-center mb-5">
                  <div className="flex space-x-[14px] items-center">
                    <Checkbox
                      id={`brand-${brand.name}`}
                      name={brand.id}
                      handleChange={brandsHandler}
                      checked={!!brand.selected}
                    />
                    <label
                      htmlFor={`brand-${brand.name}`}
                      className="text-xs font-400 capitalize cursor-pointer"
                    >
                      {brand.name}
                    </label>
                  </div>
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* ─── Variants ─── */}
        {variantsFilter &&
          variantsFilter.length > 0 &&
          variantsFilter.map((variant, i) => (
            <div
              key={i}
              className="filter-subject-item pb-10 border-b border-qgray-border mt-10"
            >
              <div className="subject-title mb-[30px]">
                <h2 className="text-black text-base font-500">{variant.name}</h2>
              </div>
              <ul>
                {variant.active_variant_items &&
                  variant.active_variant_items.length > 0 &&
                  variant.active_variant_items.map((item, j) => (
                    <li key={j} className="item flex justify-between items-center mb-5">
                      <div className="flex space-x-[14px] items-center">
                        <Checkbox
                          id={`variant-${item.name}`}
                          name={item.name}
                          handleChange={varientHandler}
                          checked={!!item.selected}
                        />
                        <label
                          htmlFor={`variant-${item.name}`}
                          className="text-xs font-400 capitalize cursor-pointer"
                        >
                          {item.name}
                        </label>
                      </div>
                    </li>
                  ))}
              </ul>
            </div>
          ))}

        {/* ─── Clear all ─── */}
        <button
          onClick={clearAllFilters}
          type="button"
          className="w-full text-sm my-4 text-start text-qred transition-colors duration-200 font-medium"
        >
          Tüm filtreleri temizle
        </button>

        {/* ─── Close button (mobile) ─── */}
        <button
          onClick={filterToggleHandler}
          type="button"
          className="w-10 h-10 fixed top-5 right-5 z-[110] rounded lg:hidden flex justify-center items-center border border-qred text-qred bg-white shadow-sm"
        >
          <FilterToggleIco />
        </button>
      </div>
    </>
  );
}
