"use client";

import React, { useMemo, useState } from "react";
import { toast } from "react-toastify";
import appConfig from "@/appConfig";
import auth from "@/utils/auth";
import CurrencyConvert from "@/components/Shared/CurrencyConvert";

export default function ReturnModal({
  orderId,
  orderProductId,
  maxQty,
  paidUnitPrice,
  unitPrice,
  suggestedRefund,
  couponShare = 0,
  bankDiscountShare = 0,
  isBankPayment = false,
  setReturnModal,
  onSuccess,
}) {
  const reasonOptions = useMemo(
    () => [
      { value: "defective", label: "Arızalı ürün" },
      { value: "wrong_item", label: "Yanlış ürün geldi" },
      { value: "not_as_described", label: "Açıklamadaki gibi değil" },
      { value: "changed_mind", label: "Kararım değişti" },
      { value: "damaged_in_shipping", label: "Kargoda hasar gördü" },
      { value: "other", label: "Diğer" },
    ],
    []
  );

  const [formData, setFormData] = useState({
    reason: "",
    details: "",
    qty: maxQty || 1,
  });
  const [images, setImages] = useState([]);
  const [loading, setLoading] = useState(false);

  const hintQty = Math.max(1, Number(maxQty) || 1);
  const qty = Math.max(1, Number(formData.qty) || 1);
  const scale = qty / hintQty;
  const lineGross = Number(unitPrice || 0) * qty;
  const couponPart = Math.max(0, Number(couponShare || 0) * scale);
  const bankPart = Math.max(0, Number(bankDiscountShare || 0) * scale);
  const fromParts = Math.max(0, lineGross - couponPart - bankPart);
  const fromApi =
    Number(suggestedRefund || 0) > 0
      ? Number(suggestedRefund) * scale
      : Number(paidUnitPrice || unitPrice || 0) * qty;
  // API tavanı bozulmuşsa (ör. 8860) ürün-%3 ile uyumlu tutarı göster.
  const estimated =
    bankPart > 0.009 && fromApi + 0.05 < fromParts ? fromParts : fromApi;

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    const token = auth()?.access_token;

    const dataToSend = new FormData();
    dataToSend.append("order_id", orderId);
    dataToSend.append("order_product_id", orderProductId);
    dataToSend.append("reason", formData.reason);
    dataToSend.append("details", formData.details);
    dataToSend.append("qty", formData.qty);

    images.forEach((img) => {
      dataToSend.append("images[]", img);
    });

    try {
      const res = await fetch(
        `${appConfig.BASE_URL}api/user/return-requests?token=${token}`,
        {
          method: "POST",
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
          },
          body: dataToSend,
        }
      );

      let data = {};
      try {
        data = await res.json();
      } catch (_) {
        data = {};
      }

      if (res.ok) {
        toast.success(data.message || "İade talebi alındı");
        if (typeof onSuccess === "function") {
          onSuccess();
        } else {
          setReturnModal(false);
        }
      } else {
        const validationMsg = data?.errors
          ? Object.values(data.errors).flat().join(" ")
          : null;
        toast.error(
          validationMsg ||
            data.message ||
            "İade talebi gönderilemedi. Lütfen tekrar deneyin."
        );
      }
    } catch (error) {
      toast.error("Bir hata oluştu. Lütfen tekrar deneyin.");
    } finally {
      setLoading(false);
    }
  };

  const handleImageChange = (e) => {
    const files = Array.from(e.target.files || []);
    setImages(files);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
      <div className="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl transform transition-all scale-100">
        <div className="bg-qred p-6 flex justify-between items-center text-white">
          <h3 className="text-xl font-bold">İade Talebi Oluştur</h3>
          <button
            type="button"
            onClick={() => setReturnModal(false)}
            className="hover:rotate-90 transition-transform"
            aria-label="Kapat"
          >
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-8 space-y-5 overflow-y-auto max-h-[80vh]">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">
                İade Nedeni
              </label>
              <select
                required
                className="w-full bg-gray-50 border border-gray-200 rounded-2xl p-4 focus:ring-2 focus:ring-qred/20 transition-all text-sm text-qblack"
                value={formData.reason}
                onChange={(e) => setFormData({ ...formData, reason: e.target.value })}
              >
                <option value="">Bir neden seçin</option>
                {reasonOptions.map((reason) => (
                  <option key={reason.value} value={reason.value}>
                    {reason.label}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">
                Adet
              </label>
              <select
                required
                className="w-full bg-gray-50 border border-gray-200 rounded-2xl p-4 focus:ring-2 focus:ring-qred/20 transition-all text-sm text-qblack"
                value={formData.qty}
                onChange={(e) => setFormData({ ...formData, qty: e.target.value })}
              >
                {[...Array(maxQty || 1)].map((_, i) => (
                  <option key={i + 1} value={i + 1}>
                    {i + 1}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {Number(formData.qty) > 0 && (paidUnitPrice != null || unitPrice != null) && (
            <div className="rounded-2xl bg-gray-50 px-4 py-3 text-sm text-gray-700">
              <div className="flex justify-between gap-3">
                <span>Ürün tutarı</span>
                <span>
                  <CurrencyConvert price={lineGross} />
                </span>
              </div>
              {couponPart > 0.009 && (
                <div className="flex justify-between gap-3 text-gray-500">
                  <span>Kupon payı</span>
                  <span>
                    − <CurrencyConvert price={couponPart} />
                  </span>
                </div>
              )}
              {(bankPart > 0.009 || (isBankPayment && lineGross - estimated > 0.009)) && (
                <div className="flex justify-between gap-3 text-gray-500">
                  <span>Havale indirimi (~%3)</span>
                  <span>
                    −{" "}
                    <CurrencyConvert
                      price={
                        bankPart > 0.009
                          ? bankPart
                          : Math.max(0, lineGross - couponPart - estimated)
                      }
                    />
                  </span>
                </div>
              )}
              <div className="mt-1 flex justify-between gap-3 font-semibold">
                <span>Tahmini iade</span>
                <span>
                  <CurrencyConvert price={estimated} />
                </span>
              </div>
              <p className="mt-2 text-xs text-gray-500">
                {couponPart > 0.009
                  ? "Kupon siparişe orantılı dağılır; iade ettiğiniz ürüne düşen pay düşülür. "
                  : ""}
                {isBankPayment || bankPart > 0.009
                  ? "Havale/EFT siparişlerinde alışverişteki ~%3 indirim iade tutarından düşülür; ödediğiniz kadar iade edilir."
                  : "İndirim yoksa ürün tutarı kadar iade edilir."}
              </p>
            </div>
          )}

          <div>
            <label className="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">
              Ek Açıklama
            </label>
            <textarea
              className="w-full bg-gray-50 border border-gray-200 rounded-2xl p-4 focus:ring-2 focus:ring-qred/20 transition-all resize-none text-sm text-qblack"
              rows="3"
              placeholder="Lütfen sorun hakkında detaylı bilgi verin..."
              value={formData.details}
              onChange={(e) => setFormData({ ...formData, details: e.target.value })}
            />
          </div>

          <div>
            <label className="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">
              Kanıt Fotoğrafları
            </label>
            <div className="mt-1 flex justify-center px-4 pt-4 pb-4 border-2 border-gray-100 border-dashed rounded-2xl hover:border-qred/40 transition-all cursor-pointer relative group">
              <div className="space-y-1 text-center">
                <svg
                  className="mx-auto h-8 w-8 text-gray-400 group-hover:text-qred transition-colors"
                  stroke="currentColor"
                  fill="none"
                  viewBox="0 0 48 48"
                >
                  <path
                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  />
                </svg>
                <div className="flex text-xs text-gray-600 justify-center">
                  <span className="relative cursor-pointer rounded-md font-bold text-qred">
                    Dosya yükle
                  </span>
                  <p className="pl-1">veya sürükle bırak</p>
                </div>
                <p className="text-[10px] text-gray-400">PNG, JPG, JPEG (max 2MB)</p>
              </div>
              <input
                type="file"
                multiple
                accept="image/*"
                className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                onChange={handleImageChange}
              />
            </div>
            {images.length > 0 && (
              <div className="mt-2 text-xs text-qred font-bold">
                {images.length} dosya seçildi
              </div>
            )}
          </div>

          <div className="flex gap-4 pt-2">
            <button
              type="button"
              onClick={() => setReturnModal(false)}
              className="flex-1 py-4 px-6 rounded-2xl font-bold bg-gray-100 text-gray-700 hover:bg-gray-200 transition-all"
            >
              Vazgeç
            </button>
            <button
              type="submit"
              disabled={loading}
              className="flex-1 py-4 px-6 rounded-2xl font-bold bg-qred text-white shadow-lg shadow-red-200 hover:opacity-90 transition-all disabled:opacity-50"
            >
              {loading ? "Gönderiliyor..." : "Talebi Oluştur"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
