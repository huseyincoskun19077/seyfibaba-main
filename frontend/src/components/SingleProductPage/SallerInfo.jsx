import Image from "next/image";
import ProductCard from "../Helpers/Cards/ProductCard";
import DataIteration from "../Helpers/DataIteration";
import Star from "../Helpers/icons/Star";
import ServeLangItem from "../Helpers/ServeLangItem";
import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";
export default function SallerInfo({ products, sellerInfo }) {
  const seller = sellerInfo?.seller;
  const rs =
    products?.length > 0
      ? products.map((item) => {
          return {
            id: item.id,
            title: item.name,
            slug: item.slug,
            image: resolveProductImageUrl(item.thumb_image),
            price: item.price,
            offer_price: item.offer_price,
            campaingn_product: null,
            vendor_id: Number(item.vendor_id),
            review: parseInt(item.averageRating),
            variants: item.active_variants,
            sale_unit_qty: item.sale_unit_qty,
          };
        })
      : [];
  return (
    <div id="seller-info" className="saller-info-wrapper w-full">
      <div className="saller-info sm:flex justify-between items-center pb-[30px] border-b border-[#E8E8E8]">
        <div className="sm:flex sm:space-x-5 items-center sm:w-1/4">
          <div className="saller w-[73px] h-[73px] rounded-full overflow-hidden relative">
            {seller?.user && (
              <Image
                layout="fill"
                src={`${
                  seller.user.image
                    ? appConfig.BASE_URL + seller.user.image
                    : "/assets/images/Group.png"
                }`}
                alt="saller"
                className="w-full h-full object-cover"
              />
            )}
          </div>
          <div>
            <h6 className="text-[18px] font-medium leading-[30px]">
              {seller?.user?.name || ""}
            </h6>
            <p className="text-[13px] font-normal text-qgray leading-[30px]">
              {seller?.address}
            </p>
            <div className="flex items-center mt-4">
              <div className="flex">
                {Array.from(Array(parseInt(seller?.averageRating) || 0), (_, index) => (
                  <span key={`seller-v1-star-filled-${index}`}>
                    <Star />
                  </span>
                ))}
                {(parseInt(seller?.averageRating) || 0) < 5 && (
                  <>
                    {Array.from(
                      Array(5 - (parseInt(seller?.averageRating) || 0)),
                      (_, index) => (
                        <span
                          key={`seller-v1-star-empty-${index}`}
                          className="text-gray-500"
                        >
                          <Star defaultValue={false} />
                        </span>
                      )
                    )}
                  </>
                )}
              </div>
              <span className="text-[13px] font-normal ml-1 leading-none">
                ({parseInt(seller?.averageRating) || 0})
              </span>
            </div>
          </div>
        </div>
        <div className="flex-1 w-full sm:flex sm:space-x-5 justify-between sm:ml-[60px] mt-5 sm:mt-0">
          <div className="w-full mb-5 sm:mb-0">
            <ul>
              <li className="text-qgray leading-[30px]">
                <span className="text-[15px] text-qblack font-medium capitalize">
                  {ServeLangItem()?.products}
                </span>
                : {seller?.sellerTotalProducts}
              </li>
              <li className="text-qgray leading-[30px]">
                <span className="text-[15px] text-qblack font-medium capitalize">
                  {ServeLangItem()?.Shop_Name}
                </span>
                : {seller?.shop_name}
              </li>
              <li className="text-qgray leading-[30px]">
                <span className="text-[15px] text-qblack font-medium capitalize">
                  {ServeLangItem()?.phone}
                </span>
                : {seller?.phone}
              </li>
            </ul>
          </div>
        </div>
      </div>
      <div className="saller-product w-full mt-[30px]">
        <h2 className="text-[18px] font-medium mb-5">
          {ServeLangItem()?.Product_from_Shop}
        </h2>
        <div className="grid grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 xl:gap-[30px] gap-2.5">
          <DataIteration
            datas={rs}
            startLength={0}
            endLength={rs.length > 4 ? 4 : rs.length}
          >
            {({ datas }) => (
              <div key={datas.id} className="item">
                <ProductCard datas={datas} />
              </div>
            )}
          </DataIteration>
        </div>
      </div>
    </div>
  );
}
