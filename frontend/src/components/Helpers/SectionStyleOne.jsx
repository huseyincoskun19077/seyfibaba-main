import { useEffect, useState } from "react";
import CategoryCard from "./Cards/CategoryCard";
import DataIteration from "./DataIteration";
import LoaderStyleTwo from "./Loaders/LoaderStyleTwo";
import ViewMoreTitle from "./ViewMoreTitle";
import ProductCard from "./Cards/ProductCard";
import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";
export default function SectionStyleOne({
  className,
  categoryTitle,
  sectionTitle,
  seeMoreUrl,
  categories = [],
  products = [],
  categoryBackground,
}) {
  const cp =
    products.length > 0 &&
    products.map((item) => {
      return {
        id: item.id,
        category_id: item.category_id,
        title: item.name,
        slug: item.slug,
        image: resolveProductImageUrl(item.thumb_image),
        price: item.price,
        offer_price: item.offer_price,
        campaingn_product: null,
        review: parseInt(item.averageRating),
        variants: item.active_variants ? item.active_variants : [],
        sale_unit_qty: item.sale_unit_qty,
      };
    });
  const [selectedId, setId] = useState(
    categories.length > 0 && categories[0].category_id
  );
  useEffect(() => {
    const categoriesIdInProducts =
      cp && cp.length > 0 && cp.map((i) => Number(i.category_id));
    if (categoriesIdInProducts && categoriesIdInProducts.length > 0) {
      const filterCategoriesWhichHaveProducts = categories.filter(
        (category) => {
          const id = Number(category.category_id);
          return categoriesIdInProducts.includes(id);
        }
      );
      if (
        filterCategoriesWhichHaveProducts &&
        filterCategoriesWhichHaveProducts.length > 0
      ) {
        setId(filterCategoriesWhichHaveProducts[0].category_id);
      } else {
        setId(categories.length > 0 && categories[0].category_id);
      }
    }
  }, []);
  const [filterProducts, setFilterProducts] = useState(
    cp && cp.filter((item) => Number(item.category_id) === Number(selectedId))
  );
  useEffect(() => {
    setFilterProducts(
      cp && cp.filter((item) => Number(item.category_id) === Number(selectedId))
    );
  }, [selectedId]);
  const [load, setLoad] = useState(false);

  const categoryHandler = (id) => {
    setLoad(true);
    setTimeout(() => {
      setId(id);
      setLoad(false);
    }, 500);
  };
  return (
    <div data-aos="fade-up" className="ignore-default-aos">
      {categories.length > 0 && products.length > 0 && (
        <div className={`section-style-one  ${className || ""}`}>
          <ViewMoreTitle categoryTitle={sectionTitle} seeMoreUrl={seeMoreUrl}>
            <div className="products-section w-full">
              <div className="grid grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 xl:gap-[30px] gap-2.5">
                <div className="category-card hidden xl:block w-full">
                  <CategoryCard
                    moreUrl={seeMoreUrl}
                    background={categoryBackground}
                    title={categoryTitle}
                    categories={categories}
                    changeIdHandler={categoryHandler}
                    productsInCategoryIds={cp.map((i) =>
                      parseInt(i.category_id)
                    )}
                  />
                </div>
                {load === false ? (
                  filterProducts.length > 0 && (
                    <DataIteration
                      datas={filterProducts}
                      startLength={0}
                      endLength={
                        filterProducts.length > 3 ? 3 : filterProducts.length
                      }
                    >
                      {({ datas }) => (
                        <div key={datas.id} className="item">
                          <ProductCard datas={datas} />
                        </div>
                      )}
                    </DataIteration>
                  )
                ) : (
                  <div className="loading  h-[445px] flex justify-center items-center col-span-3">
                    <LoaderStyleTwo />
                  </div>
                )}
              </div>
            </div>
          </ViewMoreTitle>
        </div>
      )}
    </div>
  );
}
