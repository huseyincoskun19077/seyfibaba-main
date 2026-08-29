import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";
import ProductCard from "./Cards/ProductCard";
import DataIteration from "./DataIteration";

export default function SectionStyleTwo({ className, products }) {
  const rs = products.map((item) => {
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
  });
  return (
    <div
      className={`section-content w-full grid sm:grid-cols-2 grid-cols-1 xl:gap-5 gap-3 ${
        className || ""
      }`}
    >
      <DataIteration datas={rs} startLength={0} endLength={4}>
        {({ datas }) => (
          <div key={datas.id} className="item w-full">
            <ProductCard styleType="row-v1" datas={datas} />
          </div>
        )}
      </DataIteration>
    </div>
  );
}
