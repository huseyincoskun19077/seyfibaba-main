"use client";
import Image from "next/image";
import Link from "next/link";
import { useEffect, useState } from "react";
import { useSelector } from "react-redux";
import ThinBag from "../../../Helpers/icons/ThinBag";
import Middlebar from "./Middlebar";
import Navbar from "./Navbar";
import TopBar from "./TopBar";
import { getProductImageProps } from "@/utils/productImage";
import useAuthSession from "@/hooks/useAuthSession";
import { useBuyerNotificationsApiQuery } from "@/redux/features/auth/apiSlice";
import SecondHandSiteNavLink from "@/components/SecondHand/SecondHandSiteNavLink";
import { marketplaceProfileUrl, marketplaceUrl } from "@/utils/secondHandSite";
import { marketplaceLoginHref } from "@/utils/auth";

export default function Header({
  topBarProps,
  drawerAction,
  settings,
  contact,
  languagesApi,
  defaultLanguage,
  isSecondHandSite = false,
}) {
  const { cart } = useSelector((state) => state.cart);
  const [cartItems, setCartItems] = useState([]);

  useEffect(() => {
    if (cart?.cartProducts) {
      setCartItems(cart.cartProducts);
    }
  }, [cart]);

  const cartItemsCount = cartItems.length;
  const session = useAuthSession();
  const isLoggedIn = !!session;
  const { data: buyerNotifications } = useBuyerNotificationsApiQuery(
    { token: session?.access_token, perPage: 1 },
    { skip: !session?.access_token, pollingInterval: 60000 }
  );
  const unreadNotificationCount = buyerNotifications?.unread_count || 0;

  const profileHref = isSecondHandSite
    ? marketplaceUrl("/profile")
    : "/profile";
  const loginHref = isSecondHandSite
    ? marketplaceUrl(marketplaceLoginHref())
    : "/login";
  const notifyHref = isLoggedIn
    ? isSecondHandSite
      ? marketplaceUrl("/profile#notifications")
      : "/profile#notifications"
    : loginHref;

  return (
    <header className="header-section-wrapper relative print:hidden">
      {isSecondHandSite ? (
        <div className="hidden lg:block">
          <TopBar
            languagesApi={languagesApi}
            defaultLanguage={defaultLanguage}
            topBarProps={topBarProps}
            contact={contact}
            className="quomodo-shop-top-bar"
          />
        </div>
      ) : (
        <TopBar
          languagesApi={languagesApi}
          defaultLanguage={defaultLanguage}
          topBarProps={topBarProps}
          contact={contact}
          className="quomodo-shop-top-bar"
        />
      )}

      <Middlebar
        settings={settings}
        isSecondHandSite={isSecondHandSite}
        className="quomodo-shop-middle-bar lg:block hidden"
      />

      {/* Mobile Header */}
      <div
        className="quomodo-shop-drawer lg:hidden block w-full bg-white border-b border-gray-100 sticky top-0 z-40"
        style={{ paddingTop: "max(0px, env(safe-area-inset-top))" }}
      >
        <div className="flex h-[56px] w-full items-center justify-between px-3 gap-2">
          <button
            type="button"
            onClick={drawerAction}
            className="-ml-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-qblack transition-colors hover:bg-gray-100 active:bg-gray-200"
            aria-label="Menüyü aç"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-7 w-7"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              strokeWidth="2"
            >
              <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h7" />
            </svg>
          </button>

          <div className="min-w-0 flex-1 h-full flex items-center">
            <Link href="/" className="flex min-w-0 items-center gap-2">
              {settings?.logo && (
                <Image
                  width={153}
                  height={44}
                  className={`${isSecondHandSite ? "w-[120px] h-[34px]" : "w-[153px] h-[44px]"} object-contain`}
                  {...getProductImageProps(settings.logo)}
                  alt="Seyfibaba Logo"
                  priority
                />
              )}
              {isSecondHandSite ? (
                <span className="shrink-0 rounded-full bg-qyellow px-2 py-0.5 text-[10px] font-800 text-qblack">
                  İkinci El
                </span>
              ) : null}
            </Link>
          </div>

          <div className="flex shrink-0 items-center gap-1.5">
            {isSecondHandSite ? (
              <Link
                href={marketplaceProfileUrl("second-hand-add")}
                className="h-9 px-2.5 inline-flex items-center justify-center rounded-lg bg-qyellow text-qblack text-[11px] font-800"
              >
                İlan ver
              </Link>
            ) : (
              <SecondHandSiteNavLink
                className="h-10 px-3 inline-flex items-center justify-center rounded-xl bg-qyellow text-qblack text-xs font-800 shadow-sm ring-1 ring-amber-900/10 hover:brightness-95 transition"
                ariaLabel="İkinci el al sat"
              >
                İkinci el
              </SecondHandSiteNavLink>
            )}

            <Link
              href={isLoggedIn ? profileHref : loginHref}
              className="relative h-10 w-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-qblack"
              aria-label={isLoggedIn ? "Hesabım (giriş yapıldı)" : "Hesabım"}
            >
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden>
                <path d="M20 21a8 8 0 10-16 0" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                <path d="M12 12a4 4 0 100-8 4 4 0 000 8z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
              {isLoggedIn && (
                <span className="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-green-600 ring-2 ring-white" aria-hidden />
              )}
            </Link>

            {isSecondHandSite ? null : (
              <>
            <Link
              href={notifyHref}
              className="relative h-10 w-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-qblack shadow-sm hover:bg-gray-50 transition"
              aria-label="Bildirimler"
              title="Bildirimler"
            >
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
              </svg>
              {unreadNotificationCount > 0 && (
                <span className="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[9px] font-bold flex items-center justify-center">
                  {unreadNotificationCount > 99 ? "99+" : unreadNotificationCount}
                </span>
              )}
            </Link>
            <div className="cart relative cursor-pointer">
              <Link href="/cart" aria-label="Sepetim" className="h-10 w-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white shadow-sm hover:bg-gray-50 transition">
                <ThinBag />
              </Link>
              <span className="w-[18px] h-[18px] rounded-full bg-qyellow absolute -top-2 -right-2 flex justify-center items-center text-[9px] font-700">
                {cartItemsCount}
              </span>
            </div>
              </>
            )}
          </div>
        </div>
        {isSecondHandSite ? (
          <form action="/ikinci-el" method="get" className="px-3 pb-2.5">
            <div className="flex h-10 w-full overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
              <input
                type="search"
                name="q"
                placeholder="İlan, marka veya şehir ara…"
                className="min-w-0 flex-1 bg-transparent px-3 text-sm text-qblack outline-none"
              />
              <button type="submit" className="px-3 text-xs font-800 text-qblack bg-qyellow">
                Ara
              </button>
            </div>
          </form>
        ) : null}
      </div>

      <Navbar
        className="quomodo-shop-nav-bar lg:block hidden"
        isSecondHandSite={isSecondHandSite}
      />
    </header>
  );
}
