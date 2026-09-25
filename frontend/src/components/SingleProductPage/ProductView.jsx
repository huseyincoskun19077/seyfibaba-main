"use client";
import Image from "next/image";
import { useContext, useEffect, useState, useMemo, useCallback, useRef } from "react";
import { useRouter, usePathname, useSearchParams } from "next/navigation";
import { useDispatch, useSelector } from "react-redux";
import { FacebookShareButton, TwitterShareButton } from "react-share";
import { toast } from "react-toastify";
import auth from "../../utils/auth";
import settings from "../../utils/settings";
import { addItem } from "../../redux/features/cart/cartSlice";
import useWishlist from "../../hooks/useWishlist";
import Star from "../Helpers/icons/Star";
import ThinLove from "../Helpers/icons/ThinLove";
import ServeLangItem from "../Helpers/ServeLangItem";
import LoginContext from "../Contexts/LoginContext";
import messageContext from "../Contexts/MessageContext";
import CurrencyConvert from "../Shared/CurrencyConvert";
import PriceDisplay from "../Shared/PriceDisplay";
import ReportIco from "../Helpers/icons/ReportIco";
import FbIco from "../Helpers/icons/FbIco";
import TwiterIco from "../Helpers/icons/TwiterIco";
import MessageIco from "../Helpers/icons/MessageIco";
import { useFlyingCart } from "../Contexts/FlyingCartContext";
import appConfig from "@/appConfig";
import useBfCacheRemountKey from "@/hooks/useBfCacheRemountKey";
import { displayTurkishLabel } from "@/utils/turkishDisplay";
import { getSaleUnitQty, isEffectiveOfferPrice } from "@/utils/productPricing";
import { resolveProductUnitPrice, parseAmount } from "@/utils/variantPricing";
import ProductSaleUnitInfo from "../Shared/ProductSaleUnitInfo";
import ProductFurnitureInquiry from "./ProductFurnitureInquiry";
import { getProductImageProps } from "@/utils/productImage";

const PRODUCT_IMAGE_FALLBACK = "/assets/images/server-error.png";
const ABSOLUTE_URL_REGEX = /^https?:\/\//i;

const resolveImageSrc = (value) => {
  const raw = String(value || "").trim();
  if (!raw) return PRODUCT_IMAGE_FALLBACK;
  if (
    ABSOLUTE_URL_REGEX.test(raw) ||
    raw.startsWith("data:") ||
    raw.startsWith("blob:")
  ) {
    return raw;
  }

  return `${appConfig.BASE_URL}${raw.replace(/^\/+/, "")}`;
};

const getInitialVariantItems = (variants = [], options = {}) => {
  const { includeColor = false } = options;
  return variants
    .map((variant) => {
      const items = Array.isArray(variant?.active_variant_items)
        ? variant.active_variant_items
        : [];
      if (!items.length) return null;
      const isColor = /renk|color/i.test(String(variant?.name || ""));
      // Renk seçilmeden standart ürün fiyatı görünsün
      if (isColor && !includeColor) return null;
      return items[0];
    })
    .filter(Boolean);
};

const slugifyVariantParam = (value) =>
  String(value || "")
    .toLocaleLowerCase("tr-TR")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/ğ/g, "g")
    .replace(/ü/g, "u")
    .replace(/ş/g, "s")
    .replace(/ı/g, "i")
    .replace(/ö/g, "o")
    .replace(/ç/g, "c")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");

const variantParamKey = (variantName) => {
  const key = slugifyVariantParam(variantName);
  return key || "secenek";
};

const resolveVariantsFromParams = (variants = [], params) => {
  if (!variants.length) return getInitialVariantItems(variants, { includeColor: false });

  return variants
    .map((variant) => {
      const items = Array.isArray(variant?.active_variant_items)
        ? variant.active_variant_items
        : [];
      if (!items.length) return null;
      const isColor = /renk|color/i.test(String(variant?.name || ""));
      const key = variantParamKey(variant.name);
      const wanted = params ? String(params.get(key) || "").trim() : "";
      if (isColor && !wanted) return null;
      if (!wanted) return isColor ? null : items[0];
      return (
        items.find((item) => slugifyVariantParam(item.name) === wanted) ||
        (isColor ? null : items[0])
      );
    })
    .filter(Boolean);
};

const collectColorGalleryItems = (variants = [], thumb = "") => {
  const colors = [];
  (variants || []).forEach((variant) => {
    if (!/renk|color/i.test(String(variant?.name || ""))) return;
    (variant.active_variant_items || []).forEach((item) => {
      if (!item?.image) return;
      colors.push({
        image: item.image,
        label: item.name,
        variantItem: item,
        variantId: variant.id,
      });
    });
  });
  return colors;
};

