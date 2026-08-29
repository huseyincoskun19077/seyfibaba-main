import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";
import ProductCard from "./Cards/ProductCard";
import DataIteration from "./DataIteration";
import ViewMoreTitle from "./ViewMoreTitle";

export default function SectionStyleThree({
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
          <div className="grid grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 xl:gap-[30px] gap-2.5">
            <DataIteration datas={rs} startLength={0} endLength={rs.length}>
              {({ datas }) => (
                <div data-aos="fade-up" key={datas.id} className="item">
                  <ProductCard datas={datas} />
                </div>
              )}
            </DataIteration>
          </div>
        </div>
      </ViewMoreTitle>
    </div>
  );
}
