"use client";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import Cart from "../../../Cart";
import ThinBag from "../../../Helpers/icons/ThinBag";
import ThinLove from "../../../Helpers/icons/ThinLove";
import ThinPeople from "../../../Helpers/icons/ThinPeople";
import SearchBox from "../../../Helpers/SearchBox";
import ServeLangItem from "../../../Helpers/ServeLangItem";
import { getProductImageProps } from "@/utils/productImage";
import { clearAccessTokenCookie, marketplaceLoginHref } from "@/utils/auth";
import { marketplaceProfileUrl, marketplaceUrl } from "@/utils/secondHandSite";
import useAuthSession from "@/hooks/useAuthSession";
import { setWishlistData } from "@/redux/features/wishlist/wishlistSlice";
import { AUTH_STORAGE_SYNC_EVENT } from "@/redux/api/apiSlice";
import { useLazyLogoutApiQuery, useBuyerNotificationsApiQuery } from "@/redux/features/auth/apiSlice";
import { toast } from "react-toastify";

const iconBtnClass =
  "relative h-11 w-11 inline-flex items-center justify-center rounded-xl text-[#04334a] hover:bg-[#04334a]/[0.06] transition-colors";

const actionBtnClass =
  "relative h-11 px-2.5 inline-flex items-center gap-2 rounded-xl text-[#04334a] hover:bg-[#04334a]/[0.06] transition-colors";

const badgeClass =
  "min-w-[18px] h-[18px] px-1 rounded-full absolute -top-0.5 -right-0.5 flex justify-center items-center text-[9px] font-700 bg-qyellow text-[#04334a]";

