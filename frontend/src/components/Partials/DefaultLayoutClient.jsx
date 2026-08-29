"use client";
import { usePathname } from "next/navigation";
import { useEffect, useState, useCallback } from "react";
import { useDispatch, useSelector } from "react-redux";
import settings from "@/utils/settings";
import { setupAction } from "@/redux/features/websiteSetup/websiteSetupSlice";
import { useGetDefaultSetupQuery } from "@/redux/features/websiteSetup/apiSlice";
import { STORAGE_KEYS } from "@/utils/layoutConstants";
import { persistWebsiteSetupStorage } from "@/utils/websiteSetupBootstrap";

import MaintenanceWrapper from "@/components/Partials/MaintenanceWrapper";
import Consent from "../Helpers/Consent";
import GoogleTagManager from "./LayoutHelpers/GoogleTagManager";
import AuthenticationModal from "./LayoutHelpers/AuthenticationModal";
import SimpleFlyingCart from "../Helpers/SimpleFlyingCart";
import FixedCartButton from "../Helpers/FixedCartButton";
import ScrollToTop from "../Helpers/ScrollToTop";
import ChatWidget from "../ChatWidget";

export default function DefaultLayoutClient({ children }) {
  const [gtagId, setGtagId] = useState(null);
  const [fbPixel, setFbPixel] = useState(null);
  const [messageWidget, setMessageWidget] = useState(null);

  const pathname = usePathname();
  const dispatch = useDispatch();
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const { text_direction } = settings();
  const shouldFetchFallbackSetup = pathname?.startsWith("/callback/") ?? false;

  // Use server-bootstrapped setup from Redux when available.
  // Only fall back to a client query for callback routes that do not mount the website shell.
  const { data: fallbackSetupData, isLoading: siteLoading } =
    useGetDefaultSetupQuery(undefined, {
      skip: !shouldFetchFallbackSetup,
    });
  const websiteSetupData = websiteSetup?.payload || fallbackSetupData;

  /**
   * Initializes message widget if conditions are met
   */
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

  /**
   * Processes website setup data and initializes all necessary configurations
   */
  const processWebsiteSetup = useCallback(
    (data) => {
      const {
        pusher_info,
        googleAnalytic,
        facebookPixel,
      } = data;

      // Keep Redux in sync, then persist the same setup payload locally.
      dispatch(setupAction(data));
      persistWebsiteSetupStorage(data);

      // Set state values
      setGtagId(googleAnalytic?.analytic_id);
      setFbPixel(facebookPixel);

      // Initialize message widget
      initializeMessageWidget(pusher_info);
    },
    [dispatch, initializeMessageWidget]
  );

  /**
   * Initializes Facebook Pixel
   */
  const initializeFacebookPixel = useCallback(async () => {
    if (!fbPixel || !fbPixel.app_id || fbPixel.app_id.length < 10 || !/^\d+$/.test(fbPixel.app_id)) return;

    try {
      const ReactPixel = (await import("react-facebook-pixel")).default;
      ReactPixel.init(fbPixel.app_id);
      ReactPixel.pageView();
    } catch (error) {
      // Facebook Pixel init silently failed
    }
  }, [fbPixel]);

  /**
   * Tracks page views for Facebook Pixel on route changes
   */
  const trackFacebookPixelPageView = useCallback(async () => {
    if (!fbPixel || !fbPixel.app_id || fbPixel.app_id.length < 10 || !/^\d+$/.test(fbPixel.app_id) || typeof window === "undefined") return;

    try {
      const ReactPixel = (await import("react-facebook-pixel")).default;
      ReactPixel.pageView();
    } catch (error) {
      // Facebook Pixel pageView silently failed
    }
  }, [fbPixel]);

  // Process website setup data
  useEffect(() => {
    if (!websiteSetupData || siteLoading) return;
    processWebsiteSetup(websiteSetupData);
  }, [websiteSetupData, siteLoading, processWebsiteSetup]);

  // Initialize Facebook Pixel
  useEffect(() => {
    initializeFacebookPixel();
  }, [initializeFacebookPixel]);

  // Track route changes for Facebook Pixel
  useEffect(() => {
    trackFacebookPixelPageView();
  }, [pathname, trackFacebookPixelPageView]);

  // Set text direction
  useEffect(() => {
    const html = document.getElementsByTagName("html");
    if (html[0]) {
      html[0].dir = text_direction;
    }
  }, [text_direction]);

  return (
    <>
      {/* Google Tag Manager */}
      {gtagId && <GoogleTagManager gTagId={gtagId} />}
      {/* Cookie Consent */}
      <Consent />

      {/* Main Content */}
      <main id="main-content">
        <MaintenanceWrapper>{children}</MaintenanceWrapper>
      </main>

      {/* AI Chat Widget */}
      <ChatWidget />

      {/* Authentication Modal */}
      <AuthenticationModal />

      {/* Simple Flying Cart Animation */}
      <SimpleFlyingCart />

      {/* Fixed Cart Button */}
      <FixedCartButton />

      {/* Scroll to Top */}
      <ScrollToTop />
    </>
  );
}
