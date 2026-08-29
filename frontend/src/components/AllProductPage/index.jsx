"use client";
import Image from "next/image";
import { useEffect, useState, useRef, Suspense } from "react";
import Link from "next/link";
import { useRouter, useSearchParams, usePathname } from "next/navigation";
import Star from "../Helpers/icons/Star";
import ProductsFilter from "./ProductsFilter";
import ListingSearchBar from "./ListingSearchBar";
import LoaderStyleOne from "../Helpers/Loaders/LoaderStyleOne";
import ServeLangItem from "../Helpers/ServeLangItem";
import ProductCard from "../Helpers/Cards/ProductCard";
import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";
import ShopEmailIco from "../Helpers/icons/ShopEmailIco";
import ShopPhoneIco from "../Helpers/icons/ShopPhoneIco";
import ShopLocationIco from "../Helpers/icons/ShopLocationIco";
import ShopArrowIco from "../Helpers/icons/ShopArrowIco";
import ViewColIco from "../Helpers/icons/ViewColIco";
import ViewRowIco from "../Helpers/icons/ViewRowIco";
import FilterIco from "../Helpers/icons/FilterIco";
import {
  useLazyGetAllProductsApiQuery,
  useLazyNextPageProductsApiQuery,
} from "@/redux/features/product/apiSlice";

