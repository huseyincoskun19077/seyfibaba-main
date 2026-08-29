"use client";

import Image from "next/image";
import ServeLangItem from "../Helpers/ServeLangItem";
import CurrencyConvert from "../Shared/CurrencyConvert";
import { getProductImageProps } from "@/utils/productImage";
import auth from "@/utils/auth";

function ProductActions({
  item,
  returnableItems,
  confirmingDeliveryItemId,
  onReview,
  onReturn,
  onConfirmDelivery,
}) {
  const isLoggedIn = !!auth();
  const isShippedOrDelivered =
    item?.delivered_at ||
    item?.shipped_at ||
    (item?.seller_status != null && Number(item.seller_status) >= 2);
  const isDelivered =
    item?.customer_confirmed_at || item?.auto_confirmed_at || item?.delivered_at;
  const canConfirm =
    isLoggedIn &&
    isShippedOrDelivered &&
    !item?.customer_confirmed_at &&
    !item?.auto_confirmed_at;

  return (
    <div className="flex flex-wrap gap-2 print:hidden">
      {isLoggedIn && isDelivered && !item.user_has_reviewed && (
        <button
          onClick={() => onReview(item.product_id, item.id)}
          type="button"
          className="inline-flex h-9 items-center rounded-lg border border-emerald-600 px-3 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-600 hover:text-white"
        >
          Yorum Yap
        </button>
      )}
      {isLoggedIn && isDelivered && returnableItems[item.id]?.is_returnable && (
        <button
          onClick={() => onReturn(item.id)}
          type="button"
          className="inline-flex h-9 items-center rounded-lg border border-red-500 px-3 text-xs font-semibold text-red-600 transition hover:bg-red-500 hover:text-white"
        >
          İade Et
        </button>
      )}
      {canConfirm && (
        <button
          onClick={() => onConfirmDelivery(item.id)}
          disabled={confirmingDeliveryItemId === item.id}
          type="button"
          className="inline-flex h-9 items-center rounded-lg bg-emerald-500 px-3 text-xs font-semibold text-white transition hover:bg-emerald-600 disabled:opacity-50"
        >
          {confirmingDeliveryItemId === item.id ? "Onaylanıyor..." : "Teslim Aldım"}
        </button>
      )}
    </div>
  );
}

function CargoInfo({ cargo }) {
  if (!cargo?.tracking_number) return null;

  return (
    <div className="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
      <p>
        <span className="font-semibold text-slate-800">Kargo:</span>{" "}
        {cargo.carrier_name || "-"}
      </p>
      <p className="mt-0.5">
        <span className="font-semibold text-slate-800">Takip No:</span>{" "}
        <span className="notranslate">{cargo.tracking_number}</span>
      </p>
      {cargo.tracking_url && (
        <a
          href={cargo.tracking_url}
          target="_blank"
          rel="noreferrer"
          className="mt-1 inline-block font-semibold text-blue-600 hover:underline"
        >
          Kargoyu Takip Et
        </a>
      )}
    </div>
  );
}

export default function OrderProductList({
  products,
  returnableItems,
  confirmingDeliveryItemId,
  onReview,
  onReturn,
  onConfirmDelivery,
}) {
  if (!products?.length) {
    return (
      <div className="rounded-xl border border-dashed border-slate-200 py-10 text-center text-sm text-slate-500">
        Bu siparişte ürün bulunamadı.
      </div>
    );
  }

  return (
    <>
      {/* Mobil + tablet: kartlar */}
      <div className="space-y-3 md:hidden">
        {products.map((item) => {
          const imageProps = getProductImageProps(item.thumb_image);
          return (
            <article
              key={item.id}
              className="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm"
            >
              <div className="flex gap-3">
                <div className="relative h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                  {imageProps.src ? (
                    <Image
                      {...imageProps}
                      alt={item.product_name || "Ürün"}
                      fill
                      className="object-cover"
                      sizes="64px"
                    />
                  ) : (
                    <div className="flex h-full w-full items-center justify-center text-[10px] text-slate-400">
                      Görsel yok
                    </div>
                  )}
                </div>
                <div className="min-w-0 flex-1">
                  <h3 className="text-sm font-semibold leading-snug text-qblack notranslate">
                    {item.product_name}
                  </h3>
                  <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600">
                    <span>
                      {ServeLangItem()?.quantity}: <strong>{item.qty}</strong>
                    </span>
                    <span>
                      {ServeLangItem()?.price}:{" "}
                      <strong>
                        <CurrencyConvert price={item.unit_price} />
                      </strong>
                    </span>
                    <span className="font-semibold text-qblack">
                      <CurrencyConvert price={item.unit_price * item.qty} />
                    </span>
                  </div>
                </div>
              </div>
              <CargoInfo cargo={item.cargo} />
              <div className="mt-3 border-t border-slate-100 pt-3">
                <ProductActions
                  item={item}
                  returnableItems={returnableItems}
                  confirmingDeliveryItemId={confirmingDeliveryItemId}
                  onReview={onReview}
                  onReturn={onReturn}
                  onConfirmDelivery={onConfirmDelivery}
                />
              </div>
            </article>
          );
        })}
      </div>

      {/* Masaüstü: tablo */}
      <div className="hidden overflow-hidden rounded-2xl border border-slate-200/80 md:block">
        <table className="w-full text-sm">
          <thead>
            <tr className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
              <th className="px-5 py-4">{ServeLangItem()?.Product}</th>
              <th className="px-3 py-4 text-center">{ServeLangItem()?.quantity}</th>
              <th className="px-3 py-4 text-center">{ServeLangItem()?.price}</th>
              <th className="px-3 py-4 text-center">{ServeLangItem()?.SUBTOTAL}</th>
              <th className="px-5 py-4 text-center print:hidden">{ServeLangItem()?.review}</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {products.map((item) => {
              const imageProps = getProductImageProps(item.thumb_image);
              return (
                <tr key={item.id} className="bg-white hover:bg-slate-50/50">
                  <td className="px-5 py-4">
                    <div className="flex items-start gap-4">
                      <div className="relative h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                        {imageProps.src && (
                          <Image
                            {...imageProps}
                            alt={item.product_name || "Ürün"}
                            fill
                            className="object-cover"
                            sizes="56px"
                          />
                        )}
                      </div>
                      <div className="min-w-0">
                        <p className="font-medium text-qblack notranslate">{item.product_name}</p>
                        <CargoInfo cargo={item.cargo} />
                      </div>
                    </div>
                  </td>
                  <td className="px-3 py-4 text-center">
                    <span className="inline-flex min-w-[2.5rem] items-center justify-center rounded-lg border border-slate-200 px-2 py-1 font-medium">
                      {item.qty}
                    </span>
                  </td>
                  <td className="px-3 py-4 text-center">
                    <CurrencyConvert price={item.unit_price} />
                  </td>
                  <td className="px-3 py-4 text-center font-semibold">
                    <CurrencyConvert price={item.unit_price * item.qty} />
                  </td>
                  <td className="px-5 py-4 print:hidden">
                    <ProductActions
                      item={item}
                      returnableItems={returnableItems}
                      confirmingDeliveryItemId={confirmingDeliveryItemId}
                      onReview={onReview}
                      onReturn={onReturn}
                      onConfirmDelivery={onConfirmDelivery}
                    />
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </>
  );
}
