import { useSelector } from "react-redux";
import { useEffect, useState } from "react";
import Image from "next/image";
import ServeLangItem from "../Helpers/ServeLangItem";
import appConfig from "@/appConfig";
import { useRouter } from "next/navigation";

const IMAGE_FALLBACK = "/assets/images/server-error.png";

export default function EmptyCardError() {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const [emptyCart, setEmptyWis] = useState(null);
  const router = useRouter();
  useEffect(() => {
    if (!emptyCart) {
      if (websiteSetup) {
        setEmptyWis(
          websiteSetup.payload?.image_content?.empty_cart || IMAGE_FALLBACK
        );
      }
    }
  }, [emptyCart, websiteSetup]);
  return (
    <div className="empty-card-wrapper w-full">
      <div className="flex justify-center items-center w-full">
        <div>
          <div className="sm:mb-10 mb-5 transform scale-50 sm:scale-100">
            <div className="w-[527px] h-[419px] relative">
              <Image
                fill
                style={{ objectFit: "scale-down" }}
                src={
                  emptyCart === IMAGE_FALLBACK
                    ? IMAGE_FALLBACK
                    : `${appConfig.BASE_URL}/${emptyCart}`.replace(/([^:])\/\//g, '$1/')
                }
                alt="Bos sepet gorseli"
              />
            </div>
          </div>
          <div data-aos="fade-up" className="empty-content w-full">
            <h2 className="sm:text-xl text-base font-semibold text-center mb-5">
              {ServeLangItem()?.Empty_You_dont_Cart_any_Products}
            </h2>
            <div
              onClick={() => router.back()}
              className="flex justify-center w-full cursor-pointer"
            >
              <div className="w-[180px] h-[50px] ">
                <span type="button" className="yellow-btn">
                  {ServeLangItem()?.Back_to_Shop}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
