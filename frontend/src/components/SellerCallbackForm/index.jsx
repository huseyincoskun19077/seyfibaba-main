"use client";

import { useState } from "react";
import { toast } from "react-toastify";
import { usePublicSellerCallbackMutation } from "@/redux/features/sellerRegister/apiSlice";

export default function SellerCallbackForm({ compact = false }) {
  const [shopName, setShopName] = useState("");
  const [phone, setPhone] = useState("");
  const [done, setDone] = useState(false);
  const [send, { isLoading }] = usePublicSellerCallbackMutation();

  const submit = async (event) => {
    event.preventDefault();
    const digits = phone.replace(/\D/g, "");
    if (digits.slice(-10).length < 10) {
      toast.error("Geçerli bir telefon numarası girin.");
      return;
    }
    try {
      const response = await send({
        shop_name: shopName.trim(),
        phone: phone.trim(),
      }).unwrap();
      setDone(true);
      toast.success(response?.message || "Bilgileriniz alındı. Sizi arayacağız.");
    } catch (error) {
      toast.error(error?.data?.message || "Gönderilemedi. Telefonu kontrol edin.");
    }
  };

  if (done) {
    return (
      <p className="rounded-xl bg-white px-4 py-3 text-sm font-600 text-[#04334a]">
        Aldık. Firma adınız ve telefonunuz kayıtlı. Sizi arayacağız.
      </p>
    );
  }

  return (
    <form onSubmit={submit} className={compact ? "space-y-3 text-left" : "space-y-3 text-left"}>
      <div className={compact ? "grid gap-3 sm:grid-cols-2" : "grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end"}>
        <label className="block">
          <span className="mb-1 block text-xs font-700 text-[#04334a]">Firma adı</span>
          <input
            required
            value={shopName}
            onChange={(event) => setShopName(event.target.value)}
            className="h-12 w-full rounded-lg border border-[#04334a]/15 bg-white px-3 text-sm text-[#04334a] outline-none focus:border-qyellow"
            placeholder="Mağaza veya firma"
          />
        </label>
        <label className="block">
          <span className="mb-1 block text-xs font-700 text-[#04334a]">Telefon</span>
          <input
            required
            type="tel"
            value={phone}
            onChange={(event) => setPhone(event.target.value)}
            className="h-12 w-full rounded-lg border border-[#04334a]/15 bg-white px-3 text-sm text-[#04334a] outline-none focus:border-qyellow"
            placeholder="05xx xxx xx xx"
          />
        </label>
        <button
          type="submit"
          disabled={isLoading}
          className="inline-flex h-12 items-center justify-center rounded-lg bg-[#04334a] px-5 text-sm font-800 text-white hover:bg-[#032736] disabled:opacity-70"
        >
          {isLoading ? "Gönderiliyor" : "Sizi arayalım"}
        </button>
      </div>
    </form>
  );
}
