import CheckProductIsExistsInFlashSale from "@/components/Shared/CheckProductIsExistsInFlashSale";
import CurrencyConvert from "@/components/Shared/CurrencyConvert";
import Image from "next/image";
import Link from "next/link";
import { buildProductPath } from "@/utils/url";
import useBfCacheRemountKey from "@/hooks/useBfCacheRemountKey";
import { isEffectiveOfferPrice } from "@/utils/productPricing";
import { getProductImageProps } from "@/utils/productImage";

const PRODUCT_IMAGE_FALLBACK = "/assets/images/server-error.png";

function RowV2({ styleType, datas, offerPrice, price, isProductInFlashSale }) {
  const bfCacheKey = useBfCacheRemountKey();
  const { src: productImage, unoptimized: productImageUnoptimized } = getProductImageProps(datas?.image, PRODUCT_IMAGE_FALLBACK);
  const hasRealDiscount = isEffectiveOfferPrice(offerPrice, price);

  return (
    <div className={`product-card-${styleType} w-full`}>
      <div className="w-full h-[105px] bg-white border border-primarygray px-5 ">
        <div className="w-full h-full flex space-x-5 justify-center items-center">
          <div className="w-[75px] h-full relative">
            <Image
              key={`pr2-${datas?.id}-${bfCacheKey}-${productImage}`}
              fill
              sizes="100%"
              src={productImage}
              unoptimized={productImageUnoptimized}
              alt={datas.title || "Profesyonel berber ve kuaför ürünü görseli"}
              style={{ objectFit: "scale-down" }}
              className="w-full h-full"
            />
          </div>
          <div className="flex-1 h-full flex flex-col justify-center">
            <Link href={buildProductPath(datas.slug)}>
              <h3 className="title mb-2 sm:text-[15px] text-[13px] font-600 text-qblack leading-[24px] line-clamp-1 hover:text-qyellow cursor-pointer">
                {datas.title}
              </h3>
            </Link>

            <p className="price">
              <span
                suppressHydrationWarning
                className={`main-price  font-600 text-[18px] ${
                  hasRealDiscount ? "line-through text-qgray" : "text-qred"
                }`}
              >
                {hasRealDiscount ? (
                  <span>
                    <CurrencyConvert price={price} />
                  </span>
                ) : (
                  <>
                    {isProductInFlashSale && (
                      <span className="line-through text-qgray font-500 text-base mr-2">
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
          </div>
        </div>
      </div>
    </div>
  );
}

export default RowV2;
