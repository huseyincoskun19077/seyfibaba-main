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
import OrderStatusStepper from "./OrderStatusStepper";
import OrderProductList from "./OrderProductList";
import { getOrderStatusBadgeClass } from "@/utils/orderStatus";
import appConfig from "@/appConfig";

function OrderComContent({ resData, orderStatus, orderId }) {
  const webSettings = settings();
  const urlQuery = useSearchParams();
  const router = useRouter();
  const [returnableItems, setReturnableItems] = useState({});
  const [confirmingDeliveryItemId, setConfirmingDeliveryItemId] = useState(null);

  const [reviewModal, setReviewModal] = useState(false);
  const [productId, setProductId] = useState(null);
  const [reviewOrderProductId, setReviewOrderProductId] = useState(null);
  const reviewModalHandler = (pId, opId) => {
    setReviewModal(!reviewModal);
    setProductId(Number(pId));
    setReviewOrderProductId(opId ? Number(opId) : null);
  };

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
  const address = resData?.order_address;
  const shippingType =
    address && parseInt(address.shipping_address_type, 10) === 1 ? "Ofis" : "Ev";

  return (
    <div className="w-full bg-slate-50/60 pb-16 pt-6 sm:pt-8">
      <div className="order-tracking-wrapper w-full">
        <div className="container-x mx-auto px-3 sm:px-4">
          <BreadcrumbCom
            paths={[
              { name: ServeLangItem()?.home, path: "/" },
              { name: ServeLangItem()?.Order, path: `/order/${orderId}` },
            ]}
          />

          {isPaymentSuccess && (
            <div className="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 sm:p-6 print:hidden">
              <div className="flex items-start gap-4">
                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-100">
                  <svg className="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <div>
                  <h2 className="text-lg font-bold text-emerald-900 sm:text-xl">
                    {ServeLangItem()?.Payment_Successful || "Ödemeniz Başarıyla Alındı!"}
                  </h2>
                  <p className="mt-1 text-sm text-emerald-700">
                    {ServeLangItem()?.Payment_Success_Message ||
                      "Siparişiniz onaylandı. Aşağıda sipariş detaylarınızı görebilirsiniz."}
                  </p>
                </div>
              </div>
            </div>
          )}

          <div className="mb-6 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-6">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
              <div className="min-w-0">
                <div className="mb-2 flex flex-wrap items-center gap-2">
                  <span
                    className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getOrderStatusBadgeClass(orderStatus)}`}
                  >
                    {orderStatus}
                  </span>
                  {resData?.order_delivered_date && (
                    <span className="text-xs text-slate-500">
                      {ServeLangItem()?.Delivered_on} {resData.order_delivered_date}
                    </span>
                  )}
                </div>
                <h1 className="text-xl font-bold text-qblack sm:text-2xl">
                  {address?.billing_name || "Sipariş Detayı"}
                </h1>
                <p className="mt-1 text-sm text-slate-600">
                  {ServeLangItem()?.Order_ID}:{" "}
                  <span className="font-semibold text-emerald-600 notranslate">#{resData?.order_id}</span>
                </p>
              </div>
              <div className="flex shrink-0 flex-col gap-2 sm:items-end print:hidden">
                <PrintBtn />
                <div className="flex flex-wrap gap-2">
                  <Link
                    href={LEGAL_ROUTES.DISTANCE_SALES}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50 sm:text-xs"
                  >
                    Mesafeli Satış Sözleşmesi
                  </Link>
                  <Link
                    href={LEGAL_ROUTES.PRE_INFORMATION}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50 sm:text-xs"
                  >
                    Ön Bilgilendirme
                  </Link>
                </div>
              </div>
            </div>
          </div>

          <div className="mb-6">
            <OrderStatusStepper orderStatus={orderStatus} />
          </div>

          <div id="printSection" className="space-y-6">
            <div className="grid gap-4 md:grid-cols-2">
              <div className="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-5">
                <h2 className="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">
                  {ServeLangItem()?.Billing_Address}
                </h2>
                <p className="text-sm leading-relaxed text-slate-700 notranslate">
                  {address?.billing_address}, {address?.billing_city}, {address?.billing_state}
                </p>
              </div>
              <div className="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-5">
                <h2 className="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">
                  {ServeLangItem()?.Shipping_Address}
                </h2>
                <p className="text-sm leading-relaxed text-slate-700 notranslate">
                  {address?.shipping_address}, {address?.shipping_city}, {address?.shipping_state}
                </p>
                <p className="mt-2 text-xs text-slate-500">
                  {ServeLangItem()?.Type}: <span className="font-medium text-slate-700">{shippingType}</span>
                </p>
              </div>
            </div>

            <div className="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-6">
              <h2 className="mb-4 text-lg font-bold text-qblack">Sipariş Ürünleri</h2>
              <OrderProductList
                products={resData?.order_products}
                returnableItems={returnableItems}
                confirmingDeliveryItemId={confirmingDeliveryItemId}
                onReview={reviewModalHandler}
                onReturn={returnModalHandler}
                onConfirmDelivery={handleConfirmDeliveryItem}
              />
            </div>

            <div className="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:ml-auto sm:max-w-sm sm:p-6">
              <h2 className="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Özet</h2>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-slate-600">{ServeLangItem()?.SUBTOTAL}</span>
                  <span className="font-medium text-qblack">
                    <CurrencyConvert
                      price={
                        parseFloat(resData?.total_amount) -
                        parseFloat(resData?.shipping_cost) +
                        parseFloat(resData?.coupon_coast)
                      }
                    />
                  </span>
                </div>
                <div className="flex justify-between text-red-600">
                  <span>(-) {ServeLangItem()?.Discount_coupon}</span>
                  <span>
                    -<CurrencyConvert price={resData?.coupon_coast} />
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-600">(+) {ServeLangItem()?.Shipping_Cost}</span>
                  <span className="font-medium text-qblack">
                    +<CurrencyConvert price={resData?.shipping_cost} />
                  </span>
                </div>
                <div className="border-t border-slate-200 pt-3">
                  <div className="flex justify-between text-base font-bold text-qblack">
                    <span>{ServeLangItem()?.Total_Paid}</span>
                    <span>
                      <CurrencyConvert price={resData?.total_amount} />
                    </span>
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
              <div className="mt-6">
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
              </div>
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
        <div className="flex min-h-[400px] w-full items-center justify-center py-16">
          <div className="h-12 w-12 animate-spin rounded-full border-b-2 border-t-2 border-gray-900" />
        </div>
      }
    >
      <OrderComContent resData={resData} orderStatus={orderStatus} orderId={orderId} />
    </Suspense>
  );
}

export default OrderCom;
