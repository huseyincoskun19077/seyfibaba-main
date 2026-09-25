"use client";

import Image from "next/image";
import { useEffect, useMemo, useState } from "react";
import { useSelector } from "react-redux";
import appConfig from "@/appConfig";
import settings from "@/utils/settings";
import { getProductImageProps } from "@/utils/productImage";

const STORAGE_KEY = "kuafortedarik_mobile_app_prompt_dismissed_at";
const DISMISS_DAYS = 7;

/** Yayınlama (store package / listing) kimlikleri — değiştirilmez */
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
  const mobileUa =
    /Android|iPhone|iPad|iPod|Mobile|webOS|BlackBerry|IEMobile|Opera Mini/i.test(
      ua
    );
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

function setupRoot(setupData, websiteSetup) {
  return (
    setupData ||
    websiteSetup?.payload ||
    websiteSetup ||
    null
  );
}

function absoluteMedia(path) {
  if (!path) return null;
  const s = String(path).trim();
  if (!s) return null;
  if (/^https?:\/\//i.test(s)) return s;
  return `${appConfig.BASE_URL}${s.replace(/^\//, "")}`;
}

/**
 * Mobil web ilk girişte: Kuaför Tedarik uygulamasını indir (footer mağaza görselleri).
 */
export default function MobileAppPrompt({ setupData = null }) {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const settingData = settings();
  const logo = settingData?.logo || null;
  const [visible, setVisible] = useState(false);
  const [platform, setPlatform] = useState({
    isMobile: false,
    isIos: false,
    isAndroid: false,
  });

  const { playStoreUrl, appStoreUrl, appStoreImage, playStoreImage } =
    useMemo(() => {
      const root = setupRoot(setupData, websiteSetup);
      const footer = root?.footer || null;
      const banner =
        root?.flashSaleSidebarBanner ||
        root?.payload?.flashSaleSidebarBanner ||
        null;

      const play =
        String(footer?.play_store_url || "").trim() ||
        String(banner?.play_store || banner?.link || "").trim() ||
        FALLBACK_PLAY_STORE;
      const appleRaw =
        String(footer?.app_store_url || "").trim() ||
        String(banner?.app_store || "").trim() ||
        FALLBACK_APP_STORE;
      const apple = /^https?:\/\//i.test(appleRaw)
        ? appleRaw
        : FALLBACK_APP_STORE;
      const playUrl = /^https?:\/\//i.test(play) ? play : FALLBACK_PLAY_STORE;

      return {
        playStoreUrl: playUrl,
        appStoreUrl: apple,
        appStoreImage: absoluteMedia(footer?.app_store_image),
        playStoreImage: absoluteMedia(footer?.play_store_image),
      };
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
    const t = window.setTimeout(() => setVisible(true), 400);
    return () => window.clearTimeout(t);
  }, []);

  const dismiss = () => {
    try {
      localStorage.setItem(STORAGE_KEY, String(Date.now()));
    } catch {
      /* ignore */
    }
    setVisible(false);
  };

  const openStore = (url) => {
    if (!url) return;
    window.location.href = url;
  };

  if (!visible) return null;

  const StoreButton = ({ href, imageSrc, label, sublabel, primary }) => {
    if (!href) return null;
    if (imageSrc) {
      return (
        <button
          type="button"
          onClick={() => openStore(href)}
          className="flex w-full items-center justify-center rounded-2xl bg-transparent p-1 transition hover:opacity-90"
        >
          <Image
            width={180}
            height={54}
            src={imageSrc}
            alt={label}
            className="h-[52px] w-auto object-contain"
            unoptimized
          />
        </button>
      );
    }
    return (
      <button
        type="button"
        onClick={() => openStore(href)}
        className={
          primary
            ? "flex w-full items-center justify-center rounded-2xl bg-qyellow px-4 py-3.5 text-base font-800 text-qblack transition hover:brightness-95"
            : "flex w-full items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-700 text-qblack transition hover:bg-gray-50"
        }
      >
        <span className="text-center leading-tight">
          {sublabel ? (
            <span className="block text-[11px] font-500 opacity-70">
              {sublabel}
            </span>
          ) : null}
          <span className="block">{label}</span>
        </span>
      </button>
    );
  };

  const primaryIsIos = platform.isIos;
  const primaryUrl = primaryIsIos ? appStoreUrl : playStoreUrl;
  const primaryImage = primaryIsIos ? appStoreImage : playStoreImage;
  const secondaryUrl = primaryIsIos ? playStoreUrl : appStoreUrl;
  const secondaryImage = primaryIsIos ? playStoreImage : appStoreImage;
  const primaryLabel = primaryIsIos ? "App Store" : "Google Play";
  const secondaryLabel = primaryIsIos ? "Google Play" : "App Store";

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
        <div className="bg-[#04334a] px-6 pt-7 pb-6 text-white">
          <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl bg-white p-1.5">
            {logo ? (
              <Image
                width={48}
                height={48}
                className="h-full w-full object-contain"
                {...getProductImageProps(logo)}
                alt="Kuaför Tedarik"
              />
            ) : (
              <span className="text-lg font-800 text-[#04334a]">KT</span>
            )}
          </div>
          <h2
            id="mobile-app-prompt-title"
            className="text-center text-xl font-800 leading-tight"
          >
            Kuaför Tedarik uygulamasını indirin
          </h2>
          <p className="mt-2 text-center text-sm leading-relaxed text-white/75">
            Daha hızlı alışveriş ve bildirimler için mobil uygulamayı kullanın.
            İsterseniz web’de de devam edebilirsiniz.
          </p>
        </div>

        <div className="space-y-3 px-5 py-5">
          {platform.isIos || platform.isAndroid ? (
            <>
              <StoreButton
                href={primaryUrl}
                imageSrc={primaryImage}
                label={`${primaryLabel}’dan indir`}
                sublabel="İndirin"
                primary
              />
              {secondaryUrl ? (
                <StoreButton
                  href={secondaryUrl}
                  imageSrc={secondaryImage}
                  label={`${secondaryLabel}’dan indir`}
                  sublabel="İndirin"
                />
              ) : null}
            </>
          ) : (
            <div className="grid grid-cols-2 gap-3">
              <StoreButton
                href={playStoreUrl}
                imageSrc={playStoreImage}
                label="Android"
              />
              <StoreButton
                href={appStoreUrl}
                imageSrc={appStoreImage}
                label="iPhone"
              />
            </div>
          )}

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