export default function Middlebar({ className, settings, isSecondHandSite = false }) {
  const router = useRouter();
  const dispatch = useDispatch();

  const { wishlistData } = useSelector((state) => state.wishlistData);
  const { compareProducts } = useSelector((state) => state.compareProducts);
  const { cart } = useSelector((state) => state.cart);

  const [profile, setProfile] = useState(false);
  const [cartItems, setCartItems] = useState([]);
  const [mobileSearch, setMobileSearch] = useState(false);
  const authUser = useAuthSession();
  const { data: buyerNotifications } = useBuyerNotificationsApiQuery(
    {
      token: authUser?.access_token,
      perPage: 1,
    },
    {
      skip: !authUser?.access_token,
      pollingInterval: 60000,
    }
  );
  const unreadNotificationCount = buyerNotifications?.unread_count || 0;

  const wishlists = wishlistData?.wishlists;
  const compareProductsCount = compareProducts?.products?.length || 0;
  const wishlistCount = wishlists?.length || 0;
  const cartItemsCount = cartItems.length;

  useEffect(() => {
    if (cart?.cartProducts) {
      setCartItems(cart.cartProducts);
    }
  }, [cart]);

  const toggleProfile = () => {
    setProfile(!profile);
  };

  const [logoutApi] = useLazyLogoutApiQuery();

  const logoutSuccessHandler = (data, statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      dispatch(setWishlistData(null));
      toast.success(data?.notification);
      localStorage.removeItem("auth");
      clearAccessTokenCookie();
      window.dispatchEvent(new Event(AUTH_STORAGE_SYNC_EVENT));
      setProfile(false);
      router.push(isSecondHandSite ? marketplaceUrl("/login") : "/login");
    } else {
      dispatch(setWishlistData(null));
      toast.success("Cikis yapildi");
      localStorage.removeItem("auth");
      clearAccessTokenCookie();
      window.dispatchEvent(new Event(AUTH_STORAGE_SYNC_EVENT));
      setProfile(false);
      router.push(isSecondHandSite ? marketplaceUrl("/login") : "/login");
    }
  };

  const logout = async () => {
    if (authUser) {
      await logoutApi({
        token: authUser?.access_token,
        success: logoutSuccessHandler,
      });
    }
  };

  return (
    <div className={`w-full h-[84px] bg-white border-b border-[#04334a]/10 ${className}`}>
      <div className="container-x mx-auto h-full">
        <div className="relative h-full">
          <div className="flex items-center gap-5 h-full">
            <div className="relative flex items-center gap-2.5 shrink-0">
              <Link href="/" className="block">
                {settings?.logo && (
                  <Image
                    width={160}
                    height={46}
                    className="w-[160px] h-[46px] object-contain"
                    {...getProductImageProps(settings.logo)}
                    alt="Kuaför Tedarik Logo"
                    priority
                  />
                )}
              </Link>
              {isSecondHandSite ? (
                <span className="hidden xl:inline-flex h-7 items-center rounded-md bg-qyellow px-2.5 text-[11px] font-800 text-qblack">
                  İkinci El
                </span>
              ) : null}
            </div>

            <div className="flex-1 max-w-[640px] h-[48px] hidden lg:block">
              {isSecondHandSite ? (
                <form action="/ikinci-el" method="get" className="w-full h-full">
                  <div className="w-full h-full flex items-center border-2 border-qblack/10 bg-white overflow-hidden rounded-xl focus-within:border-qyellow transition-colors">
                    <input
                      type="search"
                      name="q"
                      defaultValue=""
                      placeholder="İlan ara..."
                      className="flex-1 h-full px-4 text-sm outline-none bg-transparent"
                    />
                    <button
                      type="submit"
                      className="h-full px-5 text-sm font-700 text-qblack bg-qyellow hover:brightness-95 transition"
                    >
                      Ara
                    </button>
                  </div>
                </form>
              ) : (
                <SearchBox className="search-com" />
              )}
            </div>

            <button
              type="button"
              onClick={() => setMobileSearch(!mobileSearch)}
              className={`${iconBtnClass} lg:hidden`}
              aria-label="Ara"
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />
              </svg>
            </button>

            <div className="flex items-center gap-1 sm:gap-2 ml-auto relative">
              {isSecondHandSite ? (
                <Link
                  href={marketplaceProfileUrl("second-hand-add")}
                  className="h-11 px-4 inline-flex items-center justify-center rounded-xl bg-qyellow text-qblack text-sm font-800 hover:brightness-95 transition"
                >
                  İlan ver
                </Link>
              ) : null}

              <div className="relative">
                <Link
                  href={
                    authUser
                      ? isSecondHandSite
                        ? marketplaceUrl("/profile#notifications")
                        : "/profile#notifications"
                      : isSecondHandSite
                        ? marketplaceUrl(marketplaceLoginHref())
                        : "/login"
                  }
                  aria-label="Bildirimler"
                  className={iconBtnClass}
                >
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                    <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                  </svg>
                </Link>
                {unreadNotificationCount > 0 ? (
                  <span className={badgeClass}>{unreadNotificationCount}</span>
                ) : null}
              </div>

              {!isSecondHandSite ? (
                <div className="favorite relative">
                  <Link
                    href={authUser ? "/wishlist" : "/login"}
                    aria-label={ServeLangItem()?.Wishlist || "Favorilerim"}
                    className={iconBtnClass}
                  >
                    <ThinLove className="fill-current" />
                  </Link>
                  {wishlistCount > 0 ? (
                    <span className={badgeClass}>{wishlistCount}</span>
                  ) : null}
                </div>
              ) : null}

              {isSecondHandSite ? null : (
                <div className="cart-wrapper group relative py-4">
                  <div className="cart relative cursor-pointer">
                    <Link
                      href="/cart"
                      aria-label={ServeLangItem()?.Cart || "Sepetim"}
                      className={actionBtnClass}
                    >
                      <span className="relative inline-flex">
                        <ThinBag />
                        {cartItemsCount > 0 ? (
                          <span className={badgeClass}>{cartItemsCount}</span>
                        ) : null}
                      </span>
                      <span className="hidden sm:inline text-[12px] font-800 text-[#04334a]">
                        Sepetim
                      </span>
                    </Link>
                  </div>
                  <Cart className="absolute ltr:-right-[45px] rtl:-left-[45px] top-11 z-50 hidden group-hover:block" />
                </div>
              )}

              <div className="relative group/account">
                {authUser ? (
                  <button
                    onClick={toggleProfile}
                    type="button"
                    className={actionBtnClass}
                    aria-expanded={profile}
                    aria-haspopup="true"
                  >
                    <ThinPeople />
                    <span className="hidden sm:flex flex-col items-start leading-tight text-left">
                      <span className="text-[11px] font-800 text-[#04334a]">
                        Hesabım
                      </span>
                      <span className="text-[10px] font-500 text-[#04334a]/55 max-w-[88px] truncate">
                        {authUser?.user?.name}
                      </span>
                    </span>
                  </button>
                ) : (
                  <button
                    type="button"
                    className={actionBtnClass}
                    aria-label="Hesabım"
                    aria-haspopup="true"
                  >
                    <ThinPeople />
                    <span className="hidden sm:inline text-[12px] font-800 text-[#04334a]">
                      Hesabım
                    </span>
                  </button>
                )}

                {!authUser ? (
                  <div className="invisible opacity-0 pointer-events-none group-hover/account:visible group-hover/account:opacity-100 group-hover/account:pointer-events-auto transition-opacity duration-150 absolute right-0 top-full pt-1 z-50">
                    <div
                      className="w-[220px] bg-white rounded-xl border border-[#04334a]/10 overflow-hidden p-3 flex flex-col gap-2"
                      style={{
                        boxShadow: "0px 12px 40px 0px rgba(4, 51, 74, 0.12)",
                      }}
                    >
                      <Link
                        href={isSecondHandSite ? marketplaceUrl(marketplaceLoginHref()) : "/login"}
                        className="h-10 w-full inline-flex items-center justify-center rounded-lg border border-[#04334a]/20 text-[#04334a] text-sm font-700 hover:bg-[#04334a]/[0.04] transition"
                      >
                        {ServeLangItem()?.Login || "Giriş Yap"}
                      </Link>
                      <Link
                        href={isSecondHandSite ? marketplaceUrl("/signup") : "/signup"}
                        className="h-10 w-full inline-flex items-center justify-center rounded-lg bg-[#04334a] text-white text-sm font-700 hover:brightness-110 transition"
                      >
                        {ServeLangItem()?.Sign_Up || "Üye Ol"}
                      </Link>
                    </div>
                  </div>
                ) : null}

                {profile && authUser && (
                  <>
                    <div
                      onClick={() => setProfile(false)}
                      className="w-full h-full fixed top-0 left-0 z-30"
                      style={{ zIndex: "35", margin: "0" }}
                    ></div>

                    <div
                      className="w-[260px] bg-white absolute right-0 top-12 z-40 rounded-xl border border-[#04334a]/10 overflow-hidden flex flex-col"
                      style={{
                        boxShadow: "0px 12px 40px 0px rgba(4, 51, 74, 0.12)",
                      }}
                    >
                      <div className="h-1 w-full bg-qyellow" />
                      <div className="menu-item-area w-full px-5 pt-4 pb-4">
                        <ul className="w-full flex flex-col space-y-3.5">
                          <li className="text-sm text-[#04334a]/50 font-500">
                            <span>
                              {ServeLangItem()?.Hi}, {authUser?.user?.name}
                            </span>
                          </li>
                          <li className="text-sm text-[#04334a]/80 cursor-pointer hover:text-[#04334a] hover:font-semibold">
                            <Link
                              href={isSecondHandSite ? marketplaceUrl("/profile#dashboard") : "/profile#dashboard"}
                              onClick={() => setProfile(false)}
                            >
                              <span className="capitalize">
                                {ServeLangItem()?.profile || "Profilim"}
                              </span>
                            </Link>
                          </li>
                          {!isSecondHandSite ? (
                            <>
                              <li className="text-sm text-[#04334a]/80 cursor-pointer hover:text-[#04334a] hover:font-semibold">
                                <Link href="/wishlist" onClick={() => setProfile(false)}>
                                  <span className="inline-flex items-center justify-between w-full gap-2">
                                    <span>{ServeLangItem()?.Wishlist || "Favorilerim"}</span>
                                    <span className="min-w-[20px] h-5 px-1.5 rounded-full bg-qyellow text-[#04334a] text-[10px] font-800 inline-flex items-center justify-center">
                                      {wishlistCount}
                                    </span>
                                  </span>
                                </Link>
                              </li>
                              <li className="text-sm text-[#04334a]/80 cursor-pointer hover:text-[#04334a] hover:font-semibold">
                                <Link href="/products-compare" onClick={() => setProfile(false)}>
                                  <span className="inline-flex items-center justify-between w-full gap-2">
                                    <span>{ServeLangItem()?.Compare || "Karşılaştır"}</span>
                                    <span className="min-w-[20px] h-5 px-1.5 rounded-full bg-[#04334a]/10 text-[#04334a] text-[10px] font-800 inline-flex items-center justify-center">
                                      {compareProductsCount}
                                    </span>
                                  </span>
                                </Link>
                              </li>
                            </>
                          ) : null}
                          <li className="text-sm text-[#04334a]/80 cursor-pointer hover:text-[#04334a] hover:font-semibold">
                            <Link
                              href={isSecondHandSite ? marketplaceUrl("/contact") : "/contact"}
                              onClick={() => setProfile(false)}
                            >
                              <span className="capitalize">
                                {ServeLangItem()?.Support}
                              </span>
                            </Link>
                          </li>
                          <li className="text-sm text-[#04334a]/80 cursor-pointer hover:text-[#04334a] hover:font-semibold">
                            <Link
                              href={isSecondHandSite ? marketplaceUrl("/yardim") : "/yardim"}
                              onClick={() => setProfile(false)}
                            >
                              <span className="capitalize">
                                {ServeLangItem()?.FAQ}
                              </span>
                            </Link>
                          </li>
                        </ul>
                      </div>

                      <div className="w-full h-[48px] flex justify-center items-center border-t border-[#04334a]/10 bg-[#04334a]/[0.02]">
                        <button
                          onClick={logout}
                          type="button"
                          className="text-[#04334a] text-sm font-600 hover:text-qred transition-colors"
                        >
                          {ServeLangItem()?.Sign_Out}
                        </button>
                      </div>
                    </div>
                  </>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>

      {mobileSearch && (
        <div className="lg:hidden absolute left-0 right-0 top-full z-50 bg-white shadow-lg border-t border-qblack/5 p-3">
          {isSecondHandSite ? (
            <form action="/ikinci-el" method="get">
              <div className="w-full h-[44px] flex items-center border-2 border-qblack/10 bg-white overflow-hidden rounded-xl">
                <input
                  type="search"
                  name="q"
                  placeholder="İlan ara..."
                  className="flex-1 h-full px-4 text-sm outline-none bg-transparent"
                />
                <button type="submit" className="h-full px-4 text-sm font-700 text-qblack bg-qyellow">
                  Ara
                </button>
              </div>
            </form>
          ) : (
            <SearchBox className="search-com" />
          )}
        </div>
      )}
    </div>
  );
}
