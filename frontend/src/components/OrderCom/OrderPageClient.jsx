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
      <div className="flex min-h-[50vh] w-full items-center justify-center bg-slate-50/60 px-4 py-16">
        <div className="flex flex-col items-center gap-3">
          <div className="h-10 w-10 animate-spin rounded-full border-2 border-slate-200 border-t-qblack" />
          <p className="text-sm text-slate-500">Oturum kontrol ediliyor…</p>
        </div>
      </div>
    );
  }

  if (isFetching) {
    return (
      <div className="flex min-h-[50vh] w-full items-center justify-center bg-slate-50/60 px-4 py-16">
        <div className="flex flex-col items-center gap-3">
          <div className="h-10 w-10 animate-spin rounded-full border-2 border-slate-200 border-t-qblack" />
          <p className="text-sm text-slate-500">Sipariş yükleniyor…</p>
        </div>
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
      <div className="w-full bg-slate-50/60 px-4 py-12 sm:py-16">
        <div className="container-x mx-auto max-w-lg">
          <div className="rounded-2xl border border-red-200 bg-white p-6 text-center shadow-sm">
            <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600">
              !
            </div>
            <p className="text-sm font-medium text-red-700 sm:text-base">{message}</p>
            <button
              type="button"
              onClick={() => refetch()}
              className="mt-5 inline-flex h-11 w-full items-center justify-center rounded-xl bg-qyellow px-5 text-sm font-semibold text-qblack sm:w-auto"
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
