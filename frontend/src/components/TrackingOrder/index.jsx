"use client";

import { useEffect, useState } from "react";
import { toast } from "react-toastify";
import { useRouter } from "next/navigation";
import InputCom from "../Helpers/InputCom";
import PageTitle from "../Helpers/PageTitle";
import Thumbnail from "./Thumbnail";
import ServeLangItem from "../Helpers/ServeLangItem";
import { useLazyTrackOrderApiQuery } from "@/redux/features/order/apiSlice";
import auth from "../../utils/auth";
import LoaderStyleOne from "../Helpers/Loaders/LoaderStyleOne";

function TrackingOrder() {
  const router = useRouter();
  const [authReady, setAuthReady] = useState(false);
  const [form, setForm] = useState({ orderNumber: "" });
  const [trackOrderApi, { isLoading: isTrackOrderLoading }] =
    useLazyTrackOrderApiQuery();

  useEffect(() => {
    if (!auth()) {
      toast.info("Sipariş takibi için giriş yapmalısınız.");
      router.replace("/login?redirect=/tracking-order");
      return;
    }
    setAuthReady(true);
  }, [router]);

  const trackOrderSuccessHandler = (data, statusCode) => {
    if ((statusCode === 200 || statusCode === 201) && data?.order?.order_id) {
      router.push(`/order/${data.order.order_id}`);
      return;
    }

    toast.error(data?.message || "Siparis bulunamadi");
  };

  const trackOrderErrorHandler = (error) => {
    toast.error(error?.data?.message || "Siparis bulunamadi");
  };

  const trackOrder = () => {
    const userToken = auth()?.access_token;
    const normalizedOrderNumber = form.orderNumber?.trim();

    if (normalizedOrderNumber) {
      trackOrderApi({
        data: normalizedOrderNumber,
        token: userToken,
        success: trackOrderSuccessHandler,
        error: trackOrderErrorHandler,
      });
    } else {
      toast.error("Siparis numarasi zorunludur");
    }
  };

  if (!authReady) {
    return (
      <div className="tracking-page-wrapper w-full min-h-[40vh] flex items-center justify-center">
        <LoaderStyleOne />
      </div>
    );
  }

  return (
    <div className="tracking-page-wrapper w-full">
      {/* Page Title Section */}
      <div className="page-title mb-[40px]">
        <PageTitle
          title="Siparis Takibi"
          breadcrumb={[
            { name: ServeLangItem()?.home, path: "/" },
            { name: ServeLangItem()?.Track_Order, path: "/tracking-order" },
          ]}
        />
      </div>

      {/* Main Content Section */}
      <div className="content-wrapper w-full mb-[40px]">
        <div className="container-x mx-auto">
          {/* Heading and Description */}
          <h2 className="text-[22px] text-qblack font-semibold leading-9">
            {ServeLangItem()?.Track_Your_Order}
          </h2>
          <p className="text-[15px] text-qgraytwo leading-8 mb-5">
            Siparişinizin güncel durumunu görmek için sipariş numaranızı girin.
            (Yalnızca giriş yapmış hesaplar)
          </p>

          {/* Form and Thumbnail Section */}
          <div className="w-full bg-white lg:px-[30px] px-5 py-[23px] lg:flex items-center">
            {/* Form Inputs */}
            <div className="lg:w-[642px] w-full">
              {/* Order Number Input */}
              <div className="mb-3">
                <InputCom
                  value={form.orderNumber}
                  inputHandler={(e) =>
                    setForm((prev) => ({
                      ...prev,
                      orderNumber: e.target.value,
                    }))
                  }
                  placeholder="Siparis numarasi"
                  label="Siparis Takip Numarasi*"
                  inputClasses="w-full h-[50px]"
                />
              </div>
              {/* Track Order Button */}
              <button
                onClick={trackOrder}
                type="button"
                disabled={isTrackOrderLoading}
              >
                <div className="w-[142px] h-[50px] black-btn flex justify-center items-center disabled:cursor-not-allowed">
                  {isTrackOrderLoading ? (
                    <span style={{ transform: "scale(0.3)" }}>
                      <LoaderStyleOne />
                    </span>
                  ) : (
                    <span>{ServeLangItem()?.Track_Now}</span>
                  )}
                </div>
              </button>
            </div>

            {/* Thumbnail Illustration */}
            <div className="flex-1 flex justify-center mt-5 lg:mt-0">
              <Thumbnail />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default TrackingOrder;
