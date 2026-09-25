import Image from "next/image";
import Link from "next/link";
import { useMemo, useState } from "react";
import PriceDisplay from "@/components/Shared/PriceDisplay";
import QuickViewIco from "../../icons/QuickViewIco";
import ThinLove from "../../icons/ThinLove";
import Compair from "../../icons/Compair";
import ServeLangItem from "../../ServeLangItem";
import AddToCardIco from "../../icons/AddToCardIco";
import { buildProductPath } from "@/utils/url";
import useBfCacheRemountKey from "@/hooks/useBfCacheRemountKey";
import {
  getSaleUnitQty,
  isEffectiveOfferPrice,
} from "@/utils/productPricing";
import ProductSaleUnitInfo from "@/components/Shared/ProductSaleUnitInfo";
import { getProductImageProps } from "@/utils/productImage";

const PRODUCT_IMAGE_FALLBACK = "/assets/images/server-error.png";

function collectColorSwatches(variants = []) {
  const list = [];
  (variants || []).forEach((variant) => {
    if (!/renk|color/i.test(String(variant?.name || ""))) return;
    (variant.active_variant_items || []).forEach((item) => {
      if (!item) return;
      list.push({
        id: item.id,
        name: item.name,
        image: item.image || "",
      });
    });
  });
  return list;
}

