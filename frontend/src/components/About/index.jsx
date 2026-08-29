"use client";
import Image from "next/image";
import Link from "next/link";
import PageTitle from "../Helpers/PageTitle";
import ServeLangItem from "../Helpers/ServeLangItem";
import FontAwesomeCom from "../Helpers/icons/FontAwesomeCom";
import appConfig from "@/appConfig";

const IMAGE_FALLBACK = "/assets/images/server-error.png";

export default function About({ aboutData }) {
  const aboutUs = aboutData?.aboutUs;
  const aboutBanner = aboutUs?.banner_image
    ? `${appConfig.BASE_URL}${aboutUs.banner_image}`
    : IMAGE_FALLBACK;

  return (
    <div className="about-page-wrapper w-full">
      {/* Breadcrumb */}
      <PageTitle
        title={ServeLangItem()?.About_us}
        breadcrumb={[
          { name: ServeLangItem()?.home, path: "/" },
          { name: ServeLangItem()?.About_us, path: "/about" },
        ]}
      />

      <div className="container-x mx-auto py-10">

        {/* Üst: Görsel + Açıklama */}
        <div className="grid lg:grid-cols-2 gap-10 items-start">

          {/* Görsel */}
          <div className="w-full">
            <div className="w-full h-[400px] lg:h-[500px] relative rounded-2xl overflow-hidden shadow-lg">
              <Image
                src={aboutBanner}
                alt="Seyfibaba Hakkında"
                fill
                className="object-cover"
                sizes="(max-width: 1024px) 100vw, 50vw"
                priority
              />
            </div>
          </div>

          {/* İçerik */}
          <div>
            <h1 className="text-3xl font-bold text-qblack mb-6">Hakkımızda</h1>

            {aboutUs?.description && (
              <div
                id="about-editorial-content"
                className="prose max-w-none text-[15px] text-[#4b5563] leading-8 [&>p]:mb-4"
                dangerouslySetInnerHTML={{ __html: aboutUs.description }}
              />
            )}

            <Link href="/contact">
              <span className="inline-block mt-6 yellow-btn px-6 py-2 cursor-pointer">
                {ServeLangItem()?.Contact_Us}
              </span>
            </Link>
          </div>
        </div>

        {/* Hizmetler */}
        {aboutData?.services?.length > 0 && (
          <div className="mt-12 bg-qyellow rounded-2xl px-8 py-8 grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {aboutData.services.map((item) => (
              <div key={item.id} className="flex items-start space-x-4 rtl:space-x-reverse">
                <div className="flex-shrink-0 mt-1">
                  <FontAwesomeCom className="w-7 h-7 text-qblack" icon={item.icon} />
                </div>
                <div>
                  <p className="text-qblack text-sm font-700 uppercase tracking-wide mb-1">
                    {item.title}
                  </p>
                  <p className="text-sm text-qblack/70 line-clamp-2">
                    {item.description}
                  </p>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
