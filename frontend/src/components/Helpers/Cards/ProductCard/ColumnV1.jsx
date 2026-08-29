import Image from "next/image";
import Link from "next/link";
import CurrencyConvert from "@/components/Shared/CurrencyConvert";
import CheckProductIsExistsInFlashSale from "@/components/Shared/CheckProductIsExistsInFlashSale";
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
  const { src: productImage, unoptimized: productImageUnoptimized } =
    getProductImageProps(datas?.image, PRODUCT_IMAGE_FALLBACK);
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
              sizes="(max-width: 768px) 33vw, (max-width: 1200px) 25vw, 20vw"
              className="object-cover transform scale-100 group-hover:scale-105 transition duration-300 ease-in-out"
              loading="lazy"
            />
          </div>
          {packQty > 1 && (
            <span className="absolute left-1.5 bottom-1.5 z-20 rounded-full border border-[#222] bg-qyellow px-2 py-0.5 text-[10px] font-700 leading-none text-[#222] md:left-2 md:bottom-2 md:text-[11px]">
              x{packQty} adet
            </span>
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
          <p className="price flex items-baseline flex-wrap gap-x-1.5 gap-y-0.5">
            <span
              suppressHydrationWarning
              className={`font-700 leading-tight ${
                compact ? "text-[13px] md:text-[16px]" : "text-[14px] md:text-[18px]"
              } ${hasRealDiscount ? "text-[#E11D48]" : "text-qblack"}`}
            >
              {hasRealDiscount ? (
                <CheckProductIsExistsInFlashSale
                  id={datas.id}
                  price={offerPrice}
                />
              ) : (
                <>
                  {isProductInFlashSale && (
                    <span className="line-through text-qgray font-500 text-[11px] mr-1">
                      <CurrencyConvert price={price} />
                    </span>
                  )}
                  <CheckProductIsExistsInFlashSale
                    id={datas.id}
                    price={price}
                  />
                </>
              )}
            </span>
            {hasRealDiscount && (
              <span
                suppressHydrationWarning
                className="line-through text-qgray font-500 text-[10px] md:text-[13px]"
              >
                <CurrencyConvert price={price} />
              </span>
            )}
          </p>
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
