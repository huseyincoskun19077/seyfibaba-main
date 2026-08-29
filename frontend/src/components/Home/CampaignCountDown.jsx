import Link from "next/link";
import Image from "next/image";
import CountDown from "../Helpers/CountDown";
import ShopNowBtn from "../Helpers/Buttons/ShopNowBtn";
import GooglePlay from "../Helpers/icons/GooglePlay";
import AppleStore from "../Helpers/icons/AppleStore";
import ServeLangItem from "../Helpers/ServeLangItem";
import appConfig from "@/appConfig";
import { isFlashSaleActive } from "@/utils/flashSale";

export default function CampaignCountDown({
  className,
  lastDate,
  downloadData,
  flashSaleData,
}) {
  const flashSaleActive = isFlashSaleActive(flashSaleData);
  const { showDate, showHour, showMinute, showSecound } = CountDown(
    flashSaleActive ? lastDate : null
  );

  const playStoreUrl = downloadData?.play_store?.trim();
  const appStoreUrl = downloadData?.app_store?.trim();
  const hasAppLinks = Boolean(playStoreUrl || appStoreUrl);

  if (!flashSaleActive && !hasAppLinks) return null;

  const showDownloadSection = Boolean(downloadData) && hasAppLinks;

  return (
    <div>
      <div className={`w-full lg:h-[460px] ${className || ""}`}>
        <div className="container-x mx-auto h-full">
          <div className="lg:flex xl:space-x-[30px] lg:space-x-5 rtl:space-x-reverse items-center h-full">
            {flashSaleActive && (
            <div
              data-aos="fade-right"
              className={`campaign-countdown group h-[300px] sm:h-[400px] lg:h-full w-full mb-5 lg:mb-0 ${
                showDownloadSection ? "lg:w-1/2" : "lg:w-full"
              }`}
              style={{
                background: `url(${
                  flashSaleData?.homepage_image
                    ? appConfig.BASE_URL + flashSaleData.homepage_image
                    : ""
                }) no-repeat`,
                backgroundSize: "cover",
              }}
            >
              <div className="w-full h-full xl:p-12 p-5 bg-black/40 flex flex-col justify-between">
                <div className="countdown-wrapper w-full flex lg:justify-between justify-evenly lg:mb-10 mb-2">
                  <div className="countdown-item">
                    <div className="countdown-number sm:w-[100px] sm:h-[100px] w-[50px] h-[50px] rounded-full bg-white flex justify-center items-center">
                      <span className="font-700 sm:text-[30px] text-[14px] text-[#EB5757] notranslate">
                        {showDate}
                      </span>
                    </div>
                    <p className="sm:text-[18px] text-[12px] font-500 text-center leading-8 text-white">
                      {ServeLangItem()?.Days}
                    </p>
                  </div>
                  <div className="countdown-item">
                    <div className="countdown-number sm:w-[100px] sm:h-[100px] w-[50px] h-[50px] rounded-full bg-white flex justify-center items-center">
                      <span className="font-700 sm:text-[30px] text-[14px] text-[#2F80ED] notranslate">
                        {showHour}
                      </span>
                    </div>
                    <p className="sm:text-[18px] text-[12px] font-500 text-center leading-8 text-white">
                      {ServeLangItem()?.Hours}
                    </p>
                  </div>
                  <div className="countdown-item">
                    <div className="countdown-number sm:w-[100px] sm:h-[100px] w-[50px] h-[50px] rounded-full bg-white flex justify-center items-center">
                      <span className="font-700 sm:text-[30px] text-[14px] text-[#219653] notranslate">
                        {showMinute}
                      </span>
                    </div>
                    <p className="sm:text-[18px] text-[12px] font-500 text-center leading-8 text-white">
                      {ServeLangItem()?.Minutes}
                    </p>
                  </div>
                  <div className="countdown-item">
                    <div className="countdown-number sm:w-[100px] sm:h-[100px] w-[50px] h-[50px] rounded-full bg-white flex justify-center items-center">
                      <span className="font-700 sm:text-[30px] text-[14px] text-[#EF5DA8] notranslate">
                        {showSecound}
                      </span>
                    </div>
                    <p className="sm:text-[18px] text-[12px] font-500 text-center leading-8 text-white">
                      {ServeLangItem()?.Seconds}
                    </p>
                  </div>
                </div>
                <div>
                  <div className="countdown-title mb-4">
                    <h2 className="sm:text-[36px] text-[24px] text-white font-700 leading-tight drop-shadow-lg">
                      {flashSaleData.title}
                    </h2>
                  </div>
                  <div className="w-auto">
                    <Link href="/flash-sale">
                      <ShopNowBtn />
                    </Link>
                  </div>
                </div>
              </div>
            </div>
            )}
            {showDownloadSection && (
            <div
              data-aos="fade-left"
              className={`download-app lg:h-full h-[430px] xl:p-12 p-5 relative overflow-hidden ${
                flashSaleActive ? "flex-1" : "w-full"
              }`}
            >
              <Image
                src={
                  downloadData?.image
                    ? appConfig.BASE_URL + downloadData.image
                    : "/assets/images/download-app-cover.png"
                }
                alt="Seyfibaba mobil uygulama"
                fill
                className="object-cover"
                sizes="(max-width: 1200px) 100vw, 560px"
                loading="lazy"
              />
              <div className="flex flex-col h-full justify-between relative z-10">
                <div className="get-app">
                  <p className="text-[13px] font-600 text-qblack mb-3">
                    {ServeLangItem()?.MOBILE_APP_VERSION}
                  </p>
                  <h2 className="lg:text-[30px] text-2xl font-600 text-qblack leading-10 mb-8">
                    {ServeLangItem()?.Get_Our}
                    <span className="text-qred border-b-2 border-qred mx-2">
                      {ServeLangItem()?.Mobile_App}
                    </span>
                    <br /> {ServeLangItem()?.Its_Make_easy_for_you_life}
                  </h2>
                  <div className="flex space-x-5 rtl:space-x-reverse items-center">
                    {playStoreUrl && (
                    <div className="bg-white w-[170px] h-[60px] flex justify-center items-center cursor-pointer">
                      <Link
                        href={playStoreUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Google Play uzerinden mobil uygulamayi indir"
                      >
                        <GooglePlay />
                      </Link>
                    </div>
                    )}
                    {appStoreUrl && (
                    <div className="bg-white w-[170px] h-[60px] flex justify-center items-center cursor-pointer">
                      <Link
                        href={appStoreUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="App Store uzerinden mobil uygulamayi indir"
                      >
                        <AppleStore />
                      </Link>
                    </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
