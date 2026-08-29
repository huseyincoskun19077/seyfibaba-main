import Image from "next/image";
import SubscribeInputWidget from "./Helpers/SubscribeInputWidget";
import appConfig from "@/appConfig";

export default function DiscountBanner({ className, datas }) {
  const hasHeading = Boolean(datas?.header);
  const hasDescription = Boolean(datas?.title);
  const bgImage = datas?.image ? appConfig.BASE_URL + datas.image : null;

  return (
    <div
      className={`w-full relative overflow-hidden print:hidden ${className || ""}`}
    >
      {/* Arka plan görseli */}
      {bgImage && (
        <Image
          src={bgImage}
          alt=""
          fill
          className="object-cover object-center"
          sizes="(max-width: 640px) 100vw, (max-width: 1280px) 100vw, 1280px"
          priority={false}
        />
      )}

      {/* Arka plan görseli yoksa fallback renk */}
      {!bgImage && (
        <div className="absolute inset-0 bg-[#1b3a5c]" />
      )}

      {/* İçerik */}
      <div className="relative z-10 flex flex-col items-center justify-center min-h-[300px] py-12 px-4">
        {(hasHeading || hasDescription) && (
          <div data-aos="fade-up" className="text-center mb-4">
            {hasHeading && (
              <h2 className="sm:text-3xl text-xl font-700 text-qblack mb-2 leading-tight">
                {datas.header}
              </h2>
            )}
            {hasDescription && (
              <p className="sm:text-[18px] text-sm font-400 text-qblack">
                {datas.title}
              </p>
            )}
          </div>
        )}
        <div className="flex justify-center w-full max-w-xl">
          <SubscribeInputWidget />
        </div>
      </div>
    </div>
  );
}
