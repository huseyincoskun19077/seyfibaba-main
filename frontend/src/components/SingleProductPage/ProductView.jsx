"use client";
import Image from "next/image";
import { useContext, useEffect, useState, useMemo, useCallback } from "react";
import { useDispatch, useSelector } from "react-redux";
import { FacebookShareButton, TwitterShareButton } from "react-share";
import { buildProductPath } from "@/utils/url";
import { toast } from "react-toastify";
import auth from "../../utils/auth";
import settings from "../../utils/settings";
import { addItem } from "../../redux/features/cart/cartSlice";
import useWishlist from "../../hooks/useWishlist";
import Star from "../Helpers/icons/Star";
import ThinLove from "../Helpers/icons/ThinLove";
import Selectbox from "../Helpers/Selectbox";
import CheckProductIsExistsInFlashSale from "../Shared/CheckProductIsExistsInFlashSale";
import ServeLangItem from "../Helpers/ServeLangItem";
import LoginContext from "../Contexts/LoginContext";
import messageContext from "../Contexts/MessageContext";
import CurrencyConvert from "../Shared/CurrencyConvert";
import ReportIco from "../Helpers/icons/ReportIco";
import FbIco from "../Helpers/icons/FbIco";
import TwiterIco from "../Helpers/icons/TwiterIco";
import MessageIco from "../Helpers/icons/MessageIco";
import { useFlyingCart } from "../Contexts/FlyingCartContext";
import appConfig from "@/appConfig";
import useBfCacheRemountKey from "@/hooks/useBfCacheRemountKey";
import { displayTurkishLabel } from "@/utils/turkishDisplay";
import { getSaleUnitQty, isEffectiveOfferPrice } from "@/utils/productPricing";
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

const parseAmount = (value) => {
  const parsedValue = parseInt(value, 10);
  return Number.isNaN(parsedValue) ? 0 : parsedValue;
};

const getInitialVariantItems = (variants = []) => {
  return variants
    .map((variant) => variant?.active_variant_items?.[0] || null)
    .filter(Boolean);
};

const calculateVariantPricing = (product, selectedVariantItems = []) => {
  const basePrice = parseAmount(product?.price);
  const baseOfferPrice = isEffectiveOfferPrice(product?.offer_price, product?.price)
    ? parseAmount(product.offer_price)
    : null;
  const variantTotal = selectedVariantItems.reduce(
    (total, item) => total + parseAmount(item?.price),
    0
  );

  return {
    price: basePrice + variantTotal,
    offerPrice:
      baseOfferPrice !== null ? baseOfferPrice + variantTotal : null,
  };
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
  return (
  <div
    onClick={onClick}
    className={`w-[110px] h-[110px] p-[15px] border border-qgray-border cursor-pointer relative ${
      onClick ? "" : "cursor-default"
    }`}
  >
    <Image
      key={`pi-${resolved}-${bfCacheKey}`}
      fill
      style={{ objectFit: "scale-down" }}
      src={resolved}
      unoptimized={unoptimized}
      alt={alt}
      sizes="110px"
      className={`w-full h-full object-contain transform scale-110 ${className}`}
    />
  </div>
  );
};

const QuantitySelector = ({ quantity, onIncrement, onDecrement }) => (
  <div className="w-[120px] h-full px-[26px] flex items-center border border-qgray-border">
    <div className="flex justify-between items-center w-full">
      <button
        onClick={onDecrement}
        type="button"
        className="text-base text-qgray"
      >
        -
      </button>
      <span className="text-qblack">{quantity}</span>
      <button
        onClick={onIncrement}
        type="button"
        className="text-base text-qgray"
      >
        +
      </button>
    </div>
  </div>
);