function ColumnV1({
  styleType,
  datas,
  addToCart,
  offerPrice,
  price,
  isProductInFlashSale,
  arWishlist,
  addToWishlist,
  removeToWishlist,
  wishlisted,
  addToCompare,
  compact = false,
}) {
  const bfCacheKey = useBfCacheRemountKey();
  const [hoverImage, setHoverImage] = useState(null);
  const colorSwatches = useMemo(
    () => collectColorSwatches(datas?.variants),
    [datas?.variants]
  );
  const { src: productImage, unoptimized: productImageUnoptimized } =
    getProductImageProps(hoverImage || datas?.image, PRODUCT_IMAGE_FALLBACK);
  const packQty = getSaleUnitQty(datas);
  const hasRealDiscount = isEffectiveOfferPrice(offerPrice, price);

  return (
    <div className={`product-card-${styleType} relative h-full`}>
      <div
        className="product-card-one w-full h-full bg-white relative group overflow-hidden rounded-2xl border border-[#EFEFEF]"
        style={{ boxShadow: "0px 10px 28px 0px rgba(0, 0, 0, 0.05)" }}
      >
        <Link
          href={buildProductPath(datas.slug)}
          className="absolute inset-0 z-0"
          aria-label={
            datas.title || ServeLangItem()?.View_Details || "Ürün detayı"
          }
        />
        <Link
          href={buildProductPath(datas.slug)}
          className="product-card-img w-full block relative z-10 aspect-square bg-[#F4F4F6]"
        >
          <div className="w-full h-full relative overflow-hidden">
            <Image
              key={`pc-${datas?.id}-${bfCacheKey}-${productImage}`}
              src={productImage}
              unoptimized={productImageUnoptimized}
              alt={datas.title || "Profesyonel berber ve kuaför ürünü görseli"}
              fill
              sizes="(max-width: 640px) 50vw, (max-width: 768px) 33vw, (max-width: 1024px) 25vw, (max-width: 1280px) 20vw, 16vw"
              className="object-cover transform scale-100 group-hover:scale-105 transition duration-300 ease-in-out"
              loading="lazy"
            />
          </div>
          {packQty > 1 && (
            <span className="absolute left-1.5 bottom-1.5 z-20 rounded-full border border-[#222] bg-qyellow px-2 py-0.5 text-[10px] font-700 leading-none text-[#222] md:left-2 md:bottom-2 md:text-[11px]">
              x{packQty} adet
            </span>
          )}
          {Array.isArray(colorSwatches) && colorSwatches.length > 0 && (
            <div
              className="absolute right-1.5 bottom-1.5 z-20 flex max-w-[70%] flex-wrap justify-end gap-1"
              onClick={(e) => e.preventDefault()}
            >
              {colorSwatches.slice(0, 5).map((c) => (
                <button
                  key={c.id || c.name}
                  type="button"
                  title={c.name}
                  className="h-5 w-5 overflow-hidden rounded-full border border-white shadow-sm ring-1 ring-black/10"
                  onMouseEnter={() => c.image && setHoverImage(c.image)}
                  onMouseLeave={() => setHoverImage(null)}
                  onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (c.image) setHoverImage(c.image);
                  }}
                >
                  {c.image ? (
                    <span className="relative block h-full w-full">
                      <Image
                        src={
                          c.image.startsWith("http")
                            ? c.image
                            : getProductImageProps(c.image).src
                        }
                        alt={c.name || ""}
                        fill
                        className="object-cover"
                        unoptimized
                      />
                    </span>
                  ) : (
                    <span className="block h-full w-full bg-gradient-to-br from-slate-200 to-slate-400" />
                  )}
                </button>
              ))}
              {colorSwatches.length > 5 && (
                <span className="flex h-5 items-center rounded-full bg-black/70 px-1.5 text-[9px] font-700 text-white">
                  +{colorSwatches.length - 5}
                </span>
              )}
            </div>
          )}
        </Link>
        <div
          className={`product-card-details relative z-10 ${
            compact ? "px-1.5 pt-1.5 pb-2 md:px-3 md:pb-3" : "px-2 pt-2 pb-3 md:px-4 md:pb-4"
          }`}
        >
          <div
            className={`absolute w-full left-0 transition-all duration-300 ease-in-out z-20 hidden md:block ${
              compact
                ? "h-9 px-3 top-28 group-hover:top-[52px]"
                : "h-10 px-5 top-36 group-hover:top-[70px]"
            }`}
          >
            <button
              onClick={(e) => addToCart(datas.id, e)}
              type="button"
              data-product-id={datas.id}
              className="yellow-btn group relative w-full h-full flex shadow justify-center items-center overflow-hidden rounded-xl"
            >
              <div className="btn-content flex items-center space-x-2 rtl:space-x-reverse relative z-10">
                <span>
                  <AddToCardIco />
                </span>
                <span className={compact ? "text-xs sm:text-sm" : ""}>
                  {ServeLangItem()?.Add_To_Cart}
                </span>
              </div>
              <div className="bg-shape w-full h-full absolute bg-qblack"></div>
            </button>
          </div>
          <Link href={buildProductPath(datas.slug)} className="relative z-10">
            <h3
              className={`title mb-1 font-600 text-qblack leading-snug line-clamp-2 hover:text-qyellow cursor-pointer ${
                compact ? "text-[11px] md:text-[13px]" : "text-[12px] md:text-[15px]"
              }`}
            >
              {datas.title}
            </h3>
          </Link>
          {colorSwatches.length > 0 && (
            <p className="mb-1 text-[10px] font-600 text-qgray md:text-[11px]">
              {colorSwatches.length} renk seçeneği
            </p>
          )}
          <PriceDisplay
            price={price}
            offerPrice={hasRealDiscount ? offerPrice : null}
            size={compact ? "sm" : "md"}
            layout="stack"
            className="mb-0"
          />
          <ProductSaleUnitInfo
            product={datas}
            price={price}
            offerPrice={offerPrice}
            className="mt-0.5 truncate"
          />
        </div>
        <div className="quick-access-btns flex flex-col space-y-2 relative z-20 max-md:hidden">
          <Link
            className=" absolute group-hover:right-3 -right-10 top-16 transition-all ease-in-out"
            href={buildProductPath(datas.slug)}
            aria-label={ServeLangItem()?.Quick_View || "Hızlı bakış"}
          >
            <span className="hover:bg-qyellow w-9 h-9 flex justify-center text-black hover:text-white items-center transition-all duration-300 ease-in-out hover-bg-qyellow bg-primarygray rounded-xl">
              <QuickViewIco className="fill-current" />
            </span>
          </Link>
          {!arWishlist ? (
            <button
              className=" absolute group-hover:right-3 -right-10 top-[100px] transition-all duration-300 ease-in-out"
              type="button"
              onClick={() => addToWishlist(datas.id)}
              aria-label={ServeLangItem()?.Add_To_Wishlist || "Favorilere Ekle"}
            >
              <span className="hover:bg-qyellow w-9 h-9 flex text-black hover:text-white justify-center items-center transition-all duration-300 ease-in-out hover-bg-qyellow bg-primarygray rounded-xl">
                <ThinLove className="fill-current" />
              </span>
            </button>
          ) : (
            <button
              className="absolute group-hover:right-3 -right-10 top-[100px] transition-all duration-300 ease-in-out"
              type="button"
              onClick={() => removeToWishlist(wishlisted && wishlisted.id)}
              aria-label={
                ServeLangItem()?.Remove_From_Wishlist || "Favorilerden çıkar"
              }
            >
              <span className="hover:bg-qyellow w-9 h-9 flex justify-center items-center bg-primarygray rounded-xl">
                <ThinLove fill={true} />
              </span>
            </button>
          )}

          <button
            className=" absolute group-hover:right-3 -right-10 top-[144px] transition-all duration-500 ease-in-out"
            type="button"
            onClick={() => addToCompare(datas.id)}
            aria-label={ServeLangItem()?.Add_To_Compare || "Karşılaştır"}
          >
            <span className="hover:bg-qyellow w-9 h-9 flex justify-center text-black hover:text-white transition-all duration-300 ease-in-out items-center hover-bg-qyellow bg-primarygray rounded-xl">
              <Compair className="fill-current" />
            </span>
          </button>
        </div>
      </div>
      <span className="anim bottom"></span>
      <span className="anim right"></span>
      <span className="anim top"></span>
      <span className="anim left"></span>
    </div>
  );
}

export default ColumnV1;
