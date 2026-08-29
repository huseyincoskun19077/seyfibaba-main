"use client";
import { useSearchParams } from "next/navigation";
import React, { useState, useEffect, Suspense } from "react";
import { useRouter } from "next/navigation";
import auth from "../../utils/auth";
import settings from "../../utils/settings";
import BreadcrumbCom from "../BreadcrumbCom";
import ServeLangItem from "../Helpers/ServeLangItem";
import CurrencyConvert from "../Shared/CurrencyConvert";
import Link from "next/link";
import { LEGAL_ROUTES } from "@/config/legalDocuments";
import ReviewModal from "./ReviewModal";
import ReturnModal from "./ReturnModal";
import TrackDeliveryMan from "./TrackDeliveryMan";
import PrintBtn from "./PrintBtn";
import appConfig from "@/appConfig";

function OrderComContent({ resData, orderStatus, orderId }) {
  const webSettings = settings();
  const urlQuery = useSearchParams();
  const router = useRouter();
  const [returnableItems, setReturnableItems] = useState({});
  const [confirmingDeliveryItemId, setConfirmingDeliveryItemId] = useState(null);

  // review modal
  const [reviewModal, setReviewModal] = useState(false);
  const [productId, setProductId] = useState(null);
  const [reviewOrderProductId, setReviewOrderProductId] = useState(null);
  const reviewModalHandler = (pId, opId) => {
    setReviewModal(!reviewModal);
    setProductId(Number(pId));
    setReviewOrderProductId(opId ? Number(opId) : null);
  };

  // return modal
  const [returnModal, setReturnModal] = useState(false);
  const [orderProductId, setOrderProductId] = useState(null);
  const returnModalHandler = (id) => {
    setReturnModal(!returnModal);
    setOrderProductId(Number(id));
  };

  useEffect(() => {
    const loadReturnableItems = async () => {
      if (!auth() || !orderId) {
        setReturnableItems({});
        return;
      }

      try {
        const token = auth()?.access_token;
        const response = await fetch(
          `${appConfig.BASE_URL}api/user/orders/${orderId}/returnable-items?token=${token}`,
          {
            headers: {
              Accept: "application/json",
            },
          }
        );

        const data = await response.json();
        if (!response.ok) {
          setReturnableItems({});
          return;
        }

        const nextItems = (data?.items || []).reduce((accumulator, item) => {
          accumulator[item.order_product_id] = item;
          return accumulator;
        }, {});

        setReturnableItems(nextItems);
      } catch (error) {
        setReturnableItems({});
      }
    };

    loadReturnableItems();
  }, [orderId]);

  const handleConfirmDeliveryItem = async (opId) => {
    if (!auth()) return;
    setConfirmingDeliveryItemId(opId);
    try {
      const token = auth()?.access_token;
      const response = await fetch(
        `${appConfig.BASE_URL}api/user/order-products/${opId}/confirm-delivery?token=${token}`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
        }
      );
      const data = await response.json();
      if (response.ok) {
        alert(data?.message || "Ürün teslim onayınız alındı. Teşekkürler.");
        router.refresh();
      } else {
        alert(data.message || "Onaylama işlemi başarısız");
      }
    } catch (error) {
      console.error("Confirm delivery error:", error);
      alert("Bir hata oluştu");
    } finally {
      setConfirmingDeliveryItemId(null);
    }
  };

  const isPaymentSuccess = urlQuery.get("payment_status") === "success";

  return (
    <div className="w-full pt-[30px] pb-[60px]">
      <div className="order-tracking-wrapper w-full">
        <div className="container-x mx-auto">
          <BreadcrumbCom
            paths={[
              { name: ServeLangItem()?.home, path: "/" },
              { name: ServeLangItem()?.Order, path: `/order/${orderId}` },
            ]}
          />

          {isPaymentSuccess && (
            <div className="mb-6 rounded-2xl bg-green-50 border border-green-200 p-6 print:hidden">
              <div className="flex items-center gap-4">
                <div className="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                  <svg className="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <div>
                  <h2 className="text-xl font-bold text-green-800">
                    {ServeLangItem()?.Payment_Successful || "Ödemeniz Başarıyla Alındı!"}
                  </h2>
                  <p className="text-green-600 text-sm mt-1">
                    {ServeLangItem()?.Payment_Success_Message || "Siparişiniz onaylandı. Aşağıda sipariş detaylarınızı görebilirsiniz."}
                  </p>
                </div>
              </div>
            </div>
          )}

          <div className="w-full h-[168px]  bg-[#CBECFF] rounded-2xl mb-10 relative print:hidden">
            <div className="w-full px-10 flex justify-between pt-3 mb-7">
              <div>
                {resData?.order_delivered_date && (
                  <p className="text-base font-400">
                    {ServeLangItem()?.Delivered_on}{" "}
                    {resData?.order_delivered_date}
                  </p>
                )}
              </div>
              <div>
                {orderStatus === "Reddedildi" && (
                  <p className="text-base font-bold text-qred mr-10">
                    {ServeLangItem()?.Your_order_is_declined || "Siparişiniz reddedildi"}!
                  </p>
                )}
              </div>
            </div>
            <div className="flex lg:space-x-[373px] space-x-[90px] rtl:space-x-reverse w-full h-full justify-center">
              <div className="relative">
                <div className="w-[30px] h-[30px] border-[8px] rounded-full border-qyellow bg-white relative z-20"></div>
                <p className="absolute -left-4 top-10 sm:text-base text-sm font-400">
                  {ServeLangItem()?.Pending}
                </p>
              </div>
              {/*orderStatus*/}
              <div className="relative">
                <div
                  className={`w-[30px] h-[30px] border-[8px] rounded-full  bg-white relative z-20 ${
                    orderStatus === "Hazırlanıyor" ||
                    orderStatus === "Teslim Edildi" ||
                    orderStatus === "Tamamlandı"
                      ? "border-qyellow"
                      : "border-qgray"
                  }`}
                ></div>
                <div
                  className={`lg:w-[400px] w-[100px] h-[8px] absolute ltr:lg:-left-[390px] ltr:-left-[92px] rtl:lg:-right-[390px] rtl:-right-[92px] top-[10px] z-10  ${
                    orderStatus === "Hazırlanıyor" ||
                    orderStatus === "Teslim Edildi" ||
                    orderStatus === "Tamamlandı"
                      ? "primary-bg"
                      : "bg-white"
                  }`}
                ></div>
                <p className="absolute -left-4 top-10 sm:text-base text-sm font-400">
                  {ServeLangItem()?.Progress}
                </p>
              </div>
              <div className="relative">
                <div
                  className={`w-[30px] h-[30px] border-[8px] rounded-full bg-white  relative z-20 ${
                    orderStatus === "Teslim Edildi" || orderStatus === "Tamamlandı"
                      ? "border-qyellow"
                      : "border-qgray"
                  }`}
                ></div>
                <div
                  className={`lg:w-[400px] w-[100px] h-[8px] absolute ltr:lg:-left-[390px] ltr:-left-[92px] rtl:lg:-right-[390px] rtl:-right-[92px] top-[10px] z-10 ${
                    orderStatus === "Teslim Edildi" || orderStatus === "Tamamlandı"
                      ? "primary-bg"
                      : "bg-white"
                  }`}
                ></div>
                <p className="absolute -left-4 top-10 sm:text-base text-sm font-400">
                  {ServeLangItem()?.Delivered}
                </p>
              </div>
            </div>
          </div>
          <div className="bg-white lg:p-10 p-3 rounded-xl">
            <div id="printSection">
              <div className="sm:flex justify-between items-center mb-4">
                <div>
                  <h2 className="text-[26px] font-semibold text-qblack mb-2.5">
                    {resData?.order_address?.billing_name}
                  </h2>
                  <ul className="flex flex-col space-y-0.5">
                    <li className="text-[22px]n text-[#4F5562]">
                      {ServeLangItem()?.Order_ID}:{" "}
                      <span className="text-[#27AE60] notranslate">
                        {resData?.order_id}
                      </span>
                    </li>
                    <li className="text-[22px]n text-[#4F5562]">
                      {ServeLangItem()?.Billing_Address}:{" "}
                      <span className="text-[#27AE60] notranslate">{`${
                        resData?.order_address?.billing_address
                      },${
                        resData?.order_address?.billing_city
                      },${
                        resData?.order_address?.billing_state
                      }`}</span>
                    </li>
                    <li className="text-[22px]n text-[#4F5562]">
                      {ServeLangItem()?.Shipping_Address}:{" "}
                      <span className="text-[#27AE60] notranslate">{`${
                        resData?.order_address?.shipping_address
                      },${
                        resData?.order_address?.shipping_city
                      },${
                        resData?.order_address?.shipping_state
                      }`}</span>
                    </li>
                    <li className="text-[22px]n text-[#4F5562]">
                      {ServeLangItem()?.Type}:{" "}
                      <span className="text-[#27AE60] notranslate">
                        {resData?.order_address &&
                        parseInt(
                          resData?.order_address?.shipping_address_type
                        ) === 1
                          ? "Ofis"
                          : "Ev"}
                      </span>
                    </li>
                  </ul>
                </div>
                <div className="flex flex-col sm:flex-row sm:items-center gap-3">
                  <PrintBtn />
                  <div className="flex flex-wrap gap-2 print:hidden">
                    <Link
                      href={LEGAL_ROUTES.DISTANCE_SALES}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="h-10 inline-flex items-center rounded-md border border-gray-200 px-4 text-xs font-semibold text-qblack hover:bg-gray-50"
                    >
                      Mesafeli Satış Sözleşmesini Görüntüle
                    </Link>
                    <Link
                      href={LEGAL_ROUTES.PRE_INFORMATION}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="h-10 inline-flex items-center rounded-md border border-gray-200 px-4 text-xs font-semibold text-qblack hover:bg-gray-50"
                    >
                      Ön Bilgilendirme Formunu Görüntüle
                    </Link>
                  </div>
                </div>
              </div>
              <div className="relative w-full overflow-x-auto overflow-style-none border border-[#EDEDED] box-border mb-10">
                <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                  <tbody>
                    {/* table heading */}
                    <tr className="text-[13px] font-medium text-black bg-[#F6F6F6] whitespace-nowrap px-2 border-b default-border-bottom uppercase">
                      <td className=" py-4 ltr:pl-10 rtl:pr-10 block whitespace-nowrap rtl:text-right  w-[380px]">
                        {ServeLangItem()?.Product}
                      </td>
                      <td className="py-4 whitespace-nowrap  text-center">
                        {ServeLangItem()?.quantity}
                      </td>
                      <td className="py-4 whitespace-nowrap text-center">
                        {ServeLangItem()?.price}
                      </td>
                      <td className="py-4 whitespace-nowrap text-center capitalize">
                        {ServeLangItem()?.SUBTOTAL}
                      </td>
                      <td className="py-4 whitespace-nowrap text-center print:hidden">
                        {ServeLangItem()?.review}
                      </td>
                    </tr>
                    {/* table heading end */}
                    {resData?.order_products?.length > 0 &&
                      resData?.order_products?.map((item, i) => (
                        <tr
                          key={i}
                          className="bg-white border-b hover:bg-gray-50 last:border-none"
                        >
                          <td className="pl-10 w-[400px] py-4 ">
                            <div className="flex space-x-6 items-center">
                              <div className="flex-1 flex flex-col">
                                <p className="font-medium text-[15px] text-qblack rtl:text-right rtl:pr-10 notranslate">
                                  {item.product_name}
                                </p>
                                {item?.cargo?.tracking_number && (
                                  <div className="mt-1 text-xs text-gray-600 space-y-0.5">
                                    <div>
                                      <span className="font-semibold">Kargo:</span>{" "}
                                      {item?.cargo?.carrier_name || "-"}
                                    </div>
                                    <div>
                                      <span className="font-semibold">Takip No:</span>{" "}
                                      {item.cargo.tracking_number}
                                    </div>
                                    {item?.cargo?.tracking_url && (
                                      <a
                                        href={item.cargo.tracking_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-block text-blue-600 hover:underline"
                                      >
                                        Takip Linki
                                      </a>
                                    )}
                                  </div>
                                )}
                              </div>
                            </div>
                          </td>
                          <td className=" py-4">
                            <div className="flex justify-center items-center">
                              <div className="w-[54px] h-[40px] justify-center flex items-center border border-qgray-border">
                                <span>{item.qty}</span>
                              </div>
                            </div>
                          </td>
                          <td className="text-center py-4 px-2">
                            <div className="flex space-x-1 items-center justify-center">
                              <span className="text-[15px] font-normal">
                                <CurrencyConvert price={item.unit_price} />
                              </span>
                            </div>
                          </td>
                          <td className="text-center py-4 px-2">
                            <div className="flex space-x-1 items-center justify-center">
                              <span className="text-[15px] font-normal">
                                <CurrencyConvert
                                  price={item.unit_price * item.qty}
                                />
                              </span>
                            </div>
                          </td>
                          <td className="py-4 whitespace-nowrap text-center print:hidden space-x-2">
                        {auth() && 
                          (item?.customer_confirmed_at || item?.auto_confirmed_at || item?.delivered_at) &&
                          !item.user_has_reviewed && (
                          <button
                            onClick={() =>
                              reviewModalHandler(item.product_id, item.id)
                            }
                            type="button"
                            className="text-green-600 text-sm font-semibold border border-green-600 rounded px-3 py-1 hover:bg-green-600 hover:text-white transition-colors"
                          >
                            Yorum Yap
                          </button>
                        )}
                        {auth() &&
                          (item?.customer_confirmed_at || item?.auto_confirmed_at || item?.delivered_at) &&
                          returnableItems[item.id]?.is_returnable && (
                          <button
                            onClick={() =>
                              returnModalHandler(item.id)
                            }
                            type="button"
                            className="text-qred text-sm font-semibold border border-qred rounded px-3 py-1 hover:bg-qred hover:text-white transition-colors"
                          >
                            İade Et
                          </button>
                        )}
                        {auth() &&
                          item?.delivered_at &&
                          !item?.customer_confirmed_at &&
                          !item?.auto_confirmed_at && (
                            <button
                              onClick={() => handleConfirmDeliveryItem(item.id)}
                              disabled={confirmingDeliveryItemId === item.id}
                              type="button"
                              className="text-white text-sm font-semibold bg-green-500 rounded px-3 py-1 hover:bg-green-600 disabled:opacity-50 transition-colors"
                            >
                              {confirmingDeliveryItemId === item.id
                                ? "Onaylanıyor..."
                                : "Teslim Aldım"}
                            </button>
                          )}
                      </td>
                        </tr>
                      ))}
                  </tbody>
                </table>
              </div>

              <div className="flex sm:justify-end print:justify-end justify-center sm:mr-10">
                <div>
                  <div className="flex justify-between font-semibold w-[200px] mb-1">
                    <p className="text-sm text-qblack capitalize">
                      {ServeLangItem()?.SUBTOTAL}
                    </p>
                    <p className="text-sm text-qblack">
                      <CurrencyConvert
                        price={
                          parseFloat(resData?.total_amount) -
                          parseFloat(resData?.shipping_cost) +
                          parseFloat(resData?.coupon_coast)
                        }
                      />
                    </p>
                  </div>
                  <div className="flex justify-between font-semibold w-[200px]">
                    <p className="text-sm text-qred">
                      (-) {ServeLangItem()?.Discount_coupon}
                    </p>
                    <p className="text-sm text-qred">
                      -
                      <CurrencyConvert price={resData?.coupon_coast} />
                    </p>
                  </div>
                  <div className="flex justify-between font-semibold w-[200px]">
                    <p className="text-sm text-qblack">
                      (+) {ServeLangItem()?.Shipping_Cost}
                    </p>
                    <p className="text-sm text-qblack">
                      +<CurrencyConvert price={resData?.shipping_cost} />
                    </p>
                  </div>
                  <div className="w-full h-[1px] bg-qgray-border mt-4"></div>
                  <div className="flex justify-between font-semibold w-[200px] mt-4">
                    <p className="text-lg text-qblack">
                      {ServeLangItem()?.Total_Paid}
                    </p>
                    <p className="text-lg text-qblack">
                      <CurrencyConvert price={resData?.total_amount} />
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          {resData?.deliveryman &&
            resData?.latitude &&
            resData?.longitude &&
            Number(webSettings?.map_status) === 1 &&
            Number(resData?.order_status) === 2 && (
              <TrackDeliveryMan
                location={{
                  lat: Number(resData?.latitude),
                  lng: Number(resData?.longitude),
                }}
                deliverymanLocationPoint={{
                  lat: Number(resData?.deliveryman?.latitude),
                  lng: Number(resData?.deliveryman?.longitude),
                }}
                orderId={orderId}
              />
            )}
        </div>
      </div>
      {auth() && reviewModal && (
        <ReviewModal
          productId={productId}
          orderId={orderId}
          orderProductId={reviewOrderProductId}
          setReviewModal={setReviewModal}
        />
      )}
      {auth() && returnModal && (
        <ReturnModal 
          orderId={resData?.id} 
          orderProductId={orderProductId} 
          maxQty={returnableItems[orderProductId]?.max_returnable_qty}
          paidUnitPrice={returnableItems[orderProductId]?.paid_unit_price}
          unitPrice={returnableItems[orderProductId]?.unit_price}
          setReturnModal={setReturnModal} 
        />
      )}
    </div>
  );
}

function OrderCom({ resData, orderStatus, orderId }) {
  return (
    <Suspense
      fallback={
        <div className="w-full pt-[30px] pb-[60px] flex justify-center items-center min-h-[400px]">
          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900"></div>
        </div>
      }
    >
      <OrderComContent
        resData={resData}
        orderStatus={orderStatus}
        orderId={orderId}
      />
    </Suspense>
  );
}

export default OrderCom;
