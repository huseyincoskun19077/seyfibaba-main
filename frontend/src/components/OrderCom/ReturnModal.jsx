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
  setReturnModal,
}) {
  const reasonOptions = useMemo(
    () => [
      { value: "defective", label: "Arizali Urun" },
      { value: "wrong_item", label: "Yanlis Urun Geldi" },
      { value: "not_as_described", label: "Aciklamadaki Gibi Degil" },
      { value: "changed_mind", label: "Kararim Degisti" },
      { value: "damaged_in_shipping", label: "Kargoda Hasar Gordu" },
      { value: "other", label: "Diger" },
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
    
    // Add images
    images.forEach((img) => {
      dataToSend.append("images[]", img);
    });

    try {
      const res = await fetch(`${appConfig.BASE_URL}api/user/return-requests?token=${token}`, {
        method: "POST",
        headers: {
          Accept: "application/json",
          // Content-Type should NOT be set when using FormData with files
        },
        body: dataToSend,
      });

      const data = await res.json();

      if (res.ok) {
        toast.success(data.message);
        setReturnModal(false);
      } else {
        toast.error(data.message || "Talep gonderilemedi");
      }
    } catch (error) {
      toast.error("Bir hata olustu");
    } finally {
      setLoading(false);
    }
  };

  const handleImageChange = (e) => {
    const files = Array.from(e.target.files);
    setImages(files);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
      <div className="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl transform transition-all scale-100">
        <div className="bg-primary p-6 flex justify-between items-center text-white">
          <h3 className="text-xl font-bold">Iade Talebi Olustur</h3>
          <button onClick={() => setReturnModal(false)} className="hover:rotate-90 transition-transform">
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-8 space-y-5 overflow-y-auto max-h-[80vh]">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">İade Nedeni</label>
              <select
                required
                className="w-full bg-gray-50 border-0 rounded-2xl p-4 focus:ring-2 focus:ring-primary/20 transition-all text-sm"
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
              <label className="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">Adet</label>
              <select
                required
                className="w-full bg-gray-50 border-0 rounded-2xl p-4 focus:ring-2 focus:ring-primary/20 transition-all text-sm"
                value={formData.qty}
                onChange={(e) => setFormData({ ...formData, qty: e.target.value })}
              >
                {[...Array(maxQty || 1)].map((_, i) => (
                    <option key={i+1} value={i+1}>{i+1}</option>
                ))}
              </select>
            </div>
          </div>

          {Number(formData.qty) > 0 && (paidUnitPrice != null || unitPrice != null) && (
            <div className="rounded-2xl bg-gray-50 px-4 py-3 text-sm text-gray-700">
              <div className="flex justify-between gap-3">
                <span>Ürün tutarı</span>
                <span>
                  <CurrencyConvert price={Number(unitPrice || 0) * Number(formData.qty)} />
                </span>
              </div>
              {Number(unitPrice) > Number(paidUnitPrice) && (
                <div className="flex justify-between gap-3 text-gray-500">
                  <span>Kupon payı</span>
                  <span>
                    − <CurrencyConvert
                      price={
                        (Number(unitPrice || 0) - Number(paidUnitPrice || 0)) *
                        Number(formData.qty)
                      }
                    />
                  </span>
                </div>
              )}
              <div className="mt-1 flex justify-between gap-3 font-semibold">
                <span>Tahmini iade</span>
                <span>
                  <CurrencyConvert
                    price={Number(paidUnitPrice || unitPrice || 0) * Number(formData.qty)}
                  />
                </span>
              </div>
              <p className="mt-2 text-xs text-gray-500">
                Kupon siparişe orantılı dağılır. İade ettiğiniz ürüne düşen indirim payı
                düşülür; kalan ürünlerdeki indirim durur.
              </p>
            </div>
          )}

          <div>
            <label className="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">Ek Açıklama</label>
            <textarea
              className="w-full bg-gray-50 border-0 rounded-2xl p-4 focus:ring-2 focus:ring-primary/20 transition-all resize-none text-sm"
              rows="3"
              placeholder="Lütfen sorun hakkında detaylı bilgi verin..."
              value={formData.details}
              onChange={(e) => setFormData({ ...formData, details: e.target.value })}
            ></textarea>
          </div>

          <div>
            <label className="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 ml-1">Kanıt Fotoğrafları</label>
            <div className="mt-1 flex justify-center px-4 pt-4 pb-4 border-2 border-gray-100 border-dashed rounded-2xl hover:border-primary/30 transition-all cursor-pointer relative group">
                <div className="space-y-1 text-center">
                    <svg className="mx-auto h-8 w-8 text-gray-400 group-hover:text-primary transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                    <div className="flex text-xs text-gray-600">
                        <span className="relative cursor-pointer rounded-md font-bold text-primary hover:text-primary-dark">Dosya yükle</span>
                        <p className="pl-1">veya sürükle bırak</p>
                    </div>
                    <p className="text-[10px] text-gray-400">PNG, JPG, JPEG up to 10MB</p>
                </div>
                <input 
                    type="file" 
                    multiple 
                    className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    onChange={handleImageChange}
                />
            </div>
            {images.length > 0 && (
                <div className="mt-2 text-xs text-primary font-bold">
                    {images.length} files selected
                </div>
            )}
          </div>

          <div className="flex gap-4 pt-2">
            <button
              type="button"
              onClick={() => setReturnModal(false)}
              className="flex-1 py-4 px-6 rounded-2xl font-bold bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={loading}
              className="flex-1 py-4 px-6 rounded-2xl font-bold bg-primary text-white shadow-xl shadow-primary/20 hover:bg-primary-dark transition-all disabled:opacity-50"
            >
              {loading ? "Submitting..." : "Submit Request"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