const StarRating = ({ rating }) => {
  const numericRating = isNaN(parseInt(rating)) ? 0 : Math.min(5, Math.max(0, parseInt(rating)));
  return (
    <div className="flex">
      {Array.from(Array(numericRating), (_, i) => (
        <span key={`star-filled-${i}`}>
          <Star />
        </span>
      ))}
      {numericRating < 5 && (
        <>
          {Array.from(Array(5 - numericRating), (_, i) => (
            <span
              key={`star-empty-${i}`}
              className="text-gray-500"
            >
              <Star defaultValue={false} />
            </span>
          ))}
        </>
      )}
    </div>
  );
};

const ProductImage = ({ src, alt, className = "", onClick }) => {
  const bfCacheKey = useBfCacheRemountKey();
  const { src: resolved, unoptimized } = getProductImageProps(src);
  const isDimmed = String(className || "").includes("opacity-50");
  return (
    <div
      onClick={onClick}
      className={`relative h-[88px] w-[88px] cursor-pointer overflow-hidden rounded-xl border-2 bg-white p-2 transition sm:h-[100px] sm:w-[100px] ${
        isDimmed
          ? "border-[#04334a]/10 opacity-60 hover:opacity-100"
          : "border-qyellow shadow-sm"
      } ${onClick ? "" : "cursor-default"}`}
    >
      <Image
        key={`pi-${resolved}-${bfCacheKey}`}
        fill
        style={{ objectFit: "contain" }}
        src={resolved}
        unoptimized={unoptimized}
        alt={alt}
        sizes="100px"
        className="object-contain"
      />
    </div>
  );
};

const QuantitySelector = ({ quantity, onIncrement, onDecrement }) => (
  <div className="flex h-full w-[120px] items-center rounded-xl border border-[#04334a]/12 bg-[#F4F6F7] px-3">
    <div className="flex w-full items-center justify-between">
      <button
        onClick={onDecrement}
        type="button"
        className="flex h-8 w-8 items-center justify-center rounded-lg text-base text-[#04334a]/55 transition hover:bg-white hover:text-[#04334a]"
      >
        -
      </button>
      <span className="text-sm font-700 text-[#04334a]">{quantity}</span>
      <button
        onClick={onIncrement}
        type="button"
        className="flex h-8 w-8 items-center justify-center rounded-lg text-base text-[#04334a]/55 transition hover:bg-white hover:text-[#04334a]"
      >
        +
      </button>
    </div>
  </div>
);

