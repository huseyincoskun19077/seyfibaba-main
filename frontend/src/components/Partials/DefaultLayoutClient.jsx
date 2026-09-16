"use client";
import { usePathname } from "next/navigation";
import { useEffect, useState, useCallback } from "react";
import { useDispatch, useSelector } from "react-redux";
import settings from "@/utils/settings";
import { setupAction } from "@/redux/features/websiteSetup/websiteSetupSlice";
import { useGetDefaultSetupQuery } from "@/redux/features/websiteSetup/apiSlice";
import { STORAGE_KEYS } from "@/utils/layoutConstants";
import { persistWebsiteSetupStorage } from "@/utils/websiteSetupBootstrap";
import {
  hasAnalyticsConsent,
  hasMarketingConsent,
} from "@/components/Helpers/Consent";

import MaintenanceWrapper from "@/components/Partials/MaintenanceWrapper";
import Consent from "../Helpers/Consent";
import GoogleTagManager from "./LayoutHelpers/GoogleTagManager";
import AuthenticationModal from "./LayoutHelpers/AuthenticationModal";
import SimpleFlyingCart from "../Helpers/SimpleFlyingCart";
import FixedCartButton from "../Helpers/FixedCartButton";
import ScrollToTop from "../Helpers/ScrollToTop";
import ChatWidget from "../ChatWidget";

function syncGoogleConsent({ marketing, analytics }) {
  if (typeof window === "undefined" || typeof window.gtag !== "function") return;
  window.gtag("consent", "update", {
    ad_storage: marketing ? "granted" : "denied",
    ad_user_data: marketing ? "granted" : "denied",
    ad_personalization: marketing ? "granted" : "denied",
    analytics_storage: analytics ? "granted" : "denied",
  });
}

export default function DefaultLayoutClient({ children }) {
  const [gtagId, setGtagId] = useState(null);
  const [fbPixel, setFbPixel] = useState(null);
  const [messageWidget, setMessageWidget] = useState(null);
  const [allowMarketing, setAllowMarketing] = useState(false);
  const [allowAnalytics, setAllowAnalytics] = useState(false);

  const pathname = usePathname();
  const dispatch = useDispatch();
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const { text_direction } = settings();
  const shouldFetchFallbackSetup = pathname?.startsWith("/callback/") ?? false;

  const { data: fallbackSetupData, isLoading: siteLoading } =
    useGetDefaultSetupQuery(undefined, {
      skip: !shouldFetchFallbackSetup,
    });
  const websiteSetupData = websiteSetup?.payload || fallbackSetupData;

  const refreshConsentFlags = useCallback(() => {
    const marketing = hasMarketingConsent();
    const analytics = hasAnalyticsConsent();
    setAllowMarketing(marketing);
    setAllowAnalytics(analytics);
    syncGoogleConsent({ marketing, analytics });
  }, []);

  useEffect(() => {
    refreshConsentFlags();
    const onPrefs = () => refreshConsentFlags();
    window.addEventListener("seyfibaba:cookie-prefs", onPrefs);
    return () => window.removeEventListener("seyfibaba:cookie-prefs", onPrefs);
  }, [refreshConsentFlags]);

  const initializeMessageWidget = useCallback(
    (pusherInfo) => {
      if (typeof window === "undefined") return;

      const hasDefaults =
        localStorage.getItem(STORAGE_KEYS.LEGACY_LANGUAGE) &&
        localStorage.getItem(STORAGE_KEYS.CURRENCY);

      if (hasDefaults && pusherInfo && !messageWidget) {
        setMessageWidget(pusherInfo);
      }
    },
    [messageWidget]
  );

  const processWebsiteSetup = useCallback(
    (data) => {
      const { pusher_info, googleAnalytic, facebookPixel } = data;

      dispatch(setupAction(data));
      persistWebsiteSetupStorage(data);

      setGtagId(googleAnalytic?.analytic_id || null);
      setFbPixel(facebookPixel);
      initializeMessageWidget(pusher_info);
    },
    [dispatch, initializeMessageWidget]
  );

  const initializeFacebookPixel = useCallback(async () => {
    if (!allowMarketing) return;
    if (!fbPixel || !fbPixel.app_id || fbPixel.app_id.length < 10 || !/^\d+$/.test(fbPixel.app_id)) {
      return;
    }

    try {
      const ReactPixel = (await import("react-facebook-pixel")).default;
      ReactPixel.init(fbPixel.app_id);
      ReactPixel.pageView();
    } catch {
      // silent
    }
  }, [fbPixel, allowMarketing]);

  const trackFacebookPixelPageView = useCallback(async () => {
    if (!allowMarketing) return;
    if (
      !fbPixel ||
      !fbPixel.app_id ||
      fbPixel.app_id.length < 10 ||
      !/^\d+$/.test(fbPixel.app_id) ||
      typeof window === "undefined"
    ) {
      return;
    }

    try {
      const ReactPixel = (await import("react-facebook-pixel")).default;
      ReactPixel.pageView();
    } catch {
      // silent
    }
  }, [fbPixel, allowMarketing]);

  useEffect(() => {
    if (!websiteSetupData || siteLoading) return;
    processWebsiteSetup(websiteSetupData);
  }, [websiteSetupData, siteLoading, processWebsiteSetup]);

  useEffect(() => {
    initializeFacebookPixel();
  }, [initializeFacebookPixel]);

  useEffect(() => {
    trackFacebookPixelPageView();
  }, [pathname, trackFacebookPixelPageView]);

  useEffect(() => {
    const html = document.getElementsByTagName("html");
    if (html[0]) {
      html[0].dir = text_direction;
    }
  }, [text_direction]);

  // AW-/G- etiketi root layout <head> içinde (Google doğrulaması için).
  // GTM container ayrı yüklenir.
  const useClientGtm = Boolean(gtagId && String(gtagId).startsWith("GTM-"));

  return (
    <>
      {useClientGtm ? <GoogleTagManager gTagId={gtagId} /> : null}
      <Consent />

      <main id="main-content">
        <MaintenanceWrapper>{children}</MaintenanceWrapper>
      </main>

      <ChatWidget />
      <AuthenticationModal />
      <SimpleFlyingCart />
      <FixedCartButton />
      <ScrollToTop />
    </>
  );
}
