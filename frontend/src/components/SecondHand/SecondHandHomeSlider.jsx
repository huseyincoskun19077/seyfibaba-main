"use client";

import Link from "next/link";
import appConfig from "@/appConfig";
import Slider from "@/components/Slider";

function slideSrc(image) {
  const raw = String(image || "").trim();
  if (!raw) return "";
  if (/^https?:\/\//i.test(raw)) return raw;
  return `${appConfig.BASE_URL}${raw.replace(/^\/+/, "")}`;
}

export default function SecondHandHomeSlider({ slides = [] }) {
  const items = Array.isArray(slides) ? slides.filter((s) => s?.image) : [];
  if (items.length === 0) return null;

  const settings = {
    pagination: { clickable: true },
    loop: items.length > 1,
    autoplay: items.length > 1 ? { delay: 3500, disableOnInteraction: false } : false,
    effect: "fade",
  };

  return (
    <div className="w-full h-[180px] sm:h-[260px] md:h-[380px] rounded-2xl md:rounded-3xl overflow-hidden shadow-sm bg-qgray-border [&_.swiper]:h-full [&_.swiper-slide]:h-full [&_.swiper-slide]:overflow-hidden">
      <Slider {...settings} className="w-full h-full">
        {items.map((item, i) => {
          const href = item.link || "#ilanlar";
          const src = slideSrc(item.image);
          const inner = (
            <div className="relative w-full h-full">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src={src}
                alt={item.title || "İkinci el slider"}
                className="absolute inset-0 w-full h-full object-cover"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/55 via-black/15 to-transparent" />
              {(item.title || item.subtitle) && (
                <div className="absolute inset-x-0 bottom-0 p-4 sm:p-6 md:p-8">
                  {item.title ? (
                    <p className="text-white text-lg sm:text-2xl md:text-3xl font-800 leading-tight drop-shadow">
                      {item.title}
                    </p>
                  ) : null}
                  {item.subtitle ? (
                    <p className="mt-1 text-white/90 text-xs sm:text-sm md:text-base max-w-xl line-clamp-2">
                      {item.subtitle}
                    </p>
                  ) : null}
                </div>
              )}
            </div>
          );

          if (href.startsWith("http") || href.startsWith("#")) {
            return (
              <a key={item.id || i} href={href} className="block w-full h-full">
                {inner}
              </a>
            );
          }

          return (
            <Link key={item.id || i} href={href} className="block w-full h-full">
              {inner}
            </Link>
          );
        })}
      </Slider>
    </div>
  );
}
