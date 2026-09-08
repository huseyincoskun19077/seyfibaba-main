"use client";

import { useEffect, useMemo, useState } from "react";
import { useSelector } from "react-redux";

const STORAGE_KEY = "seyfibaba_mobile_app_prompt_dismissed_at";
const DISMISS_DAYS = 7;

/** Admin banner boş olsa bile Play Store sayfası açılsın */
const FALLBACK_PLAY_STORE =
  (typeof process !== "undefined" && process.env.NEXT_PUBLIC_PLAY_STORE_URL) ||
  "https://play.google.com/store/apps/details?id=com.seyfibaba.app";

const FALLBACK_APP_STORE =
  (typeof process !== "undefined" && process.env.NEXT_PUBLIC_APP_STORE_URL) ||
  "https://apps.apple.com/tr/search?term=Seyfibaba";

function detectPlatform() {
  if (typeof window === "undefined") {
    return { isMobile: false, isIos: false, isAndroid: false };
  }
  const ua = navigator.userAgent || "";
  const isIos = /iPhone|iPad|iPod/i.test(ua);
  const isAndroid = /Android/i.test(ua);
  const mobileUa = /Android|iPhone|iPad|iPod|Mobile|webOS|BlackBerry|IEMobile|Opera Mini/i.test(ua);
  const narrow =
    typeof window.matchMedia === "function" &&
    window.matchMedia("(max-width: 900px)").matches;
  return {
    isMobile: mobileUa || narrow,
    isIos,
    isAndroid,
  };
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

function pickBanner(setupRoot) {
  if (!setupRoot || typeof setupRoot !== "object") return null;
  // RTK: state.websiteSetup = action → .payload = setup data
  // veya doğrudan setup data
  return (
    setupRoot.flashSaleSidebarBanner ||
    setupRoot.payload?.flashSaleSidebarBanner ||
    null
  );
}

/**
 * Mobil web ilk girişte tam ekran popup: App Store / Play Store indirme.
 */
export default function MobileAppPrompt({ setupData = null }) {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const [visible, setVisible] = useState(false);
  const [platform, setPlatform] = useState({
    isMobile: false,
    isIos: false,
    isAndroid: false,
  });

  const { playStoreUrl, appStoreUrl } = useMemo(() => {
    const banner =
      pickBanner(setupData) ||
      pickBanner(websiteSetup) ||
      pickBanner(websiteSetup?.payload);

    const play =
      String(banner?.play_store || banner?.link || "").trim() ||
      String(FALLBACK_PLAY_STORE || "").trim();
    const apple =
      String(banner?.app_store || banner?.title || "").trim() ||
      String(FALLBACK_APP_STORE || "").trim();

    // title alanı App Store URL değilse (eski banner metni) fallback kullan
    const appleUrl = /^https?:\/\//i.test(apple) ? apple : FALLBACK_APP_STORE;
    const playUrl = /^https?:\/\//i.test(play) ? play : FALLBACK_PLAY_STORE;

    return { playStoreUrl: playUrl, appStoreUrl: appleUrl };
  }, [setupData, websiteSetup]);

  useEffect(() => {
    const p = detectPlatform();
    setPlatform(p);
    if (!p.isMobile) {
      setVisible(false);
      return;
    }
    if (isDismissedRecently()) {
      setVisible(false);
      return;
    }
    // Kısa gecikme: sayfa boyası bittikten sonra popup
    const t = window.setTimeout(() => setVisible(true), 400);
    return () => window.clearTimeout(t);
  }, []);

  const dismiss = () => {
    try {
      localStorage.setItem(STORAGE_KEY, String(Date.now()));
    } catch {
      // ignore
    }
    setVisible(false);
  };

  const primaryUrl = platform.isIos ? appStoreUrl : playStoreUrl;
  const primaryLabel = platform.isIos
    ? "App Store’dan indir"
    : "Google Play’den indir";

  const openStore = (url) => {
    if (!url) return;
    window.location.href = url;
  };

  if (!visible) return null;

  return (
    <div
      className="fixed inset-0 z-[10060] flex items-end justify-center sm:items-center p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="mobile-app-prompt-title"
    >
      <button
        type="button"
        className="absolute inset-0 bg-black/60 backdrop-blur-[2px]"
        aria-label="Kapat"
        onClick={dismiss}
      />

      <div className="relative z-10 w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
        <div className="bg-qblack px-6 pt-7 pb-6 text-white">
          <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-qyellow text-2xl font-800 text-qblack">
            S
          </div>
          <h2
            id="mobile-app-prompt-title"
            className="text-center text-xl font-800 leading-tight"
          >
            Seyfibaba uygulamasını indirin
          </h2>
          <p className="mt-2 text-center text-sm leading-relaxed text-white/75">
            Daha hızlı alışveriş, bildirimler ve ikinci el için mobil uygulamayı
            kullanın. İsterseniz web’de de devam edebilirsiniz.
          </p>
        </div>

        <div className="space-y-3 px-5 py-5">
          <button
            type="button"
            onClick={() => openStore(primaryUrl)}
            className="flex w-full items-center justify-center rounded-2xl bg-qyellow px-4 py-3.5 text-base font-800 text-qblack transition hover:brightness-95"
          >
            {primaryLabel}
          </button>

          {!platform.isIos && appStoreUrl ? (
            <button
              type="button"
              onClick={() => openStore(appStoreUrl)}
              className="flex w-full items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-700 text-qblack transition hover:bg-gray-50"
            >
              App Store’dan indir
            </button>
          ) : null}

          {platform.isIos && playStoreUrl ? (
            <button
              type="button"
              onClick={() => openStore(playStoreUrl)}
              className="flex w-full items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-700 text-qblack transition hover:bg-gray-50"
            >
              Google Play’den indir
            </button>
          ) : null}

          {!platform.isIos && !platform.isAndroid ? (
            <div className="grid grid-cols-2 gap-2">
              <button
                type="button"
                onClick={() => openStore(playStoreUrl)}
                className="rounded-xl border border-gray-200 px-3 py-2.5 text-xs font-700 text-qblack hover:bg-gray-50"
              >
                Android
              </button>
              <button
                type="button"
                onClick={() => openStore(appStoreUrl)}
                className="rounded-xl border border-gray-200 px-3 py-2.5 text-xs font-700 text-qblack hover:bg-gray-50"
              >
                iPhone
              </button>
            </div>
          ) : null}

          <button
            type="button"
            onClick={dismiss}
            className="w-full rounded-2xl px-4 py-3 text-sm font-600 text-qgray transition hover:bg-gray-50 hover:text-qblack"
          >
            Web’de devam et
          </button>
        </div>
      </div>
    </div>
  );
}
