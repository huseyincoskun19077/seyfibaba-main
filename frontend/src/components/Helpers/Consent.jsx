"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { hasCookie, setCookie, getCookie } from "cookies-next";
import { useSelector } from "react-redux";

const STORAGE_KEY = "seyfibaba_cookie_prefs";
const LEGACY_KEY = "localConsent";

const DEFAULT_PREFS = {
  necessary: true,
  functional: true,
  analytics: false,
  marketing: false,
};

function readPrefs() {
  if (typeof window === "undefined") return null;
  try {
    const raw = getCookie(STORAGE_KEY);
    if (!raw) {
      // Eski banner: localConsent=true → hepsini kabul say
      if (hasCookie(LEGACY_KEY)) {
        const legacy = String(getCookie(LEGACY_KEY) || "");
        if (legacy === "true") {
          return { ...DEFAULT_PREFS, analytics: true, marketing: true, functional: true };
        }
        if (legacy === "false") {
          return { ...DEFAULT_PREFS, analytics: false, marketing: false, functional: false };
        }
      }
      return null;
    }
    const parsed = typeof raw === "string" ? JSON.parse(raw) : raw;
    return {
      necessary: true,
      functional: Boolean(parsed.functional),
      analytics: Boolean(parsed.analytics),
      marketing: Boolean(parsed.marketing),
    };
  } catch {
    return null;
  }
}

function persistPrefs(prefs) {
  const payload = {
    necessary: true,
    functional: Boolean(prefs.functional),
    analytics: Boolean(prefs.analytics),
    marketing: Boolean(prefs.marketing),
  };
  setCookie(STORAGE_KEY, JSON.stringify(payload), { maxAge: 60 * 60 * 24 * 365, path: "/" });
  setCookie(LEGACY_KEY, payload.marketing || payload.analytics ? "true" : "false", {
    maxAge: 60 * 60 * 24 * 365,
    path: "/",
  });
  if (typeof window !== "undefined") {
    window.dispatchEvent(new CustomEvent("seyfibaba:cookie-prefs", { detail: payload }));
  }
  return payload;
}

export function getCookiePrefs() {
  return readPrefs();
}

export function hasMarketingConsent() {
  const prefs = readPrefs();
  return Boolean(prefs?.marketing);
}

export function hasAnalyticsConsent() {
  const prefs = readPrefs();
  return Boolean(prefs?.analytics);
}

const CATEGORIES = [
  {
    key: "necessary",
    title: "Zorunlu çerezler",
    desc: "Sitenin çalışması, oturum ve güvenlik için gereklidir. Kapatılamaz.",
    locked: true,
  },
  {
    key: "functional",
    title: "İşlevsel çerezler",
    desc: "Dil, para birimi ve tercihlerinizi hatırlamamıza yardımcı olur.",
    locked: false,
  },
  {
    key: "analytics",
    title: "Analitik çerezler",
    desc: "Site kullanımını anonim ölçerek deneyimi iyileştirmemize yardımcı olur.",
    locked: false,
  },
  {
    key: "marketing",
    title: "Pazarlama / reklam çerezleri",
    desc: "Meta Pixel ve Google Ads gibi araçlarla reklam performansını ölçmek ve size uygun reklam göstermek için kullanılır.",
    locked: false,
  },
];

