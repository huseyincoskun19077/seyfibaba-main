"use client";

import { useState } from "react";
import Link from "next/link";
import { toast } from "react-toastify";
import auth from "@/utils/auth";
import { useSecondHandSendToListingMutation, useSecondHandVerificationQuery } from "@/redux/features/secondHand/apiSlice";
import { marketplaceProfileUrl, marketplaceUrl } from "@/utils/secondHandSite";
import { marketplaceLoginHref } from "@/utils/auth";

/**
 * @param {{ listingId: number|string; sellerUserId?: number|string }} props
 */
export default function SecondHandMessageToSeller({ listingId, sellerUserId }) {
  const [body, setBody] = useState("");
  const [sendToListing, { isLoading }] = useSecondHandSendToListingMutation();

  const session = auth();
  const tokenReady = !!session?.access_token;
  const { data: verData } = useSecondHandVerificationQuery(undefined, { skip: !tokenReady });
  const isSecondHandApproved = verData?.verification?.status === "approved";
  const myId = session?.user?.id != null ? Number(session.user.id) : null;
  const sellerId = sellerUserId != null ? Number(sellerUserId) : null;
  const isOwn = myId != null && sellerId != null && myId === sellerId;

  if (!session) {
    return (
      <div className="mt-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm ring-1 ring-black/[0.03]">
        <div className="flex items-start gap-3 bg-amber-50/70 px-4 py-4">
          <span className="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-qyellow text-qblack ring-1 ring-amber-900/10">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden>
              <path d="M12 3v10" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
              <path d="M7 11l5 5 5-5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
              <path d="M5 21h14" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
            </svg>
          </span>
          <div className="min-w-0">
            <p className="text-sm font-700 text-qblack">Mesaj/teklif için giriş yapın</p>
          </div>
        </div>
        <div className="px-4 py-4 flex flex-wrap items-center gap-3">
          <Link
            href={marketplaceUrl(marketplaceLoginHref())}
            className="h-10 inline-flex items-center justify-center rounded-xl bg-qblack px-5 text-sm font-700 text-white shadow-sm hover:bg-qblack/90 transition"
          >
            Giriş yap
          </Link>
          <span className="text-xs text-qgray">
            Hesabın yok mu?{" "}
            <Link href={marketplaceUrl("/signup")} className="text-qblack font-700 underline">
              Kayıt ol
            </Link>
          </span>
        </div>
      </div>
    );
  }

  if (isOwn) {
    return (
      <div className="mt-8 p-4 bg-gray-50 rounded-lg text-sm text-qgray">
        Kendi ilanınıza mesaj gönderemezsiniz.
      </div>
    );
  }

  if (!isSecondHandApproved) {
    return (
      <div className="mt-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm ring-1 ring-black/[0.03]">
        <div className="flex items-start gap-3 bg-blue-50/70 px-4 py-4">
          <span className="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white ring-1 ring-blue-900/10">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden>
              <path d="M12 2a10 10 0 1010 10A10 10 0 0012 2z" stroke="currentColor" strokeWidth="0" />
              <path d="M12 7v5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
              <path d="M12 16h.01" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
            </svg>
          </span>
          <div className="min-w-0">
            <p className="text-sm font-700 text-qblack">Mesaj/teklif için doğrulama gerekli</p>
            <p className="mt-1 text-xs text-qgray">İkinci el doğrulamanız onaylı olmalı.</p>
          </div>
        </div>
        <div className="px-4 py-4 flex flex-wrap items-center gap-3">
          <Link
            href={marketplaceProfileUrl("second-hand-verification")}
            className="h-10 inline-flex items-center justify-center rounded-xl bg-qyellow px-5 text-sm font-800 text-qblack shadow-sm ring-1 ring-amber-900/10 hover:brightness-95 transition"
          >
            Doğrulamaya git
          </Link>
          <Link href={marketplaceProfileUrl("second-hand")} className="text-xs font-700 text-qblack underline">
            İkinci El panelim
          </Link>
        </div>
      </div>
    );
  }

  const submit = async () => {
    const text = body.trim();
    if (!text) {
      toast.warn("Mesaj yazın.");
      return;
    }
    try {
      const res = await sendToListing({ listingId, body: { body: text } }).unwrap();
      toast.success("Mesaj gönderildi.");
      setBody("");
      const convId = res?.conversation?.id;
      const qp = convId ? `?c2c_conv=${encodeURIComponent(String(convId))}` : "";
      window.location.assign(marketplaceProfileUrl("second-hand-messages", qp));
    } catch (err) {
      const msg = err?.data?.message || "Mesaj gönderilemedi.";
      toast.error(msg);
    }
  };

  return (
    <div className="mt-8 p-4 border border-qgray-border rounded-lg">
      <h2 className="text-lg font-600 text-qblack mb-2">Satıcıya yaz</h2>
      <p className="text-xs text-qgray mb-3">
        Mesaj gönderebilmek için ikinci el doğrulamanız onaylı olmalıdır. Aktif olmayan ilanlarda mesaj kapalıdır.
      </p>
      <textarea
        value={body}
        onChange={(e) => setBody(e.target.value)}
        rows={4}
        maxLength={2000}
        placeholder="Merhaba, ürün hâlâ satılık mı?"
        className="w-full px-3 py-2 border border-qgray-border rounded-md text-sm mb-3"
      />
      <div className="flex flex-wrap gap-3 items-center">
        <button
          type="button"
          disabled={isLoading}
          onClick={submit}
          className="h-10 px-6 bg-qblack text-white rounded-md text-sm font-600 disabled:opacity-50"
        >
          {isLoading ? "Gönderiliyor…" : "Gönder"}
        </button>
        <Link href={marketplaceProfileUrl("second-hand")} className="text-sm text-qgray underline">
          Doğrulama ve mesajlarım
        </Link>
      </div>
    </div>
  );
}
