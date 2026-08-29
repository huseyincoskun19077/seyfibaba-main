"use client";
import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";
import ProductCard from "../Helpers/Cards/ProductCard";
import DataIteration from "../Helpers/DataIteration";
import CountDownWidget from "./CountDownWidget";

export default function FlashSale({ fetchData }) {
  const cp =
    fetchData.products &&
    fetchData.products.data.length > 0 &&
    fetchData.products.data.map((item) => {
      return {
        id: item.id,
        category_id: item.category_id,
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
      };
    });
  return (
    <div className="flashsale-wrapper w-full">
      <div className="container-x mx-auto">
        <div className="w-full">
          <div
            style={{
              backgroundImage: `url(${
                appConfig.BASE_URL + fetchData.flashSale.flashsale_page_image
              })`,
              backgroundSize: "cover",
              backgroundRepeat: "no-repeat",
            }}
            data-aos="fade-right"
            className="flash-ad w-full h-[400px] flex sm:justify-end justify-center items-center mb-10"
          >
            <CountDownWidget endTime={fetchData.flashSale.end_time} />
          </div>
          <div className="products grid grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 xl:gap-[30px] gap-2.5">
            <DataIteration datas={cp} startLength={0} endLength={cp.length}>
              {({ datas }) => (
                <div data-aos="fade-up" key={datas.id} className="item">
                  <ProductCard datas={datas} />
                </div>
              )}
            </DataIteration>
          </div>
        </div>
      </div>
    </div>
  );
}
