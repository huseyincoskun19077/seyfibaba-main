import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { deleteCookie } from "cookies-next";

const CONFIG = {
  COOKIE: {
    NAME: "googtrans",
    PATH: "/",
  },
  LANGUAGES: {
    DIRECTIONS: { LTR: "ltr", RTL: "rtl" },
  },
  APP_DOWNLOAD_PATH: "/indir",
  DEFAULT_SLOGANS: ["Her Satıcıda 1000 TL Üzeri KARGO ÜCRETSİZ"],
  ROTATE_MS: 4000,
};

const TruckIcon = () => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="currentColor"
    className="w-4 h-4 shrink-0"
    aria-hidden="true"
  >
    <path d="M3.375 4.5C2.339 4.5 1.5 5.34 1.5 6.375V13.5h12V6.375c0-1.036-.84-1.875-1.875-1.875H3.375zM13.5 15h-12v2.625c0 1.035.84 1.875 1.875 1.875h.375a3 3 0 116 0h3a.75.75 0 00.75-.75V15z" />
    <path d="M8.25 19.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0zM15.75 6.75a.75.75 0 00-.75.75v11.25c0 .10.10.0.2.75h.75a3 3 0 116 0h.75a.75.75 0 00.75-.75V8.25a.75.75 0 00-.75-.75h-1.5A2.25 2.25 0 0016.5 6h-.75zM18 19.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0zM16.5 9.75a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v1.5a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-1.5z" />
  </svg>
);

const PhoneDeviceIcon = () => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.8"
    className="w-3.5 h-3.5 shrink-0"
    aria-hidden="true"
  >
    <path
      strokeLinecap="round"
      strokeLinejoin="round"
      d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"
    />
  </svg>
);

const setDocumentDirection = () =>
  document.body.setAttribute("dir", CONFIG.LANGUAGES.DIRECTIONS.LTR);

const navLinkClass =
  "text-[11px] leading-none text-[#04334a]/85 font-500 hover:text-[#04334a] transition-colors whitespace-nowrap";

function parseSlogans(raw) {
  if (raw === undefined || raw === null) {
    return CONFIG.DEFAULT_SLOGANS;
  }
  if (Array.isArray(raw)) {
    return raw.map((s) => String(s).trim()).filter(Boolean);
  }
  if (typeof raw !== "string") {
    return CONFIG.DEFAULT_SLOGANS;
  }
  if (!raw.trim()) {
    return [];
  }
  try {
    const decoded = JSON.parse(raw);
    if (Array.isArray(decoded)) {
      return decoded.map((s) => String(s).trim()).filter(Boolean);
    }
  } catch {
    // plain text / newline list
  }
  return raw
    .split(/\r?\n/)
    .map((s) => s.trim())
    .filter(Boolean);
}

export default function TopBar({ className, contact, settings }) {
  const slogans = useMemo(
    () => parseSlogans(settings?.topbar_announcement),
    [settings?.topbar_announcement]
  );
  const [index, setIndex] = useState(0);
  const [visible, setVisible] = useState(true);

  useEffect(() => {
    deleteCookie(CONFIG.COOKIE.NAME, {
      path: CONFIG.COOKIE.PATH,
    });
    setDocumentDirection();
  }, []);

  useEffect(() => {
    setIndex(0);
  }, [slogans]);

  useEffect(() => {
    if (slogans.length <= 1) return undefined;

    const timer = setInterval(() => {
      setVisible(false);
      setTimeout(() => {
        setIndex((prev) => (prev + 1) % slogans.length);
        setVisible(true);
      }, 220);
    }, CONFIG.ROTATE_MS);

    return () => clearInterval(timer);
  }, [slogans]);

  const phone = settings?.topbar_phone || contact?.phone || "";
  const phoneHref = phone ? `tel:${String(phone).replace(/\s+/g, "")}` : null;
  const currentSlogan = slogans[index] || "";

  return (
    <div
      className={`w-full bg-white h-9 shadow-[0_2px_8px_rgba(4,51,74,0.08)] border-b border-[#04334a]/08 ${className || ""}`}
    >
      <div className="container-x mx-auto h-full">
        <div className="flex justify-between items-center h-full gap-4">
          <div className="flex items-center gap-2 min-w-0">
            {currentSlogan ? (
              <>
                <span className="text-[#04334a] shrink-0">
                  <TruckIcon />
                </span>
                <span
                  className={`text-[11px] sm:text-[12px] leading-none text-[#04334a] font-600 truncate transition-opacity duration-200 ${
                    visible ? "opacity-100" : "opacity-0"
                  }`}
                >
                  {currentSlogan}
                </span>
              </>
            ) : null}
          </div>

          <nav
            className="hidden md:flex items-center gap-4 lg:gap-5 shrink-0"
            aria-label="Üst bar bağlantıları"
          >
            <Link href="/about" className={navLinkClass}>
              Hakkımızda
            </Link>
            <Link
              href={CONFIG.APP_DOWNLOAD_PATH}
              className={`${navLinkClass} inline-flex items-center gap-1.5`}
            >
              <PhoneDeviceIcon />
              Mobil Uygulama
            </Link>
            {phoneHref ? (
              <a href={phoneHref} className={navLinkClass}>
                Müşteri Hizmetleri {phone}
              </a>
            ) : null}
            <Link
              href="/satici"
              className="text-[11px] leading-none text-[#04334a] font-800 hover:opacity-80 transition whitespace-nowrap"
            >
              Kuaför Tedarik&apos;da Satış Yap
            </Link>
          </nav>
        </div>
      </div>
    </div>
  );
}
