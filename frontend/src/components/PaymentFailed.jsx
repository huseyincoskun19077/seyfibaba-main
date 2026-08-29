"use client";

import React from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import ServeLangItem from "./Helpers/ServeLangItem";

export default function PaymentFailed() {
  const searchParams = useSearchParams();
  const orderId = searchParams.get("order_id");
  const errorCode = searchParams.get("code");
  const reason = searchParams.get("reason");

  const reasonMap = {
    missing_callback_params:
      "Ödeme sağlayıcısından eksik yanıt alındı. Lütfen tekrar deneyin.",
    order_not_found:
      "Bu ödeme girişimi bir siparişle eşleştirilemedi. Lütfen yeniden sipariş verin.",
    payment_declined:
      "Bankanız veya ödeme sağlayıcınız bu işlemi reddetti.",
    payment_processing_error:
      "Ödeme işlemi geçici bir hata nedeniyle tamamlanamadı.",
  };

  const detailMessage =
    reasonMap[reason] ||
    ServeLangItem()?.Oh_snap_The_Payment_Information_was_declined ||
    "İşleminizi gerçekleştiremedik. Kart bilgilerinizi veya bakiyenizi kontrol edin.";

  const retryHref = orderId ? `/checkout?order_id=${orderId}` : "/checkout";

  return (
    <div className="min-h-[70vh] flex items-center justify-center py-20 px-4 bg-gray-50">
      <div className="max-w-xl w-full text-center animate-in fade-in zoom-in-95 duration-700">
        <div className="bg-white rounded-[40px] p-10 md:p-16 shadow-2xl shadow-red-200/50 border border-red-50 relative overflow-hidden">
          {/* Decorative Background Elements */}
          <div className="absolute top-0 right-0 w-32 h-32 bg-red-50 rounded-full -mr-16 -mt-16 blur-2xl" />
          <div className="absolute bottom-0 left-0 w-32 h-32 bg-red-50 rounded-full -ml-16 -mb-16 blur-2xl" />
          
          {/* Icon Container */}
          <div className="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-10 relative">
            <div className="absolute inset-0 bg-red-100 rounded-full animate-ping opacity-25" />
            <svg className="w-12 h-12 text-red-600 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>

          <h2 className="text-4xl font-extrabold text-gray-900 mb-4 tracking-tight">
            {ServeLangItem()?.Payment_Declined || "Ödeme Başarısız"}
          </h2>
          
          <p className="text-gray-500 text-lg mb-10 leading-relaxed max-w-sm mx-auto">
            {detailMessage}
          </p>

          {(orderId || errorCode) && (
            <div className="mb-8 rounded-2xl border border-gray-200 bg-gray-50 px-5 py-4 text-left text-sm text-gray-600">
              {orderId && (
                <p>
                  <span className="font-semibold text-gray-900">Sipariş No:</span>{" "}
                  {orderId}
                </p>
              )}
              {errorCode && (
                <p>
                  <span className="font-semibold text-gray-900">Hata Kodu:</span>{" "}
                  {errorCode}
                </p>
              )}
            </div>
          )}

          {/* Detailed Error Tips Section */}
          <div className="bg-red-50/50 rounded-2xl p-6 text-left mb-10 border border-red-100">
            <h4 className="text-red-800 font-bold mb-3 text-sm flex items-center gap-2">
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.268 15c-.77 1.333.192 3 1.732 3z" />
              </svg>
              Kontrol edilmesi gerekenler:
            </h4>
            <ul className="space-y-2 text-sm text-red-700 font-medium">
              <li className="flex items-center gap-2">
                <div className="w-1 h-1 bg-red-400 rounded-full" />
                Yanlış CVV veya son kullanma tarihi
              </li>
              <li className="flex items-center gap-2">
                <div className="w-1 h-1 bg-red-400 rounded-full" />
                3D Secure doğrulaması başarısız veya süresi dolmuş
              </li>
              <li className="flex items-center gap-2">
                <div className="w-1 h-1 bg-red-400 rounded-full" />
                Kart internet alışverişine kapalı
              </li>
            </ul>
          </div>

          <div className="flex flex-col sm:flex-row gap-4">
            <Link 
              href={retryHref}
              className="flex-1 bg-primary text-white py-4 px-8 rounded-2xl font-bold hover:bg-primary-dark transition-all shadow-xl shadow-red-200 hover:shadow-red-300 transform active:scale-95"
            >
              Tekrar Dene
            </Link>
            <Link
              href="/contact"
              className="flex-1 bg-gray-100 text-gray-700 py-4 px-8 rounded-2xl font-bold hover:bg-gray-200 transition-all transform active:scale-95"
            >
              Destek
            </Link>
          </div>
        </div>

        <p className="mt-8 text-gray-400 text-sm">
          Yardıma mı ihtiyacınız var? <Link href="/contact" className="text-primary font-bold hover:underline">Destek ekibimizle iletişime geçin</Link>
        </p>
      </div>
    </div>
  );
}
