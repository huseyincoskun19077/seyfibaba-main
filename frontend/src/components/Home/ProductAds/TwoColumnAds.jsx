import React from "react";
import Link from "next/link";
import Image from "next/image";
import ShopNowBtn from "../../Helpers/Buttons/ShopNowBtn";
import ServeLangItem from "../../Helpers/ServeLangItem";
import appConfig from "@/appConfig";

function TwoColumnAds({ bannerOne, bannerTwo }) {
  if (bannerOne || bannerTwo) {
    return (
      <>
        <div
          className={`two-column-ads-section md:mb-[60px] lg:h-[295px] h-[200px] mb-[30px] w-full`}
        >
          <div className="container-x mx-auto h-full">
            <div
              className={`sm:flex xl:space-x-[30px] md:space-x-5 rtl:space-x-reverse items-center w-full h-full  overflow-hidden`}
            >
              {bannerOne && bannerOne.image && (
                <div data-aos="fade-right" className={`h-full sm:w-1/2 w-full rounded-xl overflow-hidden shadow-sm relative`}>
                  <Image
                    src={appConfig.BASE_URL + bannerOne.image}
                    alt={
                      bannerOne.title_one || bannerOne.title_two
                        ? `${bannerOne.title_one || "Seyfibaba"} ${bannerOne.title_two || "kampanya banneri"}`
                        : "Seyfibaba sol kampanya banneri"
                    }
                    fill
                    className="object-cover object-right"
                    sizes="(max-width: 768px) 100vw, 50vw"
                    loading="lazy"
                  />
                  <div className="absolute inset-0 bg-gradient-to-r from-white/60 to-transparent pointer-events-none"></div>
                  <div className="relative z-10 ltr:pl-[40px] rtl:pr-[40px] py-[30px] flex flex-col justify-center h-full max-w-[60%]">
                    <div>
                      {bannerOne.badge && (
                        <div className="mb-2">
                          <span className="bg-qyellow/10 text-qyellow uppercase text-[10px] tracking-wider font-bold px-2 py-1 rounded">
                            {bannerOne.badge}
                          </span>
                        </div>
                      )}
                      <div className="mb-6">
                        <p className="text-lg leading-tight text-qblack font-medium mb-1 line-clamp-1">
                          {bannerOne.title_one}
                        </p>
                        <h2 className="text-2xl lg:text-3xl leading-tight text-qblack font-bold line-clamp-2">
                          {bannerOne.title_two}
                        </h2>
                      </div>
                    </div>
                    <div className="w-auto">
                      <Link
                        href={{
                          pathname: "/products",
                          query: { category: bannerOne.product_slug },
                        }}
                      >
                        <div className="inline-flex items-center group/btn cursor-pointer">
                          <span className="text-sm font-bold text-qblack mr-2 border-b-2 border-qyellow leading-relaxed">
                            {ServeLangItem()?.Shop_Now}
                          </span>
                          <svg
                              className="w-4 h-4 transition-transform duration-300 transform group-hover/btn:translate-x-1"
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
              )}
              {bannerTwo && bannerTwo.image && (
                <div data-aos="fade-left" className={`h-full sm:w-1/2 w-full rounded-xl overflow-hidden shadow-sm relative`}>
                  <Image
                    src={appConfig.BASE_URL + bannerTwo.image}
                    alt={
                      bannerTwo.title_one || bannerTwo.title_two
                        ? `${bannerTwo.title_one || "Seyfibaba"} ${bannerTwo.title_two || "kampanya banneri"}`
                        : "Seyfibaba sag kampanya banneri"
                    }
                    fill
                    className="object-cover object-right"
                    sizes="(max-width: 768px) 100vw, 50vw"
                    loading="lazy"
                  />
                  <div className="absolute inset-0 bg-gradient-to-r from-white/60 to-transparent pointer-events-none"></div>
                  <div className="relative z-10 ltr:pl-[40px] rtl:pr-[40px] py-[30px] flex flex-col justify-center h-full max-w-[60%]">
                    <div>
                      {bannerTwo.badge && (
                        <div className="mb-2">
                          <span className="bg-qyellow/10 text-qyellow uppercase text-[10px] tracking-wider font-bold px-2 py-1 rounded">
                            {bannerTwo.badge}
                          </span>
                        </div>
                      )}
                      <div className="mb-6">
                        <p className="text-lg leading-tight text-qblack font-medium mb-1 line-clamp-1">
                          {bannerTwo.title_one}
                        </p>
                        <h2 className="text-2xl lg:text-3xl leading-tight text-qblack font-bold line-clamp-2">
                          {bannerTwo.title_two}
                        </h2>
                      </div>
                    </div>
                    <div className="w-auto">
                      <Link
                        href={{
                          pathname: "/products",
                          query: { category: bannerTwo.product_slug },
                        }}
                      >
                        <div className="inline-flex items-center group/btn cursor-pointer">
                          <span className="text-sm font-bold text-qblack mr-2 border-b-2 border-qyellow leading-relaxed">
                            {ServeLangItem()?.Shop_Now}
                          </span>
                          <svg
                              className="w-4 h-4 transition-transform duration-300 transform group-hover/btn:translate-x-1"
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
              )}
            </div>
          </div>
        </div>
      </>
    );
  }
}

export default TwoColumnAds;
