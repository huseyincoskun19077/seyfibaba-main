"use client";

import { useState } from "react";
import { toast } from "react-toastify";
import LoaderStyleOne from "@/components/Helpers/Loaders/LoaderStyleOne";
import { useLazyGoogleGetLoginUrlApiQuery } from "@/redux/features/socialLogin/apiSlice";

export default function SocialAuthButtons({ className = "" }) {
  const [busy, setBusy] = useState(false);
  const [fetchGoogleUrl] = useLazyGoogleGetLoginUrlApiQuery();

  const startGoogle = async () => {
    try {
      setBusy(true);
      const res = await fetchGoogleUrl();
      const url = res?.data?.url;
      if (!url) {
        toast.error("Google girişi şu an kullanılamıyor. Admin ayarlarını kontrol edin.");
        return;
      }
      window.location.href = url;
    } catch {
      toast.error("Google girişi başlatılamadı.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className={`w-full ${className}`}>
      <div className="flex items-center gap-3 my-5">
        <div className="h-px flex-1 bg-gray-200" />
        <span className="text-xs font-600 text-qgray">VEYA</span>
        <div className="h-px flex-1 bg-gray-200" />
      </div>

      <button
        type="button"
        onClick={startGoogle}
        disabled={busy}
        className="w-full h-[50px] rounded-md border border-gray-200 bg-white hover:bg-gray-50 transition flex items-center justify-center gap-3 disabled:opacity-60"
      >
        <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden>
          <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.8 1.1 7.9 3l5.7-5.7C34.2 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.2-.1-2.3-.4-3.5z" />
          <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 16 19 12 24 12c3 0 5.8 1.1 7.9 3l5.7-5.7C34.2 6.1 29.3 4 24 4 16.1 4 9.2 8.5 6.3 14.7z" />
          <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.3 26.8 36 24 36c-5.3 0-9.7-3.3-11.3-8l-6.5 5C9.1 39.5 16 44 24 44z" />
          <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.2-4.2 5.6l.1.1 6.2 5.2C39.2 36.9 44 31.5 44 24c0-1.2-.1-2.3-.4-3.5z" />
        </svg>
        <span className="text-sm font-600 text-qblack">Google ile devam et</span>
        {busy ? (
          <span className="w-5 scale-50">
            <LoaderStyleOne />
          </span>
        ) : null}
      </button>
    </div>
  );
}