function AllProductPageContent({ response, sellerInfo }) {
  // Next.js routing hooks
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();

  // Product data states
  const [resProducts, setProducts] = useState(response?.products?.data || []);
  const [nxtPage, setNxtPage] = useState(response?.products?.next_page_url || null);

  // Filter states
  const [variantsFilter, setVariantsFilter] = useState(null);
  const [categoriesFilter, setCategoriesFilter] = useState(null);
  const [subCategoriesFilter, setSubCategoriesFilter] = useState([]);
  const [brands, setBrands] = useState(null);

  // UI states
  const [cardViewStyle, setCardViewStyle] = useState("col");
  const [filterToggle, setToggle] = useState(false);
  const [isFiltering, setIsFiltering] = useState(false);

  useEffect(() => {
    if (typeof document === "undefined") return;
    if (!filterToggle) return;
    const prevOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.body.style.overflow = prevOverflow;
    };
  }, [filterToggle]);

  // Selected filter items states
  const [selectedVarientFilterItem, setSelectedVarientFilterItem] = useState(
    []
  );
  const [selectedCategoryFilterItem, setSelectedCategoryFilterItem] = useState(
    []
  );
  const [selectedSubCategorySlug, setSelectedSubCategorySlug] = useState([]);
  const [selectedChildCategorySlug, setSelectedChildCategorySlug] = useState("");
  const [selectedBrandsFilterItem, setSelectedBrandsFilterItem] = useState([]);
  const [desktopFilterOpen, setDesktopFilterOpen] = useState(true);
  const [minPriceInput, setMinPriceInput] = useState("");
  const [maxPriceInput, setMaxPriceInput] = useState("");
  const [appliedMinPrice, setAppliedMinPrice] = useState("");
  const [appliedMaxPrice, setAppliedMaxPrice] = useState("");
  const [sortId, setSortId] = useState("");
  const [listingSearch, setListingSearch] = useState("");
  const [productTotal, setProductTotal] = useState(response?.products?.total || 0);
  const priceRangeMax = Number(response?.shopPage?.filter_price_range) || 100000;

  const getCurrentFilterPayload = (overrides = {}) => ({
    brands: ensureArray(selectedBrandsFilterItem),
    categories: ensureArray(selectedCategoryFilterItem),
    variantItems: ensureArray(selectedVarientFilterItem),
    sub_categories: ensureArray(selectedSubCategorySlug),
    child_category: selectedChildCategorySlug,
    min_price: appliedMinPrice,
    max_price: appliedMaxPrice,
    shorting_id: sortId,
    search: listingSearch,
    ...overrides,
  });

  // Track previous response to detect real response changes vs searchParams changes
  const prevResponseRef = useRef(null);

  // Helper function to ensure arrays are always arrays
  const ensureArray = (value) => {
    return Array.isArray(value) ? value : [];
  };

  // Debounce function to prevent too frequent URL updates
  const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  };

  // Debounced URL update function (will be set after updateURL is defined)
  let debouncedUpdateURL;

  /**
   * Update URL with current filter parameters
   */
  const updateURL = (filters) => {
    try {
      // Check if router and pathname are available
      if (!router || !pathname) {
        return;
      }

      // Check if searchParams is available and valid
      if (!searchParams || typeof searchParams.get !== "function") {
        return;
      }

      const params = new URLSearchParams();

      // Preserve original page parameters (category, brand, type, slug, seller, etc.)
      // These are used by the page component to determine what products to load
      const originalParams = [
        "category",
        "sub_category",
        "child_category",
        "brand",
        "highlight",
        "search",
        "type",
        "slug",
        "seller",
      ];
      originalParams.forEach((param) => {
        try {
          const value = searchParams.get(param);
          if (value) {
            params.set(param, value);
          }
        } catch (error) {
          // Silent error handling
        }
      });

      // Validate filters object
      if (!filters || typeof filters !== "object") {
        console.error("Invalid filters object:", filters);
        return;
      }

      // Add new filter params with validation and limits
      if (
        filters.brands &&
        Array.isArray(filters.brands) &&
        filters.brands.length > 0
      ) {
        // Limit brands to first 5 to prevent URL from getting too long
        const limitedBrands = filters.brands.slice(0, 5);
        params.set("brands", limitedBrands.join(","));
      }

      if (
        filters.categories &&
        Array.isArray(filters.categories) &&
        filters.categories.length > 0
      ) {
        // Limit categories to first 5 to prevent URL from getting too long
        const limitedCategories = filters.categories.slice(0, 5);
        params.set("categories", limitedCategories.join(","));
      }

      if (
        filters.variantItems &&
        Array.isArray(filters.variantItems) &&
        filters.variantItems.length > 0
      ) {
        // Limit variant items to first 3 to prevent URL from getting too long
        const limitedVariants = filters.variantItems.slice(0, 3);
        params.set("variantItems", limitedVariants.join(","));
      }

      if (
        filters.sub_categories &&
        Array.isArray(filters.sub_categories) &&
        filters.sub_categories.length > 0
      ) {
        params.set("sub_categories", filters.sub_categories.join(","));
      } else {
        params.delete("sub_categories");
      }

      if (filters.child_category) {
        params.set("child_category", String(filters.child_category));
      } else {
        params.delete("child_category");
      }

      if (filters.min_price) {
        params.set("min_price", String(filters.min_price));
      } else {
        params.delete("min_price");
      }

      if (filters.max_price) {
        params.set("max_price", String(filters.max_price));
      } else {
        params.delete("max_price");
      }

      if (filters.shorting_id) {
        params.set("shorting_id", String(filters.shorting_id));
      } else {
        params.delete("shorting_id");
      }

      if (Object.prototype.hasOwnProperty.call(filters, "search")) {
        const searchValue = String(filters.search || "").trim();
        if (searchValue) {
          params.set("search", searchValue);
        } else {
          params.delete("search");
        }
      }

      // Check URL length to prevent issues
      const newURL = `${pathname}?${params.toString()}`;

      // Limit URL length to prevent server issues (typically 2048 characters)
      if (newURL.length > 2000) {
        // Show user notification
        if (typeof window !== "undefined") {
          alert(
            "Çok fazla filtre seçildi. URL uzunluğunu korumak için bazı filtreler kaldırıldı."
          );
        }
        // Remove some filter parameters to shorten URL
        params.delete("brands");
        params.delete("categories");
        params.delete("variantItems");
        const shortenedURL = `${pathname}?${params.toString()}`;
        router.replace(shortenedURL, { scroll: false });
        return;
      }

      router.replace(newURL, { scroll: false });
    } catch (error) {
      console.error("Error updating URL:", error);
    }
  };

  // Initialize the debounced function after updateURL is defined
  debouncedUpdateURL = debounce(updateURL, 300);

  /**
   * Parse URL parameters and set initial filter states
   */
  const parseURLParams = () => {
    try {
      // Handle both old format (multiple params) and new format (comma-separated)
      let brands = [];
      let categories = [];
      let variantItems = [];

      // Try new comma-separated format first
      const brandsParam = searchParams.get("brands");
      const categoriesParam = searchParams.get("categories");
      const variantItemsParam = searchParams.get("variantItems");

      if (brandsParam) {
        brands = brandsParam.split(",").filter((b) => b.trim() !== "");
      } else {
        // Fallback to old format
        brands = searchParams.getAll("brands");
      }

      if (categoriesParam) {
        categories = categoriesParam.split(",").filter((c) => c.trim() !== "");
      } else {
        // Fallback to old format
        categories = searchParams.getAll("categories");
      }

      if (variantItemsParam) {
        variantItems = variantItemsParam
          .split(",")
          .filter((v) => v.trim() !== "");
      } else {
        // Fallback to old format
        variantItems = searchParams.getAll("variantItems");
      }

      const subCategoriesParam = searchParams.get("sub_categories");
      const childCategory = searchParams.get("child_category");

      let subCategories = [];
      if (subCategoriesParam) {
        subCategories = subCategoriesParam.split(",").filter((s) => s.trim() !== "");
      }

      return {
        brands: brands.length > 0 ? brands : [],
        categories: categories.length > 0 ? categories : [],
        variantItems: variantItems.length > 0 ? variantItems : [],
        sub_categories: subCategories,
        child_category: childCategory || "",
        min_price: searchParams.get("min_price") || "",
        max_price: searchParams.get("max_price") || "",
        shorting_id: searchParams.get("shorting_id") || "",
        search: searchParams.get("search") || "",
      };
    } catch (error) {
      console.error("Error parsing URL parameters:", error);
      return {
        brands: [],
        categories: [],
        variantItems: [],
        sub_categories: [],
        child_category: "",
        min_price: "",
        max_price: "",
        shorting_id: "",
        search: "",
      };
    }
  };

  /**
   * Transform raw product data into standardized format for ProductCard components
   */
  const products =
    resProducts &&
    resProducts.length > 0 &&
    resProducts.map((item) => ({
      id: item.id,
      title: item.name,
      slug: item.slug,
      image: resolveProductImageUrl(item.thumb_image),
      price: item.price,
      offer_price: item.offer_price,
      campaingn_product: null,
      vendor_id: Number(item.vendor_id),
      review: parseInt(item.averageRating),
      variants: item.active_variants ? item.active_variants : [],
      sale_unit_qty: item.sale_unit_qty,
    }));

  /**
   * Handle variant filter selection/deselection
   * @param {Event} e - Click event from variant checkbox
   */
  const varientHandler = (e) => {
    if (!e || !e.target || !e.target.name) {
      console.error("Invalid event or target in varientHandler:", e);
      return;
    }

    const { name } = e.target;

    // Update variants filter state with selected/deselected items
    const filterVariant =
      variantsFilter &&
      variantsFilter.length > 0 &&
      variantsFilter.map((varient) => ({
        ...varient,
        active_variant_items:
          varient.active_variant_items &&
          varient.active_variant_items.length > 0 &&
          varient.active_variant_items.map((variant_item) => ({
            ...variant_item,
            selected:
              variant_item.name === name
                ? !variant_item.selected
                : variant_item.selected,
          })),
      }));

    setVariantsFilter(filterVariant);

    // Update selected variants array
    let newSelectedVariants;
    if (selectedVarientFilterItem.includes(name)) {
      newSelectedVariants = selectedVarientFilterItem.filter(
        (like) => like !== name
      );
      setSelectedVarientFilterItem(newSelectedVariants);
    } else {
      // Check if adding this filter would exceed the limit
      const totalFilters =
        selectedVarientFilterItem.length +
        selectedCategoryFilterItem.length +
        selectedBrandsFilterItem.length +
        1;
      if (totalFilters > 8) {
        // alert(
        //   "Too many filters selected. Please remove some filters before adding more."
        // );
        return;
      }
      newSelectedVariants = [...selectedVarientFilterItem, name];
      setSelectedVarientFilterItem(newSelectedVariants);
    }

    // Update URL with new variant selection
    const currentFilters = getCurrentFilterPayload({
      variantItems: ensureArray(newSelectedVariants),
    });

    if (typeof debouncedUpdateURL === "function") {
      debouncedUpdateURL(currentFilters);
    } else {
      updateURL(currentFilters);
    }
  };

  /**
   * Handle category filter selection/deselection
   * @param {Event} e - Click event from category checkbox
   */
  const categoryHandler = (e) => {
    if (!e || !e.target || !e.target.name) {
      console.error("Invalid event or target in categoryHandler:", e);
      return;
    }

    const { name } = e.target;

    // Update categories filter state with selected/deselected items
    const filterCat =
      categoriesFilter &&
      categoriesFilter.length > 0 &&
      categoriesFilter.map((item) => ({
        ...item,
        selected:
          parseInt(item.id) === parseInt(name) ? !item.selected : item.selected,
      }));

    setCategoriesFilter(filterCat);

    // Update selected categories array
    let newSelectedCategories;
    if (selectedCategoryFilterItem.includes(name)) {
      newSelectedCategories = selectedCategoryFilterItem.filter(
        (like) => like !== name
      );
      setSelectedCategoryFilterItem(newSelectedCategories);
    } else {
      // Check if adding this filter would exceed the limit
      const totalFilters =
        selectedVarientFilterItem.length +
        selectedCategoryFilterItem.length +
        selectedBrandsFilterItem.length +
        1;
      if (totalFilters > 8) {
        // alert(
        //   "Too many filters selected. Please remove some filters before adding more."
        // );
        return;
      }
      newSelectedCategories = [...selectedCategoryFilterItem, name];
      setSelectedCategoryFilterItem(newSelectedCategories);
    }

    // Update URL with new category selection
    const currentFilters = getCurrentFilterPayload({
      categories: ensureArray(newSelectedCategories),
      sub_category: selectedSubCategorySlug,
    });

    if (typeof debouncedUpdateURL === "function") {
      debouncedUpdateURL(currentFilters);
    } else {
      updateURL(currentFilters);
    }
  };

  /**
   * Handle sub-category filter selection (single-select by slug)
   */
  const subCategoryHandler = (e) => {
    if (!e || !e.target || !e.target.name) {
      return;
    }

    const { name } = e.target;
    let nextSlugs;
    if (selectedSubCategorySlug.includes(name)) {
      nextSlugs = selectedSubCategorySlug.filter((s) => s !== name);
    } else {
      nextSlugs = [...selectedSubCategorySlug, name];
    }
    setSelectedSubCategorySlug(nextSlugs);

    const currentFilters = getCurrentFilterPayload({
      sub_categories: nextSlugs,
    });

    if (typeof debouncedUpdateURL === "function") {
      debouncedUpdateURL(currentFilters);
    } else {
      updateURL(currentFilters);
    }
  };

  /**
   * Handle child-category (3rd level) filter selection
   */
  const childCategoryHandler = (e) => {
    if (!e || !e.target || !e.target.name) return;
    const { name } = e.target;
    const nextSlug = selectedChildCategorySlug === name ? "" : name;
    setSelectedChildCategorySlug(nextSlug);

    const currentFilters = getCurrentFilterPayload({
      sub_categories: ensureArray(selectedSubCategorySlug),
      child_category: nextSlug,
    });

    if (typeof debouncedUpdateURL === "function") {
      debouncedUpdateURL(currentFilters);
    } else {
      updateURL(currentFilters);
    }
  };

  /**
   * Handle brand filter selection/deselection
   * @param {Event} e - Click event from brand checkbox
   */
  const brandsHandler = (e) => {
    if (!e || !e.target || !e.target.name) {
      console.error("Invalid event or target in brandsHandler:", e);
      return;
    }

    const { name } = e.target;

    // Update brands filter state with selected/deselected items
    const filterBrands =
      brands &&
      brands.length > 0 &&
      brands.map((item) => ({
        ...item,
        selected:
          parseInt(item.id) === parseInt(name) ? !item.selected : item.selected,
      }));

    setBrands(filterBrands);

    // Update selected brands array
    let newSelectedBrands;
    if (selectedBrandsFilterItem.includes(name)) {
      newSelectedBrands = selectedBrandsFilterItem.filter(
        (like) => like !== name
      );
      setSelectedBrandsFilterItem(newSelectedBrands);
    } else {
      // Check if adding this filter would exceed the limit
      const totalFilters =
        selectedVarientFilterItem.length +
        selectedCategoryFilterItem.length +
        selectedBrandsFilterItem.length +
        1;
      if (totalFilters > 8) {
        // alert(
        //   "Too many filters selected. Please remove some filters before adding more."
        // );
        return;
      }
      newSelectedBrands = [...selectedBrandsFilterItem, name];
      setSelectedBrandsFilterItem(newSelectedBrands);
    }

    // Update URL with new brand selection
    const currentFilters = getCurrentFilterPayload({
      brands: ensureArray(newSelectedBrands),
    });

    if (typeof debouncedUpdateURL === "function") {
      debouncedUpdateURL(currentFilters);
    } else {
      updateURL(currentFilters);
    }
  };

  /**
   * Handle filter toggle for mobile view
   */
  const handleFilterToggle = () => setToggle(!filterToggle);

  /**
   * Get the count of active filters
   */
  const getActiveFiltersCount = () => {
    let count = 0;
    if (selectedVarientFilterItem.length > 0)
      count += selectedVarientFilterItem.length;
    if (selectedCategoryFilterItem.length > 0)
      count += selectedCategoryFilterItem.length;
    if (selectedBrandsFilterItem.length > 0)
      count += selectedBrandsFilterItem.length;
    count += ensureArray(selectedSubCategorySlug).length;
    if (selectedChildCategorySlug) count += 1;
    if (appliedMinPrice) count += 1;
    if (appliedMaxPrice) count += 1;
    if (sortId) count += 1;

    return count;
  };

  /**
   * Handle card view style changes (column vs row layout)
   * @param {string} style - View style ('col' or 'row')
   */
  const handleCardViewStyle = (style) => setCardViewStyle(style);

  /**
   * Clear all applied filters and reset to default state
   */
  const clearAllFilters = () => {
    // Reset all selected filter items
    setSelectedVarientFilterItem([]);
    setSelectedCategoryFilterItem([]);
    setSelectedBrandsFilterItem([]);
    setSelectedSubCategorySlug([]);
    setSelectedChildCategorySlug("");
    setMinPriceInput("");
    setMaxPriceInput("");
    setAppliedMinPrice("");
    setAppliedMaxPrice("");
    setSortId("");

    // Reset filter states
    if (variantsFilter) {
      setVariantsFilter(
        variantsFilter.map((varient) => ({
          ...varient,
          active_variant_items:
            varient.active_variant_items &&
            varient.active_variant_items.map((variant_item) => ({
              ...variant_item,
              selected: false,
            })),
        }))
      );
    }

    if (categoriesFilter) {
      setCategoriesFilter(
        categoriesFilter.map((item) => ({
          ...item,
          selected: false,
        }))
      );
    }

    if (brands) {
      setBrands(
        brands.map((item) => ({
          ...item,
          selected: false,
        }))
      );
    }

    // Clear URL parameters while preserving original page parameters
    const params = new URLSearchParams();

    // Preserve original page parameters
    const originalParams = [
      "category",
      "brand",
      "highlight",
      "search",
      "type",
      "slug",
      "seller",
    ];
    originalParams.forEach((param) => {
      const value = searchParams.get(param);
      if (value) {
        params.set(param, value);
      }
    });

    const newURL = `${pathname}?${params.toString()}`;
    router.replace(newURL, { scroll: false });
  };

  // ========================================
  // EFFECTS
  // ========================================

  /**
   * Initialize component data when response changes
   */
  useEffect(() => {
    if (!response) return;

    const responseChanged = response !== prevResponseRef.current;
    prevResponseRef.current = response;

    // Only reset products when response actually changed (new server data).
    // When only searchParams changes (client-side filter URL update), keep
    // the current filter results intact to avoid overwriting filtered products.
    if (responseChanged) {
      setProducts(response.products?.data || []);
      setNxtPage(response.products?.next_page_url);
    }

    // Parse URL parameters for initial filter states
    const urlParams = parseURLParams();

    // Initialize categories filter with selection state from URL
    setCategoriesFilter(
      response.categories?.length > 0
        ? response.categories.map((item) => ({
          ...item,
          selected: urlParams.categories.includes(item.id.toString()),
        }))
        : []
    );

    const allSubCategories = (response.categories || []).flatMap((category) => {
      const nested =
        category?.active_sub_categories ||
        category?.activeSubCategories ||
        category?.sub_categories ||
        category?.subCategories ||
        [];
      return nested.map((sub) => ({
        id: sub.id,
        name: sub.name,
        slug: sub.slug,
        selected: (urlParams.sub_categories || []).includes(sub.slug),
      }));
    });
    setSubCategoriesFilter(allSubCategories);

    // Initialize variants filter with selection state from URL
    setVariantsFilter(
      response.activeVariants?.length > 0
        ? response.activeVariants.map((varient) => ({
          ...varient,
          active_variant_items:
            varient.active_variant_items?.length > 0
              ? varient.active_variant_items.map((variant_item) => ({
                ...variant_item,
                selected: urlParams.variantItems.includes(variant_item.name),
              }))
              : [],
        }))
        : []
    );

    // Initialize brands filter with selection state from URL
    setBrands(
      response.brands?.length > 0
        ? response.brands.map((item) => ({
          ...item,
          selected: urlParams.brands.includes(item.id.toString()),
        }))
        : []
    );

    // Set selected filter items from URL
    setSelectedCategoryFilterItem(urlParams.categories);
    setSelectedVarientFilterItem(urlParams.variantItems);
    setSelectedBrandsFilterItem(urlParams.brands);
    setSelectedSubCategorySlug(urlParams.sub_categories || []);
    setSelectedChildCategorySlug(urlParams.child_category || "");
    setAppliedMinPrice(urlParams.min_price || "");
    setAppliedMaxPrice(urlParams.max_price || "");
    setMinPriceInput(urlParams.min_price || "");
    setMaxPriceInput(urlParams.max_price || "");
    setSortId(urlParams.shorting_id || "");
    setListingSearch(urlParams.search || "");
  }, [response, searchParams]);

  const buildClientSearchQuery = () => {
    const params = [];
    const appendScalar = (key, value) => {
      if (value !== undefined && value !== null && String(value).trim() !== "") {
        params.push(`${key}=${encodeURIComponent(String(value))}`);
      }
    };

    [
      "brand",
    ].forEach((key) => appendScalar(key, searchParams.get(key)));
    const searchTerm = String(listingSearch || "").trim();
    const searching = searchTerm.length >= 2;
    if (!searching) {
      ["category", "sub_category", "child_category", "highlight"].forEach(
        (key) => appendScalar(key, searchParams.get(key))
      );
    }
    appendScalar("search", searching ? searchTerm : "");

    if (sellerInfo?.seller?.slug) {
      appendScalar("shop_name", sellerInfo.seller.slug);
    }

    appendScalar("min_price", appliedMinPrice);
    appendScalar("max_price", appliedMaxPrice);
    appendScalar("shorting_id", sortId);

    selectedBrandsFilterItem.forEach((value) => {
      params.push(`brands[]=${encodeURIComponent(value)}`);
    });
    selectedCategoryFilterItem.forEach((value) => {
      params.push(`categories[]=${encodeURIComponent(value)}`);
    });
    selectedVarientFilterItem.forEach((value) => {
      params.push(`variantItems[]=${encodeURIComponent(value)}`);
    });
    ensureArray(selectedSubCategorySlug).forEach((value) => {
      params.push(`sub_categories[]=${encodeURIComponent(value)}`);
    });
    appendScalar("child_category", selectedChildCategorySlug);

    return params.join("&");
  };

  const applyPriceFilter = () => {
    setAppliedMinPrice(minPriceInput);
    setAppliedMaxPrice(maxPriceInput);
    updateURL(
      getCurrentFilterPayload({
        min_price: minPriceInput,
        max_price: maxPriceInput,
      })
    );
  };

  const handleListingSearch = (term) => {
    const next = String(term || "").trim();
    const query = next.length >= 2 ? next : "";
    setListingSearch(query);
    updateURL(getCurrentFilterPayload({ search: query }));
  };

  const handleSortChange = (event) => {
    const nextSort = event.target.value;
    setSortId(nextSort);
    updateURL(getCurrentFilterPayload({ shorting_id: nextSort }));
  };

  /**
   * Filter Products functionality
   * @Initializes useLazyGetAllProductsApiQuery @const getAllProductsApi
   * Api call using useEffect
   * @Initializes useLazyNextPageProductsApiQuery @const nextPageProductsApi
   * @func nextPageHandler
   */

  const [getAllProductsApi, { isLoading: isLoadingGetAllProductsApi }] =
    useLazyGetAllProductsApiQuery();

  /**
   * Handle filter changes and trigger API calls
   */
  useEffect(() => {
    if (!response?.products?.data) return;

    const hasActiveFilters =
      selectedVarientFilterItem.length > 0 ||
      selectedCategoryFilterItem.length > 0 ||
      selectedBrandsFilterItem.length > 0 ||
      ensureArray(selectedSubCategorySlug).length > 0 ||
      !!selectedChildCategorySlug ||
      !!appliedMinPrice ||
      !!appliedMaxPrice ||
      !!sortId ||
      String(listingSearch || "").trim().length >= 2;

    if (hasActiveFilters) {
      const query = buildClientSearchQuery();

      const fetchProducts = async (q) => {
        setIsFiltering(true);
        try {
          const result = await getAllProductsApi(q).unwrap();
          const data = result?.products?.data ?? [];
          setProducts(data);
          setProductTotal(result?.products?.total ?? data.length);
          setNxtPage(result?.products?.next_page_url ?? null);
        } catch {
          setProducts([]);
          setNxtPage(null);
        } finally {
          setIsFiltering(false);
        }
      };
      fetchProducts(query);
    } else {
      // No active filters — show original products
      setProducts(response?.products?.data || []);
      setProductTotal(response?.products?.total || 0);
      setNxtPage(response?.products?.next_page_url || null);
    }
  }, [
    selectedVarientFilterItem,
    selectedCategoryFilterItem,
    selectedBrandsFilterItem,
    selectedSubCategorySlug,
    selectedChildCategorySlug,
    appliedMinPrice,
    appliedMaxPrice,
    sortId,
    listingSearch,
    response,
  ]);

  const [nextPageProductsApi, { isLoading: isLoadingNextPageProductsApi }] =
    useLazyNextPageProductsApiQuery();

  /**
   * Load next page of products
   */
  const nextPageHandler = async () => {
    if (!nxtPage || nxtPage === "null") {
      return false;
    }
    const fetchProducts = async (url) => {
      const products = await nextPageProductsApi(url).unwrap();
      const productsData = products?.products?.data;
      if (productsData && productsData?.length > 0) {
        setProducts((prev) => [...prev, ...productsData]);
        setNxtPage(products?.products?.next_page_url);
      }
    };
    fetchProducts(nxtPage);
  };

  // RENDER HELPERS

  /**
   * Render seller information section
   */
  const renderSellerInfo = () => {
    if (!sellerInfo) return null;

    return (
      <div
        data-aos="fade-right"
        className="saller-info w-full mb-[40px] sm:h-[328px] sm:flex justify-between items-center px-11 overflow-hidden relative py-10 sm:py-0"
        style={{
          background: `url(/assets/images/saller-cover.png) no-repeat`,
          backgroundSize: "cover",
        }}
      >
        {/* Seller Contact Information */}
        <div className="saller-text-details w-72">
          <ul>
            <li className="text-black flex space-x-5 rtl:space-x-reverse items-center leading-9 text-base font-normal">
              <span>
                <ShopEmailIco />
              </span>
              <span>{sellerInfo.seller.email}</span>
            </li>
            <li className="text-black flex space-x-5 rtl:space-x-reverse items-center leading-9 text-base font-normal">
              <span>
                <ShopPhoneIco />
              </span>
              <span>{sellerInfo.seller.phone}</span>
            </li>
            <li className="text-black flex space-x-5 rtl:space-x-reverse items-center leading-9 text-base font-normal">
              <span>
                <ShopLocationIco />
              </span>
              <span>{sellerInfo.seller.address}</span>
            </li>
          </ul>
        </div>

        {/* Seller Name and Rating - Desktop */}
        <div className="saller-name lg:block hidden">
          <h2 className="text-[60px] font-bold notranslate">
            {sellerInfo.seller.shop_name}
          </h2>
          <div className="flex justify-center">
            {Array.from(
              Array(parseInt(sellerInfo.seller.averageRating)),
              (_, index) => (
                <span
                  key={`seller-star-filled-${index}`}
                >
                  <Star />
                </span>
              )
            )}
            {parseInt(sellerInfo.seller.averageRating) < 5 && (
              <>
                {Array.from(
                  Array(5 - parseInt(sellerInfo.seller.averageRating)),
                  (_, index) => (
                    <span
                      key={`seller-star-empty-${index}`}
                      className="text-gray-500"
                    >
                      <svg
                        width="18"
                        height="17"
                        viewBox="0 0 18 17"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        className="fill-current"
                      >
                        <path d="M9 0L11.0206 6.21885H17.5595L12.2694 10.0623L14.2901 16.2812L9 12.4377L3.70993 16.2812L5.73056 10.0623L0.440492 6.21885H6.97937L9 0Z" />
                      </svg>
                    </span>
                  )
                )}
              </>
            )}
          </div>
        </div>

        {/* Seller Logo and Name - Mobile */}
        <div className="saller-logo mt-5 sm:mt-5">
          <div className="flex sm:justify-center justify-start">
            <div className="w-[170px] h-[170px] p-[30px] flex justify-center items-center rounded-full bg-white relative mb-1 overflow-hidden">
              <Image
                width={170}
                height={170}
                className="w-full h-full object-contain"
                src={`${appConfig.BASE_URL + sellerInfo.seller.logo}`}
                alt={sellerInfo.seller.shop_name || "Satici logosu"}
              />
            </div>
          </div>
          <div className="flex sm:justify-center justify-start">
            <span className="text-[30px] font-medium text-center notranslate">
              {sellerInfo.seller.shop_name}
            </span>
          </div>
        </div>
      </div>
    );
  };

  /**
   * Render sidebar banner
   */
  /**
   * Render product sorting and view controls
   */
  const renderProductControls = () => {
    if (!products?.length) return null;

    return (
      <div className="products-sorting w-full bg-white md:h-[70px] flex md:flex-row flex-col md:space-y-0 space-y-5 md:justify-between md:items-center p-[30px] mb-[40px]">
        {/* Results count + sort */}
        <div className="flex flex-col sm:flex-row sm:items-center gap-3">
          <p className="font-400 text-[13px]">
            <span className="text-qgray">{ServeLangItem()?.Showing}</span> 1–
            {products.length} / {productTotal} {ServeLangItem()?.results}
          </p>
          <div className="flex items-center gap-2">
            <label htmlFor="product-sort" className="text-[13px] text-qblack whitespace-nowrap">
              Sırala:
            </label>
            <select
              id="product-sort"
              value={sortId}
              onChange={handleSortChange}
              className="h-10 min-w-[170px] px-3 border border-qgray-border rounded text-[13px] bg-white"
            >
              <option value="">En yeni</option>
              <option value="2">Fiyat: Düşükten yükseğe</option>
              <option value="3">Fiyat: Yüksekten düşüğe</option>
            </select>
          </div>
        </div>

        {/* View style controls */}
        <div className="flex space-x-3 items-center">
          <span className="font-bold text-qblack text-[13px]">
            {ServeLangItem()?.View_by} :
          </span>
          <button
            onClick={() => handleCardViewStyle("col")}
            type="button"
            className={`hover:text-qgreen w-6 h-6 ${
              cardViewStyle === "col" ? "text-qgreen" : "text-qgray"
            }`}
          >
            <ViewColIco />
          </button>
          <button
            onClick={() => handleCardViewStyle("row")}
            type="button"
            className={`hover:text-qgreen w-6 h-6 ${
              cardViewStyle === "row" ? "text-qgreen" : "text-qgray"
            }`}
          >
            <ViewRowIco />
          </button>
        </div>

        {/* Filter toggle — desktop + mobile */}
        <button
          onClick={() => {
            if (window.innerWidth < 1024) {
              handleFilterToggle();
            } else {
              setDesktopFilterOpen((v) => !v);
            }
          }}
          type="button"
          className="w-10 h-10 rounded flex justify-center items-center border border-qyellow text-qyellow relative"
          aria-label={desktopFilterOpen ? "Filtreleri kapat" : "Filtreleri aç"}
        >
          <FilterIco />
          {getActiveFiltersCount() > 0 && (
            <span className="absolute -top-2 -right-2 bg-qred text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
              {getActiveFiltersCount()}
            </span>
          )}
        </button>
      </div>
    );
  };

  /**
   * Render products grid
   */
  const renderProductsGrid = (productsData, startIndex = 0, endIndex = 6) => {
    if (!productsData) return null;

    const gridClass =
      cardViewStyle === "col"
        ? "grid grid-cols-3 xl:grid-cols-3 xl:gap-[30px] gap-2.5 mb-[40px]"
        : "grid lg:grid-cols-2 grid-cols-1 xl:gap-[30px] gap-5 mb-[40px]";

    const productStyle = cardViewStyle === "row" ? "row-v2" : undefined;

    return (
      <div className={gridClass}>
        {(productsData || []).slice(startIndex, endIndex).map((item) => (
          <div key={`${pathname}-${searchParams?.toString?.() || ""}-${item.id}`}>
            <ProductCard datas={item} styleType={productStyle} />
          </div>
        ))}
      </div>
    );
  };

  /**
   * Render load more button
   */
  const renderLoadMoreButton = () => {
    if (!nxtPage || nxtPage === "null") return null;

    return (
      <div className="flex justify-center">
        <button
          disabled={isLoadingNextPageProductsApi}
          onClick={nextPageHandler}
          type="button"
          className="w-[180px] h-[54px] bg-qyellow rounded mt-10 disabled:cursor-not-allowed"
        >
          <div className="flex justify-center w-full h-full items-center group rounded relative transition-all duration-300 ease-in-out overflow-hidden cursor-pointer">
            <div className="flex items-center transition-all duration-300 ease-in-out relative z-10 text-white hover:text-white">
              <span className="text-sm font-600 tracking-wide leading-7 mr-2">
                {ServeLangItem()?.Show_more}...
              </span>
              {isLoadingNextPageProductsApi && (
                <span className="w-5" style={{ transform: "scale(0.3)" }}>
                  <LoaderStyleOne />
                </span>
              )}
            </div>
            <div
              style={{ transition: `transform 0.25s ease-in-out` }}
              className="w-full h-full bg-black absolute top-0 left-0 right-0 bottom-0 transform scale-x-0 group-hover:scale-x-100 origin-[center_left] group-hover:origin-[center_right]"
            ></div>
          </div>
        </button>
      </div>
    );
  };

  // Main Component Render
  return (
    <div className="products-page-wrapper w-full">
      <div className="container-x mx-auto">
        {/* Main H1 for SEO - only if not seller page (seller info has its own H1) */}
        {!sellerInfo && (
          <h1 className="text-2xl font-semibold text-qblack mb-6">
            Tüm Ürünler
          </h1>
        )}
        
        {/* Seller Information Section */}
        {renderSellerInfo()}


        <div className="w-full lg:flex lg:space-x-[30px] rtl:space-x-reverse">
          {/* Left Sidebar - Filters */}
          {desktopFilterOpen && (
            <div className="lg:w-[270px] shrink-0">
              <ProductsFilter
                filterToggle={filterToggle}
                filterToggleHandler={handleFilterToggle}
                categories={categoriesFilter}
                brands={brands}
                varientHandler={varientHandler}
                categoryHandler={categoryHandler}
                subCategoryHandler={subCategoryHandler}
                childCategoryHandler={childCategoryHandler}
                brandsHandler={brandsHandler}
                className="mb-[30px]"
                variantsFilter={variantsFilter}
                clearAllFilters={clearAllFilters}
                selectedSubCategorySlug={selectedSubCategorySlug}
                selectedChildCategorySlug={selectedChildCategorySlug}
                minPriceInput={minPriceInput}
                maxPriceInput={maxPriceInput}
                onMinPriceInputChange={setMinPriceInput}
                onMaxPriceInputChange={setMaxPriceInput}
                onApplyPriceFilter={applyPriceFilter}
                priceRangeMax={priceRangeMax}
              />
            </div>
          )}


          {/* Main Content Area */}
          <div className="flex-1">
            <ListingSearchBar
              value={listingSearch}
              onSubmit={handleListingSearch}
            />
            {isFiltering ? (
              <div className="w-full flex justify-center items-center min-h-[300px]">
                <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-qyellow"></div>
              </div>
            ) : products?.length > 0 ? (
              <div className="w-full">
                {/* Product Controls */}
                {renderProductControls()}

                {/* First Products Grid */}
                {renderProductsGrid(products, 0, products?.length)}

                {/* Load More Button */}
                {renderLoadMoreButton()}

              </div>
            ) : (
              <div className="mt-5 flex justify-center">
                <h2 className="text-2xl font-medium text-tblack">
                  Ürün bulunamadı
                </h2>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

export default function AllProductPage({ response, sellerInfo }) {
  return (
    <Suspense
      fallback={
        <div className="w-full flex justify-center items-center min-h-[400px]">
          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900"></div>
        </div>
      }
    >
      <AllProductPageContent response={response} sellerInfo={sellerInfo} />
    </Suspense>
  );
}