const VariantSelector = ({ variants, onSelectVariant, basePrice = 0 }) => {
  const [selectedIds, setSelectedIds] = useState({});

  if (!Array.isArray(variants) || variants.length === 0) {
    return null;
  }

  const selectItem = (variant, item) => {
    setSelectedIds((prev) => ({ ...prev, [variant.id]: item.id }));
    onSelectVariant(item);
  };

  return (
    <div className="space-y-5 mb-6">
      {variants.map((variant) => {
        const items = Array.isArray(variant?.active_variant_items)
          ? variant.active_variant_items
          : [];
        if (!items.length) return null;
        const name = String(variant?.name || "");
        const isColor = /renk|color/i.test(name);
        const selectedId = selectedIds[variant.id] ?? items[0]?.id;

        return (
          <div key={variant.id || name}>
            <p className="text-sm font-700 text-qblack mb-3">{name || "Seçenek"}</p>
            {isColor ? (
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                {items.map((item) => {
                  const extra = Number(item.price || 0);
                  const total = Number(basePrice || 0) + extra;
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
                      onClick={() => selectItem(variant, item)}
                      className={`text-left rounded-2xl border p-2 transition ${
                        selected
                          ? "border-qblack ring-2 ring-qyellow"
                          : "border-qgray-border hover:border-qblack"
                      }`}
                    >
                      {imgSrc ? (
                        <span className="block relative w-full h-20 rounded-xl overflow-hidden mb-2 bg-gray-50">
                          <Image src={imgSrc} alt={item.name} fill className="object-cover" unoptimized />
                        </span>
                      ) : (
                        <span className="block h-8 rounded-xl mb-2 bg-gray-100" />
                      )}
                      <span className="block text-sm font-700 text-qblack">{item.name}</span>
                      <span className="block text-xs text-qgray">
                        <CurrencyConvert price={total} />
                      </span>
                    </button>
                  );
                })}
              </div>
            ) : (
              <div className="border border-qgray-border h-[50px] flex justify-between items-center cursor-pointer">
                <Selectbox
                  action={(value) => selectItem(variant, value)}
                  className="w-full px-5"
                  datas={items}
                >
                  {({ item }) => (
                    <div className="flex justify-between items-center w-full">
                      <span className="text-[13px] text-qblack">{item}</span>
                    </div>
                  )}
                </Selectbox>
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
};

const SocialShareButtons = ({ product }) => {
  const safeProduct = product || {};
  const shareUrl =
    typeof window !== "undefined" && window.location.origin
      ? `${window.location.origin}${buildProductPath(safeProduct.slug || "")}`
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

  // State Management
  const [more, setMore] = useState(false);
  const [quantity, setQuantity] = useState(1);
  const [src, setSrc] = useState(safeProduct?.thumb_image || "");
  const [price, setPrice] = useState(null);
  const [offerPrice, setOffer] = useState(null);
  const [pricePercent, setPricePercent] = useState("");

  const [varients, setVarients] = useState(safeVariants);
  const [selectedVariantItems, setSelectedVariantItems] = useState(
    getInitialVariantItems(safeVariants)
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
    const initialVariants = getInitialVariantItems(nextVariants);

    setVarients(nextVariants);
    setSelectedVariantItems(initialVariants);
    setSrc(safeProduct?.thumb_image || "");
    setQuantity(1);
  }, [safeProduct?.id, safeProduct?.thumb_image, safeVariants]);

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
    const pricing = calculateVariantPricing(safeProduct, selectedVariantItems);
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
        const baselineItems = previousItems.length
          ? previousItems
          : getInitialVariantItems(varients);

        return baselineItems.map((item) => {
          if (
            parseInt(item?.product_variant_id, 10) ===
            parseInt(value?.product_variant_id, 10)
          ) {
            return value;
          }
          return item;
        });
      });
    },
    [varients, changeImgHandler]
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
        <div className="w-full rounded border border-qgray-border p-8 text-center text-qgray">
          Ürün detayları şu anda görüntülenemiyor.
        </div>
      </div>
    );
  }

  return (
    <div
      className={`product-view w-full lg:flex justify-between ${
        className || ""
      }`}
    >
      {/* Product Images Section */}
      <div data-aos="fade-right" className="lg:w-1/2 xl:mr-[70px] lg:mr-[50px]">
        <div className="w-full">
          <div className="w-full md:h-[600px] h-[350px] border border-qgray-border flex justify-center items-center overflow-hidden relative mb-3">
            <Image
              key={`pv-main-${safeProduct?.id}-${bfCacheKey}-${mainImageProps.src}`}
              fill
              style={{ objectFit: "scale-down" }}
              src={mainImageProps.src}
              unoptimized={mainImageProps.unoptimized}
              alt={product?.name || "Ürün görseli"}
              className="object-contain transform scale-110 cursor-zoom-in"
              onClick={() => setIsImagePreviewOpen(true)}
              priority
              sizes="(max-width: 768px) 100vw, 50vw"
            />
            {isEffectiveOfferPrice(safeProduct?.offer_price, safeProduct?.price) &&
              pricePercent !== "" && pricePercent < 0 && (
              <div className="w-[80px] h-[80px] rounded-full bg-qyellow text-qblack flex justify-center items-center text-xl font-medium absolute left-[30px] top-[30px]">
                <span className="text-tblack">{Math.abs(pricePercent)}%</span>
              </div>
            )}
            {getSaleUnitQty(safeProduct) > 1 && (
              <span className="absolute left-3 bottom-3 z-20 rounded-full border border-[#222] bg-qyellow px-2.5 py-1 text-[12px] font-700 leading-none text-[#222] md:left-4 md:bottom-4 md:text-sm">
                x{getSaleUnitQty(safeProduct)} adet
              </span>
            )}
          </div>
          <div className="flex gap-2 flex-wrap">
            <ProductImage
              src={safeProduct?.thumb_image}
              alt=""
              className={src !== safeProduct?.thumb_image ? "opacity-50" : ""}
              onClick={() => changeImgHandler(safeProduct?.thumb_image || "")}
            />
            {productsImg &&
              productsImg.length > 0 &&
              productsImg.map((img, i) => (
                <ProductImage
                  key={i}
                  src={img.image}
                  alt=""
                  className={src !== img.image ? "opacity-50" : ""}
                  onClick={() => changeImgHandler(img.image)}
                />
              ))}
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
      <div className="flex-1">
        <div className="product-details w-full mt-10 lg:mt-0">
          {/* Brand */}
          {safeProduct?.brand && (
            <span
              data-aos="fade-up"
              className="text-qgray text-xs font-normal uppercase tracking-wider mb-2 inline-block"
            >
              {safeProduct?.brand?.name}
            </span>
          )}

          {/* Product Name */}
          <h1
            data-aos="fade-up"
            className="text-xl font-medium text-qblack mb-4 notranslate"
          >
            {safeProduct?.name}
          </h1>

          {/* Rating */}
          <div
            data-aos="fade-up"
            className="flex space-x-[10px] items-center mb-6"
          >
            <StarRating rating={safeProduct?.averageRating} />
            <span className="text-[13px] font-normal text-qblack">
              {averageRating > 0 ? averageRating.toFixed(1) : "0.0"} puan
            </span>
            <span className="text-[13px] text-qgray">
              ({reviewCount} değerlendirme)
            </span>
          </div>

          {/* Müşteri puanı/Satıcı ölçeği/Alışveriş sinyali — kaldırıldı (#6) */}

          {/* Price — belirgin UI (#8) */}
          <div
            data-aos="fade-up"
            className="mb-7 p-4 bg-gradient-to-r from-[#fff5f5] to-[#fff0f0] rounded-xl border border-[#ffe0e0]"
          >
            {(() => {
              const hasRealDiscount = isEffectiveOfferPrice(offerPrice, price);
              return (
                <>
                  <div className="flex items-baseline gap-3">
                    <span
                      suppressHydrationWarning
                      className={`main-price font-700 text-[28px] ${
                        hasRealDiscount ? "text-[#E11D48]" : "text-qblack"
                      }`}
                    >
                      {hasRealDiscount ? (
                        <CheckProductIsExistsInFlashSale
                          id={safeProduct.id}
                          price={offerPrice}
                        />
                      ) : (
                        <CheckProductIsExistsInFlashSale
                          id={safeProduct.id}
                          price={price}
                        />
                      )}
                    </span>
                    {hasRealDiscount && (
                      <span
                        suppressHydrationWarning
                        className="line-through text-qgray font-500 text-[16px]"
                      >
                        <CurrencyConvert price={price} />
                      </span>
                    )}
                  </div>
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
          <div data-aos="fade-up" className="mb-[30px]">
            <div
              className={`text-qgray text-sm text-normal leading-7 ${
                more ? "" : "line-clamp-2"
              }`}
            >
              {safeProduct?.short_description || ""}
            </div>
            <button
              onClick={() => setMore(!more)}
              type="button"
              className="text-blue-500 text-xs font-bold"
            >
              {more ? "Daha az göster" : "Devamını göster"}
            </button>
          </div>

          {/* Availability — gizlendi (#5) */}

          {/* Variants */}
          <VariantSelector
            variants={varients || []}
            onSelectVariant={selectVarient}
            basePrice={parseAmount(safeProduct?.price)}
          />

          {/* Quantity and Wishlist */}
          <div
            data-aos="fade-up"
            className="quantity-card-wrapper w-full flex items-center h-[50px] space-x-[10px] mb-[30px]"
          >
            <QuantitySelector
              quantity={quantity}
              onIncrement={increment}
              onDecrement={decrement}
            />
            <div className="w-[60px] h-full flex justify-center items-center border border-qgray-border">
              {!arWishlist ? (
                <button
                  disabled={addToWishlistLoading}
                  type="button"
                  onClick={() => addToWishlist(safeProduct.id)}
                >
                  <span className="w-10 h-10 flex justify-center items-center">
                    <ThinLove className="fill-current" />
                  </span>
                </button>
              ) : (
                <button
                  type="button"
                  onClick={() => removeToWishlist(wishlisted?.id)}
                  disabled={removeFromWishlistLoading}
                >
                  <span className="w-10 h-10 flex justify-center items-center">
                    <ThinLove fill={true} />
                  </span>
                </button>
              )}
            </div>
            {/* Add to Cart Button */}
            <div className="flex-1 h-full">
              <button
                onClick={(e) => addToCard(safeProduct.id, e)}
                type="button"
                className="black-btn text-sm font-semibold w-full h-full"
              >
                {ServeLangItem()?.Add_To_Cart}
              </button>
            </div>
          </div>
        </div>

        <ProductFurnitureInquiry product={safeProduct} />

        {/* Product Info */}
        <div data-aos="fade-up" className="mb-[20px]">
          <p className="text-[13px] text-qgray leading-7">
            <span className="text-qblack">Kategori:</span>{" "}
            {displayTurkishLabel(safeProduct?.category?.name || "")}
          </p>
          {tags.length > 0 && (
            <p className="text-[13px] text-qgray leading-7">
              <span className="text-qblack">Etiketler:</span>{" "}
              {tags.map((item, i) => (
                <span key={i}>
                  {(item?.value || item?.name || item || "") +
                    (i < tags.length - 1 ? ", " : "")}
                </span>
              ))}
            </p>
          )}
          {safeProduct?.sku ? (
            <p className="text-[13px] text-qgray leading-7">
              <span className="text-qblack uppercase">
                {ServeLangItem()?.SKU}:
              </span>{" "}
              {safeProduct.sku}
            </p>
          ) : null}
        </div>

        {/* Report Button */}
        <div
          data-aos="fade-up"
          className="flex space-x-2 items-center mb-[20px] report-btn"
        >
          <span>
            <ReportIco />
          </span>
          <button
            type="button"
            onClick={reportHandler}
            className="text-qred font-semibold text-[13px]"
          >
            {ServeLangItem()?.Report_This_Item}
          </button>
        </div>

        {/* Social Share */}
        <div
          data-aos="fade-up"
          className="social-share flex items-center w-full mb-[20px]"
        >
          <span className="text-qblack text-[13px] mr-[17px] inline-block">
            {ServeLangItem()?.Share_This}
          </span>
          <SocialShareButtons product={safeProduct} />
        </div>

        {/* Satıcıya mesaj — müşteri-satıcı mesajlaşma kaldırıldı (#35) */}
      </div>
    </div>
  );
}
