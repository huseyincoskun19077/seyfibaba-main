import React from "react";
import ProductView from "@/components/SingleProductPage/ProductView";
import ProductViewCloseIco from "../../icons/ProductViewCloseIco";

function ProductQuickView({ quickViewModal, quickViewData, setQuickView }) {
  return (
    <div className="quicke-view-wrapper w-full h-full flex fixed left-0 top-0 justify-center z-50 items-center ">
      <div
        onClick={() => setQuickView(!quickViewModal)}
        className="w-full h-full fixed left-0 top-0 bg-black  bg-opacity-25"
      ></div>
      <div
        data-aos="fade-up"
        className="md:mx-10 xl:mt-[100px] rounded w-[calc(100%-1.5rem)] max-w-5xl bg-white relative lg:py-[40px] pt-[72px] pb-[40px] sm:px-[38px] px-3 md:mt-12 max-h-[100dvh] overflow-y-auto"
        style={{ zIndex: "999" }}
      >
        <div className="w-full h-full overflow-y-scroll overflow-style-none">
          <ProductView
            images={
              quickViewData?.gellery?.length > 0 ? quickViewData.gellery : []
            }
            product={quickViewData.product}
          />
        </div>
        <button
          onClick={() => setQuickView(!quickViewModal)}
          type="button"
          className="absolute right-3 top-3"
        >
          <span className="text-red-500 w-12 h-12 flex justify-center items-center rounded border border-qred">
            <ProductViewCloseIco />
          </span>
        </button>
      </div>
    </div>
  );
}

export default ProductQuickView;
