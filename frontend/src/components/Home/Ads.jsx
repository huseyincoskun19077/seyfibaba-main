"use client";
import { useEffect, useState } from "react";
import { useSelector } from "react-redux";
import Link from "next/link";
import Image from "next/image";
import appConfig from "@/appConfig";

const SESSION_KEY = "home_promo_shown_session";
const DISMISS_KEY = "home_promo_dismiss_until";

function resolveHref(link) {
  if (!link || typeof link !== "string") return null;
  const href = link.trim();
  if (!href) return null;
  if (href.startsWith("http://") || href.startsWith("https://")) return href;
  return href.startsWith("/") ? href : `/${href}`;
}

function isDismissed() {
  try {
    const until = localStorage.getItem(DISMISS_KEY);
    if (!until) return false;
    return new Date() < new Date(until);
  } catch {
    return false;
  }
}

function markSessionShown() {
  try {
    sessionStorage.setItem(SESSION_KEY, "1");
  } catch {
    /* ignore */
  }
}

function wasShownThisSession() {
  try {
    return sessionStorage.getItem(SESSION_KEY) === "1";
  } catch {
    return false;
  }
}

export default function Ads() {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const [announcement, setAnnouncement] = useState(null);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const data = websiteSetup?.payload?.announcementModal;
    if (!data) return;
    setAnnouncement(data);

    const active = parseInt(data.status, 10) === 1;
    const onWeb = data.show_on_web === undefined || parseInt(data.show_on_web, 10) === 1;
    const hasImage = Boolean(data.image);
    if (!active || !onWeb || !hasImage) {
      setVisible(false);
      return;
    }
    if (wasShownThisSession() || isDismissed()) {
      setVisible(false);
      return;
    }

    markSessionShown();
    setVisible(true);
  }, [websiteSetup]);

  const close = () => setVisible(false);

  const dismissForeverPeriod = () => {
    if (!announcement) {
      close();
      return;
    }
    const days = Math.max(1, parseInt(announcement.expired_date, 10) || 7);
    const date = new Date();
    date.setDate(date.getDate() + days);
    try {
      localStorage.setItem(DISMISS_KEY, date.toISOString());
      // Eski anahtarları temizle
      localStorage.removeItem("ads");
      localStorage.removeItem("upcoming_announcement");
    } catch {
      /* ignore */
    }
    markSessionShown();
    close();
  };

  if (!visible || !announcement?.image) return null;

  const href = resolveHref(announcement.link);
  const cta = (announcement.cta_text || "").trim() || (href ? "İncele" : "");
  const imageSrc = `${appConfig.BASE_URL}${announcement.image}`;

  const content = (
    <>
      <div className="relative w-full aspect-[4/5] max-h-[70vh] bg-qblacklight">
        <Image
          src={imageSrc}
          alt={announcement.title || "Kampanya"}
          fill
          priority
          className="object-cover"
          sizes="(max-width: 768px) 92vw, 420px"
        />
      </div>
      {(announcement.title || announcement.description || cta) && (
        <div className="px-4 pt-4 pb-2 text-center bg-white">
          {announcement.title ? (
            <h2 className="text-lg font-bold text-qblack leading-snug mb-1">
              {announcement.title}
            </h2>
          ) : null}
          {announcement.description ? (
            <p className="text-sm text-qgray leading-relaxed mb-3">
              {announcement.description}
            </p>
          ) : null}
          {cta && href ? (
            <span className="inline-flex items-center justify-center min-w-[140px] h-11 px-5 rounded-md bg-qyellow text-qblack text-sm font-semibold">
              {cta}
            </span>
          ) : null}
        </div>
      )}
    </>
  );

  return (
    <div className="fixed inset-0 z-[60] flex items-center justify-center p-4">
      <button
        type="button"
        className="absolute inset-0 bg-black/50 border-0 cursor-pointer"
        aria-label="Kapat"
        onClick={close}
      />
      <div className="relative z-10 w-full max-w-[420px] rounded-xl overflow-hidden bg-white shadow-2xl">
        <button
          type="button"
          onClick={close}
          className="absolute top-3 right-3 z-20 w-9 h-9 rounded-full bg-black/55 text-white flex items-center justify-center hover:bg-black/75"
          aria-label="Kapat"
        >
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden>
            <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
          </svg>
        </button>

        {href ? (
          <Link href={href} onClick={close} className="block">
            {content}
          </Link>
        ) : (
          <div>{content}</div>
        )}

        <div className="px-4 pb-4 pt-1 bg-white border-t border-qgray-border">
          <button
            type="button"
            onClick={dismissForeverPeriod}
            className="w-full text-center text-sm text-qgray hover:text-qblack py-2"
          >
            Bir daha gösterme
          </button>
        </div>
      </div>
    </div>
  );
}
