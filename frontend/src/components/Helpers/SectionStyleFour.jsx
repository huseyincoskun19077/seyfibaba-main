import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";
import ProductCard from "./Cards/ProductCard";
import DataIteration from "./DataIteration";
import ViewMoreTitle from "./ViewMoreTitle";

export default function SectionStyleFour({
  className,
  sectionTitle,
  seeMoreUrl,
  products = [],
}) {
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
    <div className={`section-style-one ${className || ""}`}>
      <ViewMoreTitle categoryTitle={sectionTitle} seeMoreUrl={seeMoreUrl}>
        <div className="products-section w-full">
          <div className="grid lg:grid-cols-3 grid-cols-1 xl:gap-[30px] lg:gap-5">
            <div className="item-col">
              <DataIteration datas={rs} startLength={0} endLength={4}>
                {({ datas }) => (
                  <ProductCard
                    styleType="row-v2"
                    key={datas.id}
                    datas={datas}
                  />
                )}
              </DataIteration>
            </div>
            <div className="item-col">
              <DataIteration datas={rs} startLength={4} endLength={8}>
                {({ datas }) => (
                  <ProductCard
                    styleType="row-v2"
                    key={datas.id}
                    datas={datas}
                  />
                )}
              </DataIteration>
            </div>
            <div className="item-col">
              <DataIteration datas={rs} startLength={8} endLength={12}>
                {({ datas }) => (
                  <ProductCard
                    styleType="row-v2"
                    key={datas.id}
                    datas={datas}
                  />
                )}
              </DataIteration>
            </div>
          </div>
        </div>
      </ViewMoreTitle>
    </div>
  );
}
