import Image from "next/image";
import CurrencyConvert from "@/components/Shared/CurrencyConvert";
import CheckProductIsExistsInFlashSale from "@/components/Shared/CheckProductIsExistsInFlashSale";
import ServeLangItem from "../../ServeLangItem";
import Link from "next/link";
import QuickViewIco from "../../icons/QuickViewIco";
import ThinLove from "../../icons/ThinLove";
import Compair from "../../icons/Compair";
import { buildProductPath } from "@/utils/url";
import useBfCacheRemountKey from "@/hooks/useBfCacheRemountKey";
import { isEffectiveOfferPrice } from "@/utils/productPricing";
import ProductSaleUnitInfo from "@/components/Shared/ProductSaleUnitInfo";
import { getProductImageProps } from "@/utils/productImage";

const PRODUCT_IMAGE_FALLBACK = "/assets/images/server-error.png";

function RowV1({
  styleType,
  datas,
  offerPrice,
  price,
  isProductInFlashSale,
  addToCart,
  arWishlist,
  addToWishlist,
  removeToWishlist,
  wishlisted,
  addToCompare,
}) {
  const bfCacheKey = useBfCacheRemountKey();
  const { src: productImage, unoptimized: productImageUnoptimized } = getProductImageProps(datas?.image, PRODUCT_IMAGE_FALLBACK);
  const hasRealDiscount = isEffectiveOfferPrice(offerPrice, price);

  return (
    <div className={`w-full relative`}>
      <div
        data-aos="fade-left"
        className={`product-card-${styleType} w-full lg:h-[230px] h-[180px] bg-white group relative overflow-hidden rounded-2xl border border-[#EFEFEF]`}
      >
        {/* Kartın boş alanına tıklayınca ürün detayına git */}
        <Link
          href={buildProductPath(datas.slug)}
          className="absolute inset-0 z-0"
          aria-label={datas.title || ServeLangItem()?.View_Details || "Ürün detayı"}
        />
        <div className="flex space-x-5 items-center w-full h-full lg:p-[30px] sm:p-5 p-2">
          <div className="lg:w-[200px] w-[110px] h-[110px] lg:h-[170px] relative overflow-hidden rounded-xl bg-[#F4F4F6] z-10 shrink-0">
            <Image
              key={`pr1-${datas?.id}-${bfCacheKey}-${productImage}`}
              fill
              sizes="(max-width: 768px) 33vw, 200px"
              src={productImage}
              unoptimized={productImageUnoptimized}
              alt={datas.title || "Profesyonel berber ve kuaför ürünü görseli"}
              className="object-cover"
              loading="lazy"
            />
          </div>
          <div className="flex-1 flex flex-col justify-center h-full z-10">
            <div>
              <Link href={buildProductPath(datas.slug)}>
                <h3 className="title mb-2 sm:text-[15px] text-[13px] font-600 text-qblack leading-[24px] line-clamp-2 hover:text-qyellow cursor-pointer">
                  {datas.title}
                </h3>
              </Link>
              <p className="price mb-[26px]">
                <span
                  suppressHydrationWarning
                className={`main-price  font-600 text-[18px] ${
                  hasRealDiscount ? "line-through text-qgray" : "text-qred"
                }`}
              >
                {hasRealDiscount ? (
                    <span>
                      {" "}
                      <CurrencyConvert price={price} />
                    </span>
                  ) : (
                    <>
                      {isProductInFlashSale && (
                        <span
                          className={`line-through text-qgray font-500 text-[16px] mr-2`}
                        >
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
                    className="offer-price text-qred font-600 text-[18px] ml-2"
                  >
                    <CheckProductIsExistsInFlashSale
                      id={datas.id}
                      price={offerPrice}
                    />
                  </span>
                )}
              </p>
              <ProductSaleUnitInfo
                product={datas}
                price={price}
                offerPrice={offerPrice}
                className="mb-3 text-[11px]"
              />
              <button
                onClick={(e) => addToCart(datas.id, e)}
                type="button"
                data-product-id={datas.id}
                className="w-[110px] h-[30px]"
              >
                <span className="yellow-btn">
                  {ServeLangItem()?.Add_To_Cart}
                </span>
              </button>
            </div>
          </div>
        </div>
        {/* quick-access-btns */}
        <div className="quick-access-btns flex flex-col space-y-2 relative z-20">
          <Link
            className=" absolute group-hover:left-4 -left-10 top-5  transition-all ease-in-out"
            href={buildProductPath(datas.slug)}
            aria-label={ServeLangItem()?.Quick_View || "Hızlı bakış"}
          >
            <span className="hover:bg-qyellow w-10 h-10 flex justify-center text-black hover:text-white items-center transition-all duration-300 ease-in-out hover-bg-qyellow bg-primarygray rounded">
              <QuickViewIco className="fill-current" />
            </span>
          </Link>
          {!arWishlist ? (
            <button
              className=" absolute group-hover:left-4 -left-10 top-[60px] duration-300   transition-all ease-in-out"
              type="button"
              onClick={() => addToWishlist(datas.id)}
              aria-label={ServeLangItem()?.Add_To_Wishlist || "Favorilere Ekle"}
            >
              <span className="hover:bg-qyellow w-10 h-10 flex text-black hover:text-white justify-center items-center transition-all duration-300 ease-in-out hover-bg-qyellow bg-primarygray rounded">
                <ThinLove className="fill-current" />
              </span>
            </button>
          ) : (
            <button
              className=" absolute group-hover:left-4 -left-10 top-[60px] duration-300   transition-all ease-in-out"
              type="button"
              onClick={() => removeToWishlist(wishlisted && wishlisted.id)}
              aria-label={ServeLangItem()?.Remove_From_Wishlist || "Favorilerden çıkar"}
            >
              <span className="hover:bg-qyellow w-10 h-10 flex justify-center items-center bg-primarygray rounded">
                <ThinLove fill={true} />
              </span>
            </button>
          )}
          <button
            className=" absolute group-hover:left-4 -left-10 top-[107px]  transition-all duration-500 ease-in-out"
            type="button"
            onClick={() => addToCompare(datas.id)}
            aria-label={ServeLangItem()?.Add_To_Compare || "Karşılaştır"}
          >
            <span className="hover:bg-qyellow w-10 h-10 flex justify-center text-black hover:text-white transition-all duration-300 ease-in-out items-center hover-bg-qyellow bg-primarygray rounded">
              <Compair className="fill-current" />
            </span>
          </button>
        </div>
      </div>
      {/* on hover square animation */}
      <span className="anim bottom"></span>
      <span className="anim right"></span>
      <span className="anim top"></span>
      <span className="anim left"></span>
    </div>
  );
}

export default RowV1;
