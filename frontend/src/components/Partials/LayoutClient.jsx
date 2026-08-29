"use client";
import { useEffect, useMemo } from "react";
import { usePathname } from "next/navigation";
import { useDispatch, useSelector } from "react-redux";
import dynamic from "next/dynamic";
const DiscountBanner = dynamic(() => import("../DiscountBanner"), { ssr: false });
import Drawer from "../Mobile/Drawer";
import Footer from "./Footers/Footer";
import Header from "./Headers/Header";
import SecondHandMessagesDock from "@/components/SecondHand/SecondHandMessagesDock";
import { CSS_CLASSES } from "../../utils/layoutConstants";
import {
  useWebsiteSetup,
  useCurrencyManagement,
  useSubscriptionBanner,
  useDrawer,
} from "../../hooks/useLayout";
import auth from "@/utils/auth";
import { setWishlistData } from "@/redux/features/wishlist/wishlistSlice";
import { setupAction } from "@/redux/features/websiteSetup/websiteSetupSlice";
import {
  useLazyGetWishlistItemsApiQuery,
  useLazyCompareListApiQuery,
} from "@/redux/features/product/apiSlice";
import { setCompareProducts } from "@/redux/features/compareProduct/compareProductSlice";
import applyGoogleTranslateDOMPatch from "@/utils/google-translate-fix";
import {
  persistWebsiteSetupStorage,
  seedLanguageStorage,
} from "@/utils/websiteSetupBootstrap";

export default function LayoutClient({ children, childrenClasses, websiteSetupData, isSecondHandSite = false }) {
  // Redux state
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const dispatch = useDispatch();

  // Write language to localStorage IMMEDIATELY (before any child renders)
  // This prevents the race condition where children call ServeLangItem()
  // before useEffect populates localStorage
  seedLanguageStorage(websiteSetupData?.language);

  useEffect(() => {
    applyGoogleTranslateDOMPatch();
  }, []);

  useEffect(() => {
    if (!websiteSetupData) return;

    dispatch(setupAction(websiteSetupData));
    persistWebsiteSetupStorage(websiteSetupData);
  }, [websiteSetupData, dispatch]);

  // Website setup management
  const {
    settings,
    contact,
    languages,
    defaultLanguage,
    allCurrencies,
  } = useWebsiteSetup(websiteSetup);

  // Currency management
  const {
    defaultCurrency,
    toggleCurrency,
    setToggleCurrency,
    handleCurrencyChange,
  } = useCurrencyManagement();

  // Subscription banner management
  const { subscribeData } = useSubscriptionBanner(websiteSetup);

  // Mobile drawer management
  const { drawer, handleDrawerToggle } = useDrawer();
  const pathname = usePathname();
  const showSecondHandDock =
    !isSecondHandSite &&
    typeof pathname === "string" &&
    pathname.startsWith("/profile");

  const processedLanguages = useMemo(() => {
    if (!languages || languages.length === 0) return [];

    return languages.map((language) => ({
      lang_code: language.lang_code,
      lang_name: language.lang_name,
    }));
  }, [languages]);

  const topBarProps = useMemo(
    () => ({
      defaultCurrency,
      allCurrency: allCurrencies,
      toggleCurrency,
      toggleHandler: setToggleCurrency,
      handler: handleCurrencyChange,
    }),
    [
      defaultCurrency,
      allCurrencies,
      toggleCurrency,
      setToggleCurrency,
      handleCurrencyChange,
    ]
  );

  const [getWishlistItemsApi] = useLazyGetWishlistItemsApiQuery();

  const getWishlistItemsSuccessHandler = (data, statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      dispatch(setWishlistData(data));
    }
  };

  const getWishlistItems = async () => {
    const userToken = auth()?.access_token;
    const data = {
      token: userToken,
      success: getWishlistItemsSuccessHandler,
    };
    await getWishlistItemsApi(data);
  };

  const [compareListApi] = useLazyCompareListApiQuery();

  const getCompareItems = async () => {
    const userToken = auth()?.access_token;
    const result = await compareListApi({
      token: userToken,
    });
    if (result.status === "fulfilled") {
      dispatch(setCompareProducts(result?.data));
    }
  };

  useEffect(() => {
    if (auth()) {
      getWishlistItems();
      getCompareItems();
    }
  }, []);

  return (
    <>
      <Drawer open={drawer} action={handleDrawerToggle} isSecondHandSite={isSecondHandSite} />
      <div className={CSS_CLASSES.LAYOUT_CONTAINER}>
        <Header
          topBarProps={topBarProps}
          contact={contact}
          settings={settings}
          drawerAction={handleDrawerToggle}
          languagesApi={processedLanguages}
          defaultLanguage={defaultLanguage}
          isSecondHandSite={isSecondHandSite}
        />
        <main
          className={`${CSS_CLASSES.MAIN_CONTENT} ${
            childrenClasses || CSS_CLASSES.DEFAULT_PADDING
          }`}
        >
          {children}
        </main>
        {showSecondHandDock ? <SecondHandMessagesDock /> : null}
        {isSecondHandSite ? null : <DiscountBanner datas={subscribeData} />}
        <Footer settings={settings} isSecondHandSite={isSecondHandSite} />
      </div>
    </>
  );
}