const VariantSelector = ({
  variants,
  onSelectVariant,
  onSelectStandard,
  basePrice = 0,
  selectedVariantItems = [],
  standardSelected = true,
  thumbImage = "",
}) => {
  const [colorQuery, setColorQuery] = useState("");

  if (!Array.isArray(variants) || variants.length === 0) {
    return null;
  }

  const selectItem = (variant, item) => {
    onSelectVariant(item);
  };

  const colorDisplayPrice = (itemPrice) => {
    const n = Number(itemPrice || 0);
    return n > 0 ? n : Number(basePrice || 0);
  };

  return (
    <div className="mb-2 space-y-5">
      {variants.map((variant) => {
        const items = Array.isArray(variant?.active_variant_items)
          ? variant.active_variant_items
          : [];
        if (!items.length) return null;
        const name = String(variant?.name || "");
        const isColor = /renk|color/i.test(name);
        const selectedForGroup = selectedVariantItems.find(
          (s) => Number(s?.product_variant_id) === Number(variant.id)
        );
        const selectedId = selectedForGroup?.id;

        if (isColor) {
          const q = colorQuery.trim().toLocaleLowerCase("tr-TR");
          const filtered = q
            ? items.filter((item) =>
                String(item.name || "")
                  .toLocaleLowerCase("tr-TR")
                  .includes(q)
              )
            : items;
          const many = items.length > 12;

          return (
            <div key={variant.id || name}>
              <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                <p className="mb-0 text-sm font-700 text-[#04334a]">
                  {name || "Renk"}
                  <span className="ml-2 text-xs font-normal text-[#04334a]/45">
                    ({items.length})
                  </span>
                </p>
                {many && (
                  <input
                    type="search"
                    value={colorQuery}
                    onChange={(e) => setColorQuery(e.target.value)}
                    placeholder="Renk ara…"
                    className="h-9 w-full max-w-[200px] rounded-lg border-0 bg-[#F4F6F7] px-3 text-sm text-[#04334a] outline-none focus:ring-2 focus:ring-[#FCBF49]/50"
                  />
                )}
              </div>
              <div
                className={`flex flex-wrap gap-2 ${
                  many ? "max-h-[280px] overflow-y-auto pr-1" : ""
                }`}
              >
                <button
                  type="button"
                  onClick={() => onSelectStandard?.(variant)}
                  className={`inline-flex items-center gap-2 rounded-xl border px-2.5 py-1.5 text-left transition ${
                    standardSelected
                      ? "border-[#04334a] bg-[#FFF8E8] ring-2 ring-qyellow"
                      : "border-[#04334a]/12 hover:border-[#04334a]/40"
                  }`}
                >
                  {thumbImage ? (
                    <span className="relative h-9 w-9 shrink-0 overflow-hidden rounded-lg bg-gray-50">
                      <Image
                        src={
                          thumbImage.startsWith("http")
                            ? thumbImage
                            : `${appConfig.BASE_URL}${thumbImage}`
                        }
                        alt="Standart"
                        fill
                        className="object-cover"
                        unoptimized
                      />
                    </span>
                  ) : null}
                  <span>
                    <span className="block text-xs font-700 text-qblack">
                      Standart
                    </span>
                    <span className="block text-[11px] text-qgray">
                      <CurrencyConvert price={basePrice} />
                    </span>
                  </span>
                </button>
                {filtered.map((item) => {
                  const total = colorDisplayPrice(item.price);
                  const selected = Number(selectedId) === Number(item.id);
                  const imgSrc = item.image
                    ? item.image.startsWith("http")
                      ? item.image
                      : `${appConfig.BASE_URL}${item.image}`
                    : "";
                  return (
                    <button
                      key={item.id}
                      type="button"
                      title={item.name}
                      onClick={() => selectItem(variant, item)}
                      className={`inline-flex max-w-[160px] items-center gap-2 rounded-xl border px-2.5 py-1.5 text-left transition ${
                        selected
                          ? "border-[#04334a] bg-[#FFF8E8] ring-2 ring-qyellow"
                          : "border-[#04334a]/12 hover:border-[#04334a]/40"
                      }`}
                    >
                      {imgSrc ? (
                        <span className="relative h-9 w-9 shrink-0 overflow-hidden rounded-lg bg-gray-50">
                          <Image
                            src={imgSrc}
                            alt={item.name}
                            fill
                            className="object-cover"
                            unoptimized
                          />
                        </span>
                      ) : (
                        <span
                          className="h-9 w-9 shrink-0 rounded-lg border border-dashed border-qgray-border bg-gray-50"
                          aria-hidden
                        />
                      )}
                      <span className="min-w-0">
                        <span className="block truncate text-xs font-700 text-qblack">
                          {item.name}
                        </span>
                        <span className="block text-[11px] text-qgray">
                          <CurrencyConvert price={total} />
                        </span>
                      </span>
                    </button>
                  );
                })}
                {filtered.length === 0 && (
                  <p className="text-xs text-qgray py-2">Eşleşen renk yok.</p>
                )}
              </div>
            </div>
          );
        }

        return (
          <div key={variant.id || name}>
            <p className="text-sm font-700 text-qblack mb-3">
              {name || "Seçenek"}
            </p>
            <div className="flex flex-wrap gap-2">
              {items.map((item) => {
                const extra = Number(item.price || 0);
                const selected = Number(selectedId) === Number(item.id);
                return (
                  <button
                    key={item.id}
                    type="button"
                    onClick={() => selectItem(variant, item)}
                    className={`rounded-xl border px-3 py-2 text-sm transition ${
                      selected
                        ? "border-[#04334a] bg-[#FFF8E8] font-700 ring-2 ring-qyellow"
                        : "border-[#04334a]/12 hover:border-[#04334a]/40"
                    }`}
                  >
                    <span className="text-qblack">{item.name}</span>
                    {extra > 0 && (
                      <span className="ml-1.5 text-xs font-600 text-qred">
                        +
                        <CurrencyConvert price={extra} />
                      </span>
                    )}
                  </button>
                );
              })}
            </div>
          </div>
        );
      })}
    </div>
  );
};

const SocialShareButtons = ({ product }) => {
  const safeProduct = product || {};
  const shareUrl =
    typeof window !== "undefined" && window.location?.href
      ? window.location.href
      : "";

  return (
    <div className="flex space-x-5 items-center">
      <FacebookShareButton url={shareUrl} quotes={safeProduct.name || ""}>
        <span className="cursor-pointer">
          <FbIco />
        </span>
      </FacebookShareButton>
      <TwitterShareButton url={shareUrl} title={safeProduct.name || ""}>
        <span className="cursor-pointer">
          <TwiterIco />
        </span>
      </TwitterShareButton>
    </div>
  );
};

