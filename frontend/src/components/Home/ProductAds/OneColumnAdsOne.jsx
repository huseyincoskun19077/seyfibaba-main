import React from "react";
import Link from "next/dist/client/link";
import Image from "next/image";
import ServeLangItem from "../../Helpers/ServeLangItem";
import appConfig from "@/appConfig";

function OneColumnAdsOne({ data }) {
  if (data && data.image) {
    return (
      <div
        className={`one-column-ads-one md:h-[295px] h-[190px] md:mb-[60px] mb-[30px] w-full`}
      >
        <div className="container-x mx-auto h-full">
          <div
            data-aos="fade-right"
            className="w-full h-full relative flex justify-center items-center rounded-2xl overflow-hidden shadow-md group"
          >
            <Image
              src={appConfig.BASE_URL + data.image}
              alt={
                data.title_one || data.title_two
                  ? `${data.title_one || "Seyfibaba"} ${data.title_two || "kampanya banneri"}`
                  : "Seyfibaba promosyon banneri"
              }
              fill
              className="object-cover"
              sizes="(max-width: 640px) 100vw, (max-width: 1280px) 100vw, 1280px"
              loading="lazy"
            />
            <div className="absolute inset-0 bg-black/10 transition-colors duration-300 group-hover:bg-black/20"></div>
            <div className="relative z-10 w-full max-w-2xl px-6 py-10 flex flex-col items-center text-center">
              <div className="backdrop-blur-sm bg-white/40 p-10 rounded-3xl border border-white/30 shadow-xl">
                <div className="mb-4">
                  <span className="bg-qyellow text-qblack uppercase text-[10px] tracking-widest font-black px-3 py-1.5 rounded-full inline-block">
                    {data.title_one}
                  </span>
                </div>
                <div className="mb-8">
                  <h2 className="text-3xl md:text-5xl font-black text-qblack leading-tight">
                    {data.title_two}
                  </h2>
                </div>
                <div>
                  <Link
                    href={{
                      pathname: "/products",
                      query: { category: data.product_slug },
                    }}
                  >
                    <div className="inline-flex items-center px-8 py-3 bg-qblack text-white rounded-full font-bold transition-all duration-300 hover:bg-qyellow hover:text-qblack transform hover:-translate-y-1 shadow-lg hover:shadow-qyellow/20 cursor-pointer">
                      <span className="text-sm mr-2">
                        {ServeLangItem()?.Shop_Now}
                      </span>
                      <svg
                        className="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                      </svg>
                    </div>
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default OneColumnAdsOne;
