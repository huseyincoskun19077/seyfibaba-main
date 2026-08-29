import Image from "next/image";
import Link from "next/link";
import { useMemo, useState } from "react";
import appConfig from "@/appConfig";

function hasBrandLogo(brand) {
  const logo = typeof brand?.logo === "string" ? brand.logo.trim() : "";
  if (!logo) return false;

  const lowered = logo.toLowerCase();
  const invalidFragments = [
    "preview.png",
    "placeholder",
    "server-error",
    "noimage",
    "no-image",
    "gorsel-hazirlaniyor",
    "image-not-found",
  ];

  return !invalidFragments.some((fragment) => lowered.includes(fragment));
}

function BrandCard({ brand }) {
  const [logoFailed, setLogoFailed] = useState(false);
  const showLogo = hasBrandLogo(brand) && !logoFailed;

  return (
    <Link
      href={{
        pathname: "/products",
        query: { brand: brand.slug },
      }}
      className="block shrink-0 w-[132px] sm:w-[150px] md:w-[170px] lg:w-[180px]"
    >
      <div className="w-full h-[100px] md:h-[120px] p-4 md:p-6 bg-white border border-gray-100 rounded-2xl flex justify-center items-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer group">
        {showLogo ? (
          <div className="w-full h-full relative">
            <Image
              fill
              className="object-contain transition-opacity duration-300 opacity-80 group-hover:opacity-100"
              src={appConfig.BASE_URL + brand.logo}
              alt={`${brand.name} marka logosu`}
              loading="lazy"
              sizes="(max-width: 768px) 50vw, 16vw"
              onError={() => setLogoFailed(true)}
            />
          </div>
        ) : (
          <span className="text-center text-sm md:text-base font-semibold text-qblack leading-snug px-1 line-clamp-3">
            {brand.name}
          </span>
        )}
      </div>
    </Link>
  );
}

export default function BrandSection({ className, sectionTitle, brands = [] }) {
  const activeBrands = useMemo(
    () => (brands || []).filter((brand) => brand?.slug && brand?.name),
    [brands]
  );

  const { topRow, bottomRow } = useMemo(() => {
    const midpoint = Math.ceil(activeBrands.length / 2);
    return {
      topRow: activeBrands.slice(0, midpoint),
      bottomRow: activeBrands.slice(midpoint),
    };
  }, [activeBrands]);

  if (activeBrands.length === 0) {
    return null;
  }

  return (
    <div data-aos="fade-up" className={`w-full ${className || ""}`}>
      <div className="container-x mx-auto">
        <div className="section-title flex justify-between items-center mb-5 md:mb-6">
          <div className="relative">
            <h2 className="sm:text-2xl text-xl font-bold text-qblacktext leading-none relative z-10">
              {sectionTitle}
            </h2>
            <div className="absolute -bottom-2 left-0 w-1/2 h-1 bg-qyellow rounded-full" />
          </div>
        </div>

        {/* Masaüstü: 6 üst + 6 alt, sağa kaydırılabilir */}
        <div className="hidden md:block overflow-x-auto pb-2 scroll-smooth [scrollbar-width:thin]">
          <div className="min-w-max pr-2">
            <div className="flex gap-4 mb-4">
              {topRow.map((brand) => (
                <BrandCard key={`top-${brand.id}`} brand={brand} />
              ))}
            </div>
            {bottomRow.length > 0 && (
              <div className="flex gap-4">
                {bottomRow.map((brand) => (
                  <BrandCard key={`bottom-${brand.id}`} brand={brand} />
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Mobil: tek satır kaydırma */}
        <div className="md:hidden overflow-x-auto pb-2 scroll-smooth [scrollbar-width:thin]">
          <div className="flex gap-3 min-w-max pr-2">
            {activeBrands.map((brand) => (
              <BrandCard key={brand.id} brand={brand} />
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
