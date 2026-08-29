"use client";
import BreadcrumbCom from "../BreadcrumbCom";
import { useSelector } from "react-redux";
import { useEffect, useState } from "react";
import Image from "next/image";
import ServeLangItem from "../Helpers/ServeLangItem";
import { useRouter } from "next/navigation";
import appConfig from "@/appConfig";
export default function FourZeroFour({ breadcrumb = true }) {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const [siteDate, setSiteDate] = useState(null);
  const router = useRouter();
  useEffect(() => {
    if (!siteDate) {
      if (websiteSetup) {
        setSiteDate(
          websiteSetup && websiteSetup?.payload.image_content.error_page
        );
      }
    }
  }, [siteDate, websiteSetup]);
  return (
    <div className="cart-page-wrapper w-full h-screen flex items-center">
      <div className="container-x mx-auto">
        {breadcrumb && <BreadcrumbCom paths={[{ name: ServeLangItem()?.home, path: "/" }]} />}
        <div className="empty-card-wrapper w-full">
          <div className="flex justify-center items-center w-full">
            <div>
              <div className="sm:mb-10 mb-0 transform sm:scale-100 scale-50">
                {siteDate && (
                  <div className="w-[338px] h-[475px] relative">
                    <Image
                      fill
                      style={{ objectFit: "scale-down" }}
                      src={appConfig.BASE_URL + siteDate}
                      alt="Sayfa bulunamadi gorseli"
                    />
                  </div>
                )}
              </div>
              <div data-aos="fade-up" className="empty-content w-full">
                <h2 className="sm:text-xl text-base font-semibold text-center mb-5">
                  {ServeLangItem()?.Sorry_We_cantt_Find_that_page}
                </h2>
                <div className="flex justify-center w-full">
                  <button type="button" onClick={() => router.back()}>
                    <div className="flex justify-center w-full">
                      <div className="w-[180px] h-[50px] ">
                        <span type="button" className="yellow-btn">
                          {ServeLangItem()?.Back_to_Shop}
                        </span>
                      </div>
                    </div>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
