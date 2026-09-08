"use client";

import { useEffect, useMemo, useState } from "react";
import { useSelector } from "react-redux";
import GooglePlay from "@/components/Helpers/icons/GooglePlay";
import AppleStore from "@/components/Helpers/icons/AppleStore";

const STORAGE_KEY = "seyfibaba_mobile_app_prompt_dismissed_at";
const DISMISS_DAYS = 14;

function isMobileBrowser() {
  if (typeof window === "undefined") return false;
  const ua = navigator.userAgent || "";
  const mobileUa = /Android|iPhone|iPad|iPod|Mobile|webOS|BlackBerry|IEMobile|Opera Mini/i.test(ua);
  const narrow = window.matchMedia("(max-width: 900px)").matches;
  return mobileUa || narrow;
}

function isDismissedRecently() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return false;
    const at = Number(raw);
    if (!Number.isFinite(at)) return false;
    return Date.now() - at < DISMISS_DAYS * 24 * 60 * 60 * 1000;
  } catch {
    return false;
  }
}

/**
 * Mobil tarayıcıda opsiyonel uygulama indirme önerisi.
 * Zorla yönlendirme yapmaz; "Web'de devam et" ile kapanır.
 */
export default function MobileAppPrompt() {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const [visible, setVisible] = useState(false);

  const { playStoreUrl, appStoreUrl, hasLinks } = useMemo(() => {
    const banner = websiteSetup?.payload?.flashSaleSidebarBanner;
    const play =
      (banner?.play_store || "").trim() ||
      (process.env.NEXT_PUBLIC_PLAY_STORE_URL || "").trim();
    const apple =
      (banner?.app_store || "").trim() ||
      (process.env.NEXT_PUBLIC_APP_STORE_URL || "").trim();
    const enabled = banner == null || Number(banner.status) === 1;
    return {
      playStoreUrl: play,
      appStoreUrl: apple,
      hasLinks: enabled && Boolean(play || apple),
    };
  }, [websiteSetup]);

  useEffect(() => {
    if (!hasLinks) {
      setVisible(false);
      return;
    }
    if (!isMobileBrowser() || isDismissedRecently()) {
      setVisible(false);
      return;
    }
    setVisible(true);
  }, [hasLinks]);

  const dismiss = () => {
    try {
      localStorage.setItem(STORAGE_KEY, String(Date.now()));
    } catch {
      // ignore
    }
    setVisible(false);
  };

  if (!visible || !hasLinks) return null;

  return (
    <div
      className="fixed inset-x-0 bottom-0 z-[10050] p-3 sm:p-4 pointer-events-none"
      role="dialog"
      aria-label="Mobil uygulama önerisi"
    >
      <div className="pointer-events-auto mx-auto max-w-lg rounded-2xl border border-black/10 bg-qblack text-white shadow-2xl shadow-black/40">
        <div className="flex items-start gap-3 px-4 pt-4 pb-3">
          <div className="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-qyellow text-qblack text-lg font-800">
            S
          </div>
          <div className="min-w-0 flex-1">
            <p className="text-sm font-800 leading-snug">Seyfibaba uygulamasını deneyin</p>
            <p className="mt-1 text-xs leading-relaxed text-white/75">
              Mobilde alışveriş, ikinci el ve bildirimler uygulama ile daha rahat. İsterseniz
              indirebilir, isterseniz web’de devam edebilirsiniz.
            </p>
          </div>
          <button
            type="button"
            onClick={dismiss}
            className="shrink-0 rounded-full p-1.5 text-white/60 transition hover:bg-white/10 hover:text-white"
            aria-label="Kapat"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
            </svg>
          </button>
        </div>

        <div className="flex flex-wrap items-center gap-2 px-4 pb-3">
          {playStoreUrl ? (
            <a
              href={playStoreUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex scale-90 origin-left"
              aria-label="Google Play'den indir"
            >
              <GooglePlay />
            </a>
          ) : null}
          {appStoreUrl ? (
            <a
              href={appStoreUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex scale-90 origin-left"
              aria-label="App Store'dan indir"
            >
              <AppleStore />
            </a>
          ) : null}
        </div>

        <div className="border-t border-white/10 px-4 py-3">
          <button
            type="button"
            onClick={dismiss}
            className="w-full rounded-xl bg-white/10 px-3 py-2.5 text-sm font-700 text-white transition hover:bg-white/15"
          >
            Web’de devam et
          </button>
        </div>
      </div>
    </div>
  );
}
