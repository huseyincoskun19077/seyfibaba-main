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
    <header className="header-section-wrapper relative print:hidden bg-white shadow-[0_2px_16px_rgba(4,51,74,0.06)]">
      {isSecondHandSite ? (
        <div className="hidden lg:block">
          <TopBar
            settings={settings}
            contact={contact}
            className="quomodo-shop-top-bar"
          />
        </div>
      ) : (
        <TopBar
          settings={settings}
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
        className="quomodo-shop-drawer lg:hidden block w-full bg-white border-b border-[#04334a]/10 sticky top-0 z-40 shadow-sm"
        style={{ paddingTop: "max(0px, env(safe-area-inset-top))" }}
      >
        <div className="flex h-[56px] w-full items-center justify-between px-3 gap-2">
          <button
            type="button"
            onClick={drawerAction}
            className="-ml-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-qblack transition-colors hover:bg-qblack/[0.05] active:bg-qblack/[0.08]"
            aria-label="Menüyü aç"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-6 w-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              strokeWidth="2"
            >
              <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h7" />
            </svg>
          </button>

          <div className="min-w-0 flex-1 h-full flex items-center justify-center">
            <Link href="/" className="flex min-w-0 items-center gap-2">
              {settings?.logo && (
                <Image
                  width={140}
                  height={40}
                  className={`${isSecondHandSite ? "w-[110px] h-[32px]" : "w-[140px] h-[40px]"} object-contain`}
                  {...getProductImageProps(settings.logo)}
                  alt="Kuaför Tedarik Logo"
                  priority
                />
              )}
              {isSecondHandSite ? (
                <span className="shrink-0 rounded-md bg-qyellow px-2 py-0.5 text-[10px] font-800 text-qblack">
                  İkinci El
                </span>
              ) : null}
            </Link>
          </div>

          <div className="flex shrink-0 items-center gap-1">
            {isSecondHandSite ? (
              <Link
                href={marketplaceProfileUrl("second-hand-add")}
                className="h-9 px-2.5 inline-flex items-center justify-center rounded-lg bg-qyellow text-qblack text-[11px] font-800"
              >
                İlan ver
              </Link>
            ) : null}

            <Link
              href={isLoggedIn ? profileHref : loginHref}
              className="relative h-10 w-10 inline-flex items-center justify-center rounded-xl text-qblack hover:bg-qblack/[0.05] transition"
              aria-label={isLoggedIn ? "Hesabım (giriş yapıldı)" : "Hesabım"}
            >
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden>
                <path d="M20 21a8 8 0 10-16 0" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                <path d="M12 12a4 4 0 100-8 4 4 0 000 8z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
              {isLoggedIn && (
                <span className="absolute top-1.5 right-1.5 h-2.5 w-2.5 rounded-full bg-qyellow ring-2 ring-white" aria-hidden />
              )}
            </Link>

            {isSecondHandSite ? null : (
              <>
            <Link
              href={notifyHref}
              className="relative h-10 w-10 inline-flex items-center justify-center rounded-xl text-qblack hover:bg-qblack/[0.05] transition"
              aria-label="Bildirimler"
              title="Bildirimler"
            >
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
              </svg>
              {unreadNotificationCount > 0 && (
                <span className="absolute top-1 right-1 min-w-[16px] h-[16px] px-0.5 rounded-full bg-qyellow text-qblack text-[9px] font-bold flex items-center justify-center">
                  {unreadNotificationCount > 99 ? "99+" : unreadNotificationCount}
                </span>
              )}
            </Link>
            <div className="cart relative cursor-pointer">
              <Link href="/cart" aria-label="Sepetim" className="h-10 w-10 inline-flex items-center justify-center rounded-xl text-qblack hover:bg-qblack/[0.05] transition">
                <ThinBag />
              </Link>
              <span className="min-w-[16px] h-[16px] px-0.5 rounded-full bg-qyellow absolute top-1 right-1 flex justify-center items-center text-[9px] font-700 text-qblack">
                {cartItemsCount}
              </span>
            </div>
              </>
            )}
          </div>
        </div>
        {isSecondHandSite ? (
          <form action="/ikinci-el" method="get" className="px-3 pb-2.5">
            <div className="flex h-10 w-full overflow-hidden rounded-xl border-2 border-qblack/10 bg-white">
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
        ) : (
          <form
            action="/search"
            method="get"
            className="px-3 pb-2.5"
            onSubmit={(e) => {
              const q = e.currentTarget.search?.value?.trim();
              if (!q) {
                e.preventDefault();
              }
            }}
          >
            <div className="flex h-10 w-full overflow-hidden rounded-xl border-2 border-qblack/10 bg-white focus-within:border-qyellow transition-colors">
              <input
                type="search"
                name="search"
                placeholder="Ürün, marka veya kategori ara…"
                className="min-w-0 flex-1 bg-transparent px-3 text-sm text-qblack outline-none"
              />
              <button type="submit" className="px-4 text-xs font-800 text-qblack bg-qyellow">
                Ara
              </button>
            </div>
          </form>
        )}
      </div>

      <Navbar
        className="quomodo-shop-nav-bar lg:block hidden"
        isSecondHandSite={isSecondHandSite}
      />
    </header>
  );
}
