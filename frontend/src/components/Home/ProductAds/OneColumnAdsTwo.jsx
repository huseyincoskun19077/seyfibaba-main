import React from "react";
import Link from "next/dist/client/link";
import ServeLangItem from "../../Helpers/ServeLangItem";
import appConfig from "@/appConfig";
import Image from "next/image";

function OneColumnAdsTwo({ data }) {
  const translateDbText = (text) => {
    if (!text) return "";
    const translations = {
      "Get the best deal for Headphones": "Profesyonel Ekipmanlarda En İyi Fırsatlar",
      "Shop page center banner": "Mağaza Özel Fırsatları",
      "Center Banner": "Öne Çıkan Fırsatlar"
    };
    return translations[text] || text;
  };

  if (data && data.image) {
    return (
      <div className={`one-column-ads-two md:h-[220px] h-[140px] w-full mb-[30px]`}>
        <div
          data-aos="fade-up"
          className="w-full h-full relative flex items-center rounded-2xl overflow-hidden shadow-xl group border border-gray-100"
        >
          <Image
            src={appConfig.BASE_URL + data.image}
            alt={
              data.title_one || data.title_two
                ? `${data.title_one || "Seyfibaba"} ${data.title_two || "kampanya banneri"}`
                : "Seyfibaba tekli kampanya banneri"
            }
            fill
            className="object-cover"
            sizes="(max-width: 640px) 100vw, (max-width: 1280px) 100vw, 1280px"
            loading="lazy"
          />
          <div className="absolute inset-0 bg-gradient-to-r from-black/50 via-black/20 to-transparent pointer-events-none transition-opacity duration-300 group-hover:opacity-80"></div>
          
          <div className="relative z-10 ltr:pl-8 md:ltr:pl-16 rtl:pr-8 md:rtl:pr-16 py-6 flex flex-col justify-center max-w-[80%] md:max-w-[60%]">
            <div className="backdrop-blur-md bg-white/10 p-6 md:p-8 rounded-3xl border border-white/20 shadow-2xl">
              <div className="mb-4">
                <h2 className="md:text-3xl text-xl font-black text-white drop-shadow-lg leading-tight tracking-tight uppercase italic">
                  {translateDbText(data.title_one)}
                </h2>
              </div>
              <div className="w-auto transform transition-transform duration-300 group-hover:translate-x-2">
                <Link
                  href={{
                    pathname: "/products",
                    query: { category: data.product_slug },
                  }}
                >
                  <div className="inline-flex items-center space-x-3 cursor-pointer group/btn">
                    <span className="text-sm font-bold text-qyellow uppercase tracking-widest border-b-2 border-qyellow pb-1 transition-all duration-300 group-hover/btn:pr-4">
                      {ServeLangItem()?.Shop_Now}
                    </span>
                    <div className="w-8 h-8 rounded-full bg-qyellow/20 flex items-center justify-center transition-all duration-300 group-hover/btn:bg-qyellow group-hover/btn:text-qblack text-qyellow">
                      <svg
                        className="w-4 h-4 fill-current"
                        viewBox="0 0 24 24"
                      >
                        <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z" />
                      </svg>
                    </div>
                  </div>
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default OneColumnAdsTwo;
