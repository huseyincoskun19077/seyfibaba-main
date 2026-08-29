import React from "react";
import Slider from ".";
import Link from "next/link";
import Image from "next/image";
import ShopNowBtn from "../Helpers/Buttons/ShopNowBtn";
import { buildProductPath } from "@/utils/url";
import { getProductImageProps } from "@/utils/productImage";

function HomeSlider({ images, settings }) {
  return (
    <Slider {...settings} className="w-full h-full home-slider">
      {images.length > 0 &&
        images.map((item, i) => (
          <div key={i} className="item w-full h-full group relative overflow-hidden">
            <Image
                {...getProductImageProps(item.image)}
                alt={
                  item.title_one || item.title_two
                    ? `${item.title_one || "Seyfibaba"} ${item.title_two || "berber ve kuafor kampanyasi"}`
                    : "Seyfibaba berber ve kuafor malzemeleri slider gorseli"
                }
                fill
                priority={i === 0}
                fetchPriority={i === 0 ? "high" : "auto"}
                sizes="(max-width: 768px) 100vw, (max-width: 1200px) 100vw, 1200px"
                className="object-cover"
                loading={i === 0 ? "eager" : "lazy"}
                quality={i === 0 ? 75 : 60}
            />

            <div className="flex w-full max-w-full h-full relative items-center rtl:pr-[30px] ltr:pl-[30px] z-10">
              <div className="relative z-20 max-w-[90%] md:max-w-xl flex flex-col items-start">
                <div className="w-auto bg-white/90 p-2 rounded-full shadow-sm">
                  <Link
                    href={item.product_slug ? buildProductPath(item.product_slug) : "/products"}
                  >
                    <ShopNowBtn />
                  </Link>
                </div>
              </div>
            </div>
          </div>
        ))}
    </Slider>
  );
}

export default HomeSlider;