function Consent() {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const [ready, setReady] = useState(false);
  const [visible, setVisible] = useState(false);
  const [settingsOpen, setSettingsOpen] = useState(false);
  const [prefs, setPrefs] = useState(DEFAULT_PREFS);
  const consentEnabled = Number(websiteSetup?.payload?.cookie_consent?.status ?? 1) === 1;
  const adminMessage = websiteSetup?.payload?.cookie_consent?.message;

  useEffect(() => {
    const existing = readPrefs();
    if (existing) {
      setPrefs(existing);
      setVisible(false);
    } else {
      setVisible(true);
    }
    setReady(true);
  }, []);

  const acceptAll = () => {
    const next = {
      necessary: true,
      functional: true,
      analytics: true,
      marketing: true,
    };
    setPrefs(next);
    persistPrefs(next);
    setSettingsOpen(false);
    setVisible(false);
  };

  const rejectOptional = () => {
    const next = {
      necessary: true,
      functional: false,
      analytics: false,
      marketing: false,
    };
    setPrefs(next);
    persistPrefs(next);
    setSettingsOpen(false);
    setVisible(false);
  };

  const saveSettings = () => {
    persistPrefs(prefs);
    setSettingsOpen(false);
    setVisible(false);
  };

  if (!ready || !consentEnabled || !visible) {
    return null;
  }

  return (
    <>
      <div
        className="fixed z-[999999999] left-0 right-0 bottom-0 md:left-6 md:right-auto md:bottom-6 md:max-w-[440px] w-full"
        role="dialog"
        aria-label="Çerez bildirimi"
      >
        <div className="m-0 md:rounded-2xl border border-[#ece3cf] bg-white shadow-[0_15px_50px_rgba(0,0,0,0.14)] p-5 md:p-6">
          <h3 className="text-lg font-700 text-qblacktext mb-2">Çerez kullanımı</h3>
          <p className="text-sm text-qgray leading-relaxed mb-3">
            {adminMessage ||
              "Seyfibaba, siteyi çalıştırmak, deneyiminizi iyileştirmek ve (onayınızla) reklam / analitik ölçümü yapmak için çerezler kullanır. Tercihlerinizi dilediğiniz zaman değiştirebilirsiniz."}
          </p>
          <Link href="/privacy-policy" className="text-sm text-blue-600 underline">
            Gizlilik ve çerez politikası
          </Link>

          <div className="mt-5 flex flex-col gap-2.5">
            <button
              type="button"
              onClick={acceptAll}
              className="w-full h-11 rounded-md bg-qyellow text-qblack font-700 text-sm hover:brightness-95 transition"
            >
              Tümünü Kabul Et
            </button>
            <button
              type="button"
              onClick={() => setSettingsOpen(true)}
              className="w-full h-11 rounded-md border border-[#d9c89a] bg-white text-qblacktext font-700 text-sm hover:bg-[#fffaf0] transition"
            >
              Çerez Ayarları
            </button>
            <button
              type="button"
              onClick={rejectOptional}
              className="w-full h-10 rounded-md text-qgray font-600 text-sm hover:text-qblacktext transition"
            >
              Yalnızca zorunlu çerezler
            </button>
          </div>
        </div>
      </div>

      {settingsOpen && (
        <div className="fixed inset-0 z-[10000000000] flex items-end md:items-center justify-center bg-black/40 p-0 md:p-4">
          <div className="w-full md:max-w-lg max-h-[90vh] overflow-y-auto rounded-t-2xl md:rounded-2xl bg-white shadow-xl">
            <div className="sticky top-0 bg-white border-b border-[#eee] px-5 py-4 flex items-center justify-between">
              <h3 className="text-lg font-700 text-qblacktext">Çerez Ayarları</h3>
              <button
                type="button"
                onClick={() => setSettingsOpen(false)}
                className="text-qgray hover:text-qblacktext text-sm font-600"
              >
                Kapat
              </button>
            </div>

            <div className="px-5 py-4 space-y-4">
              <p className="text-sm text-qgray leading-relaxed">
                İstediğiniz kategorileri açıp kapatabilirsiniz. Zorunlu çerezler site için
                gereklidir.
              </p>

              {CATEGORIES.map((cat) => (
                <div
                  key={cat.key}
                  className="rounded-xl border border-[#ece3cf] p-4 flex gap-3 items-start"
                >
                  <div className="flex-1 min-w-0">
                    <p className="font-700 text-qblacktext text-sm">{cat.title}</p>
                    <p className="mt-1 text-xs text-qgray leading-relaxed">{cat.desc}</p>
                  </div>
                  <label className="relative inline-flex items-center cursor-pointer shrink-0 mt-0.5">
                    <input
                      type="checkbox"
                      className="sr-only peer"
                      checked={Boolean(prefs[cat.key])}
                      disabled={cat.locked}
                      onChange={(e) =>
                        setPrefs((prev) => ({
                          ...prev,
                          [cat.key]: e.target.checked,
                          necessary: true,
                        }))
                      }
                    />
                    <span
                      className={`w-11 h-6 rounded-full transition ${
                        prefs[cat.key] ? "bg-qyellow" : "bg-gray-300"
                      } ${cat.locked ? "opacity-70" : ""} relative after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all ${
                        prefs[cat.key] ? "after:translate-x-5" : ""
                      }`}
                    />
                  </label>
                </div>
              ))}
            </div>

            <div className="sticky bottom-0 bg-white border-t border-[#eee] px-5 py-4 flex flex-col sm:flex-row gap-2">
              <button
                type="button"
                onClick={acceptAll}
                className="flex-1 h-11 rounded-md bg-qyellow text-qblack font-700 text-sm"
              >
                Tümünü Kabul Et
              </button>
              <button
                type="button"
                onClick={saveSettings}
                className="flex-1 h-11 rounded-md border border-[#d9c89a] text-qblacktext font-700 text-sm"
              >
                Seçimimi Kaydet
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}

export default Consent;
