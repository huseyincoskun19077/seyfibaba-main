"use client";

import { Suspense, useEffect } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { useDispatch } from "react-redux";
import { useOrderDetailApiQuery } from "@/redux/features/auth/apiSlice";
import clearCartAfterOrder from "@/utils/clearCartAfterOrder";
import { getOrderStatus } from "@/utils/orderStatus";
import auth from "@/utils/auth";
import OrderCom from "./index";

function OrderPageContent({ orderId }) {
  const dispatch = useDispatch();
  const searchParams = useSearchParams();
  const router = useRouter();
  const token = auth()?.access_token;

  const { data, error, isFetching, isError, refetch } = useOrderDetailApiQuery(
    { token, orderId },
    {
      skip: !token,
      refetchOnMountOrArgChange: true,
    }
  );

  useEffect(() => {
    if (!auth()) {
      const next = `/order/${orderId}${window.location.search || ""}`;
      router.push(`/login?next=${encodeURIComponent(next)}`);
    }
  }, [orderId, router]);

  useEffect(() => {
    if (searchParams.get("payment_status") === "success") {
      clearCartAfterOrder(dispatch, orderId);
    }
  }, [dispatch, orderId, searchParams]);

  if (!token) {
    return (
      <div className="w-full pt-[30px] pb-[60px] flex justify-center items-center min-h-[400px]">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900" />
      </div>
    );
  }

  if (isFetching) {
    return (
      <div className="w-full pt-[30px] pb-[60px] flex justify-center items-center min-h-[400px]">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900" />
      </div>
    );
  }

  if (isError || !data?.order) {
    const status = error?.status;
    const message =
      status === 401
        ? "Oturum süreniz dolmuş. Lütfen çıkış yapıp tekrar giriş yapın."
        : status === 404
          ? "Sipariş bulunamadı."
          : `Sipariş detayları yüklenemedi${status ? ` (HTTP ${status})` : ""}.`;

    return (
      <div className="w-full pt-[30px] pb-[60px]">
        <div className="container-x mx-auto">
          <div className="rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-red-700 space-y-4">
            <p>{message}</p>
            <button
              type="button"
              onClick={() => refetch()}
              className="inline-flex h-10 items-center rounded-md bg-qyellow px-4 text-sm font-semibold text-qblack"
            >
              Tekrar Dene
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <OrderCom
      resData={data.order}
      orderStatus={getOrderStatus(data.order)}
      orderId={orderId}
    />
  );
}

export default function OrderPageClient({ orderId }) {
  return (
    <Suspense
      fallback={
        <div className="w-full pt-[30px] pb-[60px] flex justify-center items-center min-h-[400px]">
          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900" />
        </div>
      }
    >
      <OrderPageContent orderId={orderId} />
    </Suspense>
  );
}
