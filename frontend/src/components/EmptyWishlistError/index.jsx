import { useEffect, useState } from "react";
import { useSelector } from "react-redux";
import Image from "next/image";
import ServeLangItem from "../Helpers/ServeLangItem";
import { useRouter } from "next/navigation";
import appConfig from "@/appConfig";

const IMAGE_FALLBACK = "/assets/images/server-error.png";

export default function EmptyWishlistError() {
  const router = useRouter();
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const [emptyWis, setEmptyWis] = useState(null);
  useEffect(() => {
    if (!emptyWis) {
      if (websiteSetup) {
        setEmptyWis(
          websiteSetup.payload?.image_content?.empty_wishlist || IMAGE_FALLBACK
        );
      }
    }
  }, [emptyWis, websiteSetup]);
  return (
    <div className="wishlist-card-wrapper w-full">
      <div className="flex justify-center items-center w-full">
        <div>
          <div className="sm:mb-10 mb-0 transform sm:scale-100 scale-50">
            <div className="w-[429px] h-[412px] relative">
              <Image
                fill
                style={{ objectFit: "scale-down" }}
                src={
                  emptyWis === IMAGE_FALLBACK
                    ? IMAGE_FALLBACK
                    : appConfig.BASE_URL + emptyWis
                }
                alt="Bos favori listesi gorseli"
              />
            </div>
          </div>
          <div data-aos="fade-up" className="wishlist-content w-full">
            <h2 className="sm:text-xl text-base font-semibold text-center mb-5">
              {ServeLangItem()?.Empty_You_dont_Wishlist_any_Products}
            </h2>
            <div className="flex justify-center w-full cursor-pointer">
              <button
                type="button"
                onClick={() => router.back()}
                className="w-[180px] h-[50px] "
              >
                <span className="yellow-btn">
                  {ServeLangItem()?.Back_to_Shop}
                </span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