export default function ProductView({
  className,
  reportHandler,
  images = [],
  product,
  details,
  seller,
}) {
  const safeProduct = product || {};
  const safeDetails = details || {};
  const safeVariants = safeProduct?.active_variants || [];
  const safeImages = Array.isArray(images) ? images : [];

  // Redux and Context
  const { cart } = useSelector((state) => state.cart);
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const dispatch = useDispatch();
  const messageHandler = useContext(messageContext);
  const loginPopupBoard = useContext(LoginContext);
  // Custom hooks
  const {
    wishlisted,
    arWishlist,
    addToWishlist,
    removeToWishlist,
    addToWishlistLoading,
    removeFromWishlistLoading,
  } = useWishlist(product);
  const { triggerFlyingCart } = useFlyingCart();
  const bfCacheKey = useBfCacheRemountKey();
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const skipUrlSyncRef = useRef(false);

  // State Management
  const [more, setMore] = useState(false);
  const [quantity, setQuantity] = useState(1);
  const [src, setSrc] = useState(safeProduct?.thumb_image || "");
  const [price, setPrice] = useState(null);
  const [offerPrice, setOffer] = useState(null);
  const [pricePercent, setPricePercent] = useState("");
  const [variantFlash, setVariantFlash] = useState(false);

  const [varients, setVarients] = useState(safeVariants);
  const [selectedVariantItems, setSelectedVariantItems] = useState(() =>
    resolveVariantsFromParams(safeVariants, searchParams)
  );

  // State Management
  const [productsImg, setProductsImg] = useState(safeImages);
  const [isImagePreviewOpen, setIsImagePreviewOpen] = useState(false);
  const mainImage = src || safeProduct?.thumb_image || safeImages?.[0]?.image || "";
  const mainImageProps = getProductImageProps(mainImage);

  const tags = useMemo(() => {
    if (!safeProduct?.tags) return [];
    try {
      const parsedTags =
        typeof safeProduct.tags === "string"
        ? JSON.parse(safeProduct.tags)
        : safeProduct.tags;
      return Array.isArray(parsedTags) ? parsedTags : [];
    } catch (e) {
      return [];
    }
  }, [safeProduct?.tags]);

  const { map_status, commission_type } = settings();
  const reviewCount = parseInt(
    safeDetails?.totalProductReviewQty ||
      safeDetails?.productReviews?.length ||
      0,
    10
  );
  const averageRating = parseFloat(safeProduct?.averageRating || 0);
  const sellerProductCount = parseInt(safeDetails?.sellerTotalProducts || 0, 10);
  const sellerReviewCount = parseInt(safeDetails?.sellerTotalReview || 0, 10);

  // Update state when props change - improved synchronization
  useEffect(() => {
    const nextVariants = safeVariants;
    const fromUrl = resolveVariantsFromParams(nextVariants, searchParams);

    setVarients(nextVariants);
    skipUrlSyncRef.current = true;
    setSelectedVariantItems(fromUrl);
    setSrc(safeProduct?.thumb_image || "");
    setQuantity(1);

    const colorItem = fromUrl.find((item) => {
      const parent = nextVariants.find(
        (v) => Number(v.id) === Number(item.product_variant_id)
      );
      return /renk|color/i.test(String(parent?.name || ""));
    });
    if (colorItem?.image) {
      setSrc(colorItem.image);
    }
  }, [safeProduct?.id, safeProduct?.thumb_image, safeVariants, searchParams]);

  useEffect(() => {
    if (!varients?.length) return;
    if (skipUrlSyncRef.current) {
      skipUrlSyncRef.current = false;
      return;
    }

    const params = new URLSearchParams(searchParams?.toString() || "");
    const keys = varients.map((v) => variantParamKey(v.name));
    keys.forEach((key) => params.delete(key));

    selectedVariantItems.forEach((item) => {
      const parent = varients.find(
        (v) => Number(v.id) === Number(item.product_variant_id)
      );
      if (!parent) return;
      params.set(variantParamKey(parent.name), slugifyVariantParam(item.name));
    });

    const next = params.toString();
    const current = searchParams?.toString() || "";
    if (next === current) return;
    router.replace(next ? `${pathname}?${next}` : pathname, { scroll: false });
  }, [selectedVariantItems, varients, pathname, router, searchParams]);

  useEffect(() => {
    if (!selectedVariantItems?.length) return;
    setVariantFlash(true);
    const t = setTimeout(() => setVariantFlash(false), 450);
    return () => clearTimeout(t);
  }, [selectedVariantItems]);

  useEffect(() => {
    setProductsImg(safeImages);
  }, [safeImages]);

  useEffect(() => {
    // Client-side route gecislerinde src bossa ilk uygun gorseli sec
    if (src) return;
    if (safeProduct?.thumb_image) {
      setSrc(safeProduct.thumb_image);
      return;
    }
    if (safeImages?.[0]?.image) {
      setSrc(safeImages[0].image);
    }
  }, [src, safeProduct?.thumb_image, safeImages]);

  useEffect(() => {
    const pricing = resolveProductUnitPrice(
      safeProduct,
      selectedVariantItems,
      varients
    );
    setPrice(pricing.price);
    setOffer(pricing.offerPrice);
  }, [safeProduct, selectedVariantItems]);

  // Memoized Values
  const isFlashSaleProduct = useMemo(() => {
    if (!websiteSetup?.payload?.flashSaleProducts || !safeProduct?.id) {
      return false;
    }

    const flashSaleProducts = websiteSetup.payload.flashSaleProducts;
    return flashSaleProducts.find(
      (item) => parseInt(item.product_id, 10) === parseInt(safeProduct.id, 10)
    );
  }, [websiteSetup, safeProduct?.id]);

  // Event Handlers
  const changeImgHandler = useCallback((current) => {
    setSrc(current);
  }, []);

  const increment = useCallback(() => {
    setQuantity((prev) => prev + 1);
  }, []);

  const decrement = useCallback(() => {
    if (quantity > 1) {
      setQuantity((prev) => prev - 1);
    }
  }, [quantity]);

  const selectVarient = useCallback(
    (value) => {
      if (!value || !varients?.length) {
        return;
      }

      if (value.image) {
        changeImgHandler(value.image);
      }

      setSelectedVariantItems((previousItems) => {
        const withoutGroup = previousItems.filter(
          (item) =>
            parseInt(item?.product_variant_id, 10) !==
            parseInt(value?.product_variant_id, 10)
        );
        // Renk yokken sadece standart seçili olabilir; diğer grupları koru
        const others = withoutGroup.length
          ? withoutGroup
          : getInitialVariantItems(varients, { includeColor: false }).filter(
              (item) =>
                parseInt(item?.product_variant_id, 10) !==
                parseInt(value?.product_variant_id, 10)
            );
        return [...others, value];
      });
    },
    [varients, changeImgHandler]
  );

  const selectStandardColor = useCallback(
    (variant) => {
      changeImgHandler(safeProduct?.thumb_image || "");
      if (!variant?.id) return;
      setSelectedVariantItems((previousItems) =>
        previousItems.filter(
          (item) =>
            parseInt(item?.product_variant_id, 10) !==
            parseInt(variant.id, 10)
        )
      );
    },
    [changeImgHandler, safeProduct?.thumb_image]
  );

  const colorGalleryItems = useMemo(
    () => collectColorGalleryItems(varients, safeProduct?.thumb_image),
    [varients, safeProduct?.thumb_image]
  );

  const hasColorVariantSelected = useMemo(
    () =>
      selectedVariantItems.some((item) => {
        const parent = (varients || []).find(
          (v) => Number(v.id) === Number(item.product_variant_id)
        );
        return /renk|color/i.test(String(parent?.name || ""));
      }),
    [selectedVariantItems, varients]
  );

  const selectGalleryImage = useCallback(
    (imagePath, colorItem = null) => {
      changeImgHandler(imagePath || "");
      if (colorItem?.variantItem) {
        selectVarient(colorItem.variantItem);
        return;
      }
      // Ana ürün görseli → standart (renk seçimini kaldır)
      const colorVariant = (varients || []).find((v) =>
        /renk|color/i.test(String(v?.name || ""))
      );
      if (colorVariant) {
        selectStandardColor(colorVariant);
      }
    },
    [changeImgHandler, selectVarient, selectStandardColor, varients]
  );

  const addToCard = useCallback(
    (id, event) => {
      if (!safeProduct?.id) {
        toast.error("Ürün bulunamadı.");
        return;
      }

      const vendor_id = safeProduct?.vendor_id;
      const parentVarients =
        selectedVariantItems?.length > 0
          ? selectedVariantItems.map((v) => {
              const variantObj = varients.find(
                (item) => Number(item.id) === Number(v.product_variant_id)
              );
              return {
                ...v,
                product_variant_name: variantObj ? variantObj.name : "Varyant",
              };
            })
          : [];

      const productShort = {
        product_id: id,
        qty: quantity,
        product: {
          id: id,
          vendor_id: vendor_id,
          name: safeProduct?.name,
          price: safeProduct?.price,
          offer_price: safeProduct?.offer_price,
          thumb_image: safeProduct?.thumb_image,
          slug: safeProduct?.slug,
          barcode: safeProduct?.barcode || null,
          sku: safeProduct?.sku || null,
          sale_unit_qty: safeProduct?.sale_unit_qty || 1,
        },
        variants: parentVarients?.length
          ? parentVarients.map((item) => ({
              variant_id: Number(item.product_variant_id),
              variant_item_id: item.id,
              product_id: id,
              variant_item: {
                id: item.id,
                product_variant_name: item.product_variant_name,
                name: item.name,
                price: item.price,
              },
            }))
          : [],
      };

      if (cart) {
        const checkProduct = cart?.cartProducts.length
          ? cart?.cartProducts.find((item) => item.product_id === id)
          : null;
        const vendorProduct = cart?.cartProducts.length
          ? cart?.cartProducts.find(
              (item) => item.product.vendor_id === vendor_id
            )
          : null;
        const enableMapOrCommission =
          (map_status && Number(map_status) === 1) ||
          (commission_type && commission_type === "subscription");

          if (enableMapOrCommission) {
          if (!vendorProduct) {
            if (checkProduct) {
              toast.error("Bu ürün zaten sepetinizde.");
            } else {
              dispatch(addItem(productShort));
              toast.success("Ürün sepete eklendi!");
              // Trigger flying cart animation
              triggerFlyingCartAnimation(event);
            }
          } else {
            toast.error(
              "Sepetinizde farklı satıcıya ait ürün bulunmaktadır. Aynı satıcıdan ürün ekleyebilirsiniz."
            );
          }
        } else {
          if (checkProduct) {
            toast.error("Bu ürün zaten sepetinizde.");
          } else {
            dispatch(addItem(productShort));
            toast.success("Ürün sepete eklendi!");
            // Trigger flying cart animation
            triggerFlyingCartAnimation(event);
          }
        }
      }
    },
    [
      cart,
      safeProduct,
      selectedVariantItems,
      varients,
      quantity,
      map_status,
      commission_type,
      dispatch,
    ]
  );

  /**
   * Trigger flying cart animation
   * Gets the position of the product card and fixed cart button to animate
   */
  const triggerFlyingCartAnimation = useCallback(
    (event) => {
      // Small delay to ensure DOM is ready
      setTimeout(() => {
        const fixedCartButton = document.querySelector(".fixed-cart-wrapper");

        if (fixedCartButton) {
          const cartRect = fixedCartButton.getBoundingClientRect();

          // Use the exact click position from the event
          const startPosition = {
            x: event ? event.clientX : 0,
            y: event ? event.clientY : 0,
          };

          const endPosition = {
            x: cartRect.left + cartRect.width / 2,
            y: cartRect.top + cartRect.height / 2,
          };

          triggerFlyingCart(
            safeProduct?.thumb_image?.replace(appConfig.BASE_URL, ""),
            startPosition,
            endPosition
          );
        }
      }, 100);
    },
    [triggerFlyingCart, safeProduct?.thumb_image]
  );

  const popupMessageHandler = useCallback(() => {
    if (auth()) {
      messageHandler.toggleHandler(seller);
    } else {
      loginPopupBoard.handlerPopup(true);
    }
  }, [messageHandler, seller, loginPopupBoard]);

  useEffect(() => {
    if (websiteSetup && safeProduct?.price) {
      if (isFlashSaleProduct) {
        const offerFlashSale = websiteSetup.payload?.flashSale;
        const offer = parseAmount(offerFlashSale?.offer || 0);
        const basePrice = parseAmount(safeProduct?.price || 0);
        if (basePrice > 0) {
          const effectivePrice = isEffectiveOfferPrice(
            safeProduct.offer_price,
            safeProduct.price
          )
            ? parseAmount(safeProduct.offer_price)
            : basePrice;
          const discountPrice = (offer / 100) * effectivePrice;
          const mainPrice = effectivePrice - discountPrice;
          setPricePercent(
            Math.trunc(((mainPrice - basePrice) / basePrice) * 100)
          );
        }
      } else {
        const basePrice = parseAmount(safeProduct?.price || 0);
        if (
          basePrice > 0 &&
          isEffectiveOfferPrice(safeProduct?.offer_price, safeProduct?.price)
        ) {
          setPricePercent(
            Math.trunc(
              ((parseAmount(safeProduct.offer_price) - basePrice) / basePrice) *
                100
            )
          );
        } else {
          setPricePercent("");
        }
      }
    } else {
      setPricePercent("");
    }
  }, [websiteSetup, isFlashSaleProduct, safeProduct]);

  if (!safeProduct?.id) {
    return (
      <div className={`product-view w-full ${className || ""}`}>
        <div className="w-full rounded-2xl border border-[#04334a]/10 bg-white p-8 text-center text-[#04334a]/55">
          Ürün detayları şu anda görüntülenemiyor.
        </div>
      </div>
    );
  }

  return (
    <div
      className={`product-view w-full lg:flex lg:items-start lg:justify-between lg:gap-8 ${
        className || ""
      }`}
    >
      {/* Product Images Section */}
      <div data-aos="fade-right" className="lg:w-1/2 lg:sticky lg:top-24">
        <div className="w-full">
          <div className="relative mb-3 flex h-[350px] items-center justify-center overflow-hidden rounded-2xl border border-[#04334a]/10 bg-white md:h-[560px]">
            <Image
              key={`pv-main-${safeProduct?.id}-${bfCacheKey}-${mainImageProps.src}`}
              fill
              style={{ objectFit: "contain" }}
              src={mainImageProps.src}
              unoptimized={mainImageProps.unoptimized}
              alt={product?.name || "Ürün görseli"}
              className="cursor-zoom-in object-contain p-4 transition duration-300 hover:scale-[1.02]"
              onClick={() => setIsImagePreviewOpen(true)}
              priority
              sizes="(max-width: 768px) 100vw, 50vw"
            />
            {isEffectiveOfferPrice(safeProduct?.offer_price, safeProduct?.price) &&
              pricePercent !== "" && pricePercent < 0 && (
              <div className="absolute left-4 top-4 flex h-14 w-14 items-center justify-center rounded-full bg-qyellow text-lg font-800 text-[#04334a] shadow-sm">
                <span>%{Math.abs(pricePercent)}</span>
              </div>
            )}
            {getSaleUnitQty(safeProduct) > 1 && (
              <span className="absolute bottom-3 left-3 z-20 rounded-full border border-[#04334a]/15 bg-qyellow px-2.5 py-1 text-[12px] font-700 leading-none text-[#04334a] md:bottom-4 md:left-4 md:text-sm">
                x{getSaleUnitQty(safeProduct)} adet
              </span>
            )}
          </div>
          <div className="flex flex-wrap gap-2">
            <ProductImage
              src={safeProduct?.thumb_image}
              alt="Standart"
              className={
                src !== safeProduct?.thumb_image || hasColorVariantSelected
                  ? "opacity-50"
                  : ""
              }
              onClick={() =>
                selectGalleryImage(safeProduct?.thumb_image || "", null)
              }
            />
            {colorGalleryItems.map((colorItem) => (
              <ProductImage
                key={`color-${colorItem.variantItem?.id || colorItem.image}`}
                src={colorItem.image}
                alt={colorItem.label || ""}
                className={src !== colorItem.image ? "opacity-50" : ""}
                onClick={() => selectGalleryImage(colorItem.image, colorItem)}
              />
            ))}
            {productsImg &&
              productsImg.length > 0 &&
              productsImg.map((img, i) => {
                const isColorDup = colorGalleryItems.some(
                  (c) => c.image === img.image
                );
                if (isColorDup) return null;
                return (
                  <ProductImage
                    key={`gal-${i}`}
                    src={img.image}
                    alt=""
                    className={src !== img.image ? "opacity-50" : ""}
                    onClick={() => changeImgHandler(img.image)}
                  />
                );
              })}
          </div>
        </div>
      </div>

      {isImagePreviewOpen && (
        <div
          className="fixed inset-0 z-[9999] bg-black/90 flex items-center justify-center p-4"
          onClick={() => setIsImagePreviewOpen(false)}
        >
          <button
            type="button"
            onClick={() => setIsImagePreviewOpen(false)}
            className="absolute top-4 right-4 text-white text-2xl leading-none"
            aria-label="Görsel önizlemeyi kapat"
          >
            ×
          </button>
          <div className="relative w-full max-w-5xl h-[min(80vh,100dvh)] max-h-[100dvh]">
            <Image
              key={`pv-zoom-${safeProduct?.id}-${bfCacheKey}-${mainImageProps.src}`}
              fill
              style={{ objectFit: "contain" }}
              src={mainImageProps.src}
              unoptimized={mainImageProps.unoptimized}
              alt={product?.name || "Ürün görseli büyütülmüş"}
              priority
              sizes="(max-width: 1024px) 100vw, 896px"
            />
          </div>
        </div>
      )}

      {/* Product Details Section */}
      <div className="mt-8 flex-1 lg:mt-0">
        <div className="product-details w-full rounded-2xl border border-[#04334a]/10 bg-white p-5 shadow-sm sm:p-7">
          {/* Brand */}
          {safeProduct?.brand && (
            <span
              data-aos="fade-up"
              className="mb-2 inline-block text-xs font-800 uppercase tracking-widest text-[#04334a]/45"
            >
              {safeProduct?.brand?.name}
            </span>
          )}

          {/* Product Name */}
          <h1
            data-aos="fade-up"
            className="notranslate mb-3 text-xl font-800 leading-snug text-[#04334a] md:text-2xl"
          >
            {safeProduct?.name}
          </h1>

          {/* Rating */}
          <div
            data-aos="fade-up"
            className="mb-5 flex items-center space-x-[10px]"
          >
            <StarRating rating={safeProduct?.averageRating} />
            <span className="text-[13px] font-600 text-[#04334a]">
              {averageRating > 0 ? averageRating.toFixed(1) : "0.0"} puan
            </span>
            <span className="text-[13px] text-[#04334a]/50">
              ({reviewCount} değerlendirme)
            </span>
          </div>

          {/* Price */}
          <div
            data-aos="fade-up"
            className="mb-6 rounded-xl border border-[#04334a]/10 bg-gradient-to-br from-[#FFF8E8] to-[#F4F6F7] p-4"
          >
            {(() => {
              const hasRealDiscount = isEffectiveOfferPrice(offerPrice, price);
              return (
                <>
                  <PriceDisplay
                    price={price}
                    offerPrice={hasRealDiscount ? offerPrice : null}
                    size="lg"
                    layout="stack"
                  />
                  <ProductSaleUnitInfo
                    product={safeProduct}
                    price={price}
                    offerPrice={offerPrice}
                    className="mt-2 text-xs md:text-[13px]"
                  />
                </>
              );
            })()}
          </div>

          {/* Description */}
          <div data-aos="fade-up" className="mb-6">
            <div
              className={`text-sm leading-7 text-[#04334a]/65 ${
                more ? "" : "line-clamp-2"
              }`}
            >
              {safeProduct?.short_description || ""}
            </div>
            {safeProduct?.short_description ? (
              <button
                onClick={() => setMore(!more)}
                type="button"
                className="mt-1 text-xs font-700 text-[#04334a] underline underline-offset-2 hover:text-qyellow"
              >
                {more ? "Daha az göster" : "Devamını göster"}
              </button>
            ) : null}
          </div>

          {/* Variants */}
          <div
            className={`rounded-2xl transition duration-300 ${
              variantFlash ? "bg-qyellow/10 ring-2 ring-qyellow" : ""
            }`}
          >
            {(varients || []).some((v) =>
              /renk|color/i.test(String(v?.name || ""))
            ) && (
              <div className="mb-3 flex flex-wrap gap-2">
                {!hasColorVariantSelected && (
                  <span className="inline-flex items-center rounded-full bg-[#F4F6F7] px-3 py-1 text-xs font-600 text-[#04334a]">
                    Standart ürün
                  </span>
                )}
                {selectedVariantItems.map((item) => {
                  const parent = (varients || []).find(
                    (v) => Number(v.id) === Number(item.product_variant_id)
                  );
                  return (
                    <span
                      key={`${item.product_variant_id}-${item.id}`}
                      className="inline-flex items-center rounded-full bg-qyellow/30 px-3 py-1 text-xs font-600 text-[#04334a]"
                    >
                      {parent?.name || "Seçenek"}: {item.name}
                    </span>
                  );
                })}
              </div>
            )}
            <VariantSelector
              variants={varients || []}
              onSelectVariant={selectVarient}
              onSelectStandard={selectStandardColor}
              basePrice={parseAmount(safeProduct?.price)}
              selectedVariantItems={selectedVariantItems}
              standardSelected={!hasColorVariantSelected}
              thumbImage={safeProduct?.thumb_image || ""}
            />
          </div>

          {/* Quantity and Wishlist */}
          <div
            data-aos="fade-up"
            className="quantity-card-wrapper mb-2 mt-6 flex h-[52px] w-full items-center space-x-[10px]"
          >
            <QuantitySelector
              quantity={quantity}
              onIncrement={increment}
              onDecrement={decrement}
            />
            <div className="flex h-full w-[56px] items-center justify-center rounded-xl border border-[#04334a]/12 bg-[#F4F6F7]">
              {!arWishlist ? (
                <button
                  disabled={addToWishlistLoading}
                  type="button"
                  onClick={() => addToWishlist(safeProduct.id)}
                  className="text-[#04334a]"
                  aria-label="Favorilere ekle"
                >
                  <span className="flex h-10 w-10 items-center justify-center">
                    <ThinLove className="fill-current" />
                  </span>
                </button>
              ) : (
                <button
                  type="button"
                  onClick={() => removeToWishlist(wishlisted?.id)}
                  disabled={removeFromWishlistLoading}
                  className="text-qred"
                  aria-label="Favorilerden çıkar"
                >
                  <span className="flex h-10 w-10 items-center justify-center">
                    <ThinLove fill={true} />
                  </span>
                </button>
              )}
            </div>
            <div className="h-full flex-1">
              <button
                onClick={(e) => addToCard(safeProduct.id, e)}
                type="button"
                className="flex h-full w-full items-center justify-center rounded-xl bg-[#04334a] text-sm font-800 text-white transition hover:bg-[#032736]"
              >
                {ServeLangItem()?.Add_To_Cart}
              </button>
            </div>
          </div>
        </div>

        <ProductFurnitureInquiry product={safeProduct} />

        {/* Product Info */}
        <div
          data-aos="fade-up"
          className="mt-4 mb-4 rounded-2xl border border-[#04334a]/10 bg-white p-4 text-[13px] leading-7 text-[#04334a]/60"
        >
          <p>
            <span className="font-700 text-[#04334a]">Kategori:</span>{" "}
            {displayTurkishLabel(safeProduct?.category?.name || "")}
          </p>
          {tags.length > 0 && (
            <p>
              <span className="font-700 text-[#04334a]">Etiketler:</span>{" "}
              {tags.map((item, i) => (
                <span key={i}>
                  {(item?.value || item?.name || item || "") +
                    (i < tags.length - 1 ? ", " : "")}
                </span>
              ))}
            </p>
          )}
          {(safeProduct?.barcode || safeProduct?.sku) ? (
            <p>
              <span className="font-700 text-[#04334a]">Barkod:</span>{" "}
              {safeProduct?.barcode || safeProduct?.sku}
            </p>
          ) : null}
        </div>

        {/* Report Button */}
        <div
          data-aos="fade-up"
          className="report-btn mb-4 flex items-center space-x-2"
        >
          <span>
            <ReportIco />
          </span>
          <button
            type="button"
            onClick={reportHandler}
            className="text-[13px] font-600 text-qred hover:underline"
          >
            {ServeLangItem()?.Report_This_Item}
          </button>
        </div>

        {/* Social Share */}
        <div
          data-aos="fade-up"
          className="social-share mb-2 flex w-full items-center"
        >
          <span className="mr-[17px] inline-block text-[13px] font-600 text-[#04334a]">
            {ServeLangItem()?.Share_This}
          </span>
          <SocialShareButtons product={safeProduct} />
        </div>
      </div>
    </div>
  );
}
