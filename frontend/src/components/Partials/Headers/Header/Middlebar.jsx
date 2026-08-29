"use client";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import Cart from "../../../Cart";
import Compair from "../../../Helpers/icons/Compair";
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

export default function Middlebar({ className, settings, isSecondHandSite = false }) {
  const router = useRouter();
  const dispatch = useDispatch();

  // Redux selectors
  const { wishlistData } = useSelector((state) => state.wishlistData);
  const { compareProducts } = useSelector((state) => state.compareProducts);
  const { cart } = useSelector((state) => state.cart);

  // Local state
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

  // Derived values
  const wishlists = wishlistData?.wishlists;
  const compareProductsCount = compareProducts?.products?.length || 0;
  const wishlistCount = wishlists?.length || 0;
  const cartItemsCount = cartItems.length;

  // Update cart items when cart changes
  useEffect(() => {
    if (cart?.cartProducts) {
      setCartItems(cart.cartProducts);
    }
  }, [cart]);

  // Toggle profile dropdown
  const toggleProfile = () => {
    setProfile(!profile);
  };

  /**
   * Handles user logout functionality
   * @Initialization Logout Api @const logoutApi
   * @func logoutSuccessHandler @param data @param statusCode
   * @func logout
   */
  const [logoutApi, { isLoading: isLogoutLoading }] = useLazyLogoutApiQuery();

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
      // for force logout
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
    <div className={`w-full h-[86px] bg-white ${className}`}>
      <div className="container-x mx-auto h-full">
        <div className="relative h-full">
          <div className="flex justify-between items-center h-full">
            {/* Logo Section */}
            <div className="relative flex items-center gap-3">
              <Link href="/">
                {settings?.logo && (
                  <Image
                    width={153}
                    height={44}
                    className="w-[153px] h-[44px] object-contain"
                    {...getProductImageProps(settings.logo)}
                    alt="Seyfibaba Logo"
                    priority
                  />
                )}
              </Link>
              {isSecondHandSite ? (
                <span className="hidden xl:inline-flex h-7 items-center rounded-full bg-qyellow px-2.5 text-[11px] font-800 text-qblack">
                  İkinci El
                </span>
              ) : null}
            </div>

            {/* Search Box — Desktop */}
            <div className="w-[517px] h-[44px] hidden lg:block">
              {isSecondHandSite ? (
                <form action="/ikinci-el" method="get" className="w-full h-full">
                  <div className="w-full h-full flex items-center border border-qgray-border bg-white overflow-hidden rounded">
                    <input
                      type="search"
                      name="q"
                      defaultValue=""
                      placeholder="İlan ara..."
                      className="flex-1 h-full px-4 text-sm outline-none bg-transparent"
                    />
                    <button
                      type="submit"
                      className="h-full px-4 text-sm font-700 text-qblack bg-qyellow hover:brightness-95"
                    >
                      Ara
                    </button>
                  </div>
                </form>
              ) : (
                <SearchBox className="search-com" />
              )}
            </div>

            {/* Mobile Search Icon */}
            <button
              type="button"
              onClick={() => setMobileSearch(!mobileSearch)}
              className="lg:hidden text-qblack"
              aria-label="Ara"
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />
              </svg>
            </button>

            {/* Right Side Icons & Profile */}
            <div className="flex space-x-6 rtl:space-x-reverse items-center relative">
              {isSecondHandSite ? (
                <Link
                  href={marketplaceProfileUrl("second-hand-add")}
                  className="h-10 px-4 inline-flex items-center justify-center rounded-xl bg-qyellow text-qblack text-sm font-800 shadow-sm ring-1 ring-amber-900/10 hover:brightness-95"
                >
                  İlan ver
                </Link>
              ) : (
                <>
              {/* Compare Products */}
              <div className="compare relative">
                <Link
                  href={authUser ? "/products-compare" : "/login"}
                  aria-label={ServeLangItem()?.Compare || "Karşılaştır"}
                >
                  <span className="cursor-pointer">
                    <Compair className="fill-current" />
                  </span>
                </Link>
                <span className="w-[18px] h-[18px] rounded-full absolute -top-2.5 -right-2.5 flex justify-center items-center text-[9px]">
                  {compareProductsCount}
                </span>
              </div>

              {/* Wishlist */}
              <div className="favorite relative">
                <Link
                  href={authUser ? "/wishlist" : "/login"}
                  aria-label={ServeLangItem()?.Wishlist || "Favorilerim"}
                >
                  <span className="cursor-pointer">
                    <ThinLove className="fill-current" />
                  </span>
                </Link>
                <span className="w-[18px] h-[18px] rounded-full absolute -top-2.5 -right-2.5 flex justify-center items-center text-[9px]">
                  {wishlistCount}
                </span>
              </div>
                </>
              )}

              {/* Notifications */}
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
                >
                  <span className="cursor-pointer text-qblack">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
                      <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                      <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                    </svg>
                  </span>
                </Link>
                <span className="w-[18px] h-[18px] rounded-full absolute -top-2.5 -right-2.5 flex justify-center items-center text-[9px] bg-qyellow text-qblack font-bold">
                  {unreadNotificationCount}
                </span>
              </div>

              {isSecondHandSite ? null : (
              <div className="cart-wrapper group relative py-4">
                <div className="cart relative cursor-pointer">
                  <Link 
                    href="/cart"
                    aria-label={ServeLangItem()?.Cart || "Sepetim"}
                  >
                    <span className="cursor-pointer">
                      <ThinBag />
                    </span>
                  </Link>
                  <span className="w-[18px] h-[18px] rounded-full absolute -top-2.5 -right-2.5 flex justify-center items-center text-[9px]">
                    {cartItemsCount}
                  </span>
                </div>
                <Cart className="absolute ltr:-right-[45px] rtl:-left-[45px] top-11 z-50 hidden group-hover:block" />
              </div>
              )}

              {/* User Profile */}
              <div>
                {authUser ? (
                  <button onClick={toggleProfile} type="button">
                    <span className="text-qblack font-bold text-sm block">
                      {authUser?.user?.name}
                    </span>
                    <span className="text-qgray font-medium text-sm block">
                      {authUser?.user?.phone}
                    </span>
                  </button>
                ) : (
                  <Link
                    href={isSecondHandSite ? marketplaceUrl(marketplaceLoginHref()) : "/login"}
                    aria-label={ServeLangItem()?.Login || "Giriş Yap"}
                  >
                    <span className="cursor-pointer">
                      <ThinPeople />
                    </span>
                  </Link>
                )}
              </div>

              {/* Profile Dropdown */}
              {profile && authUser && (
                <>
                  {/* Backdrop */}
                  <div
                    onClick={() => setProfile(false)}
                    className="w-full h-full fixed top-0 left-0 z-30"
                    style={{ zIndex: "35", margin: "0" }}
                  ></div>

                  {/* Dropdown Menu */}
                  <div
                    className="w-[220px] bg-white absolute right-0 top-11 z-40 border-t-[3px] primary-border flex flex-col"
                    style={{
                      boxShadow: "0px 15px 50px 0px rgba(0, 0, 0, 0.14)",
                    }}
                  >
                    {/* Menu Items */}
                    <div className="menu-item-area w-full px-5 pt-5 pb-4">
                      <ul className="w-full flex flex-col space-y-5">
                        <li className="text-base text-qgraytwo font-medium">
                          <span>
                            {ServeLangItem()?.Hi}, {authUser?.user?.name}
                          </span>
                        </li>
                        <li className="text-base text-qgraytwo cursor-pointer hover:text-qblack hover:font-semibold">
                          <Link href={isSecondHandSite ? marketplaceUrl("/profile#dashboard") : "/profile#dashboard"} onClick={() => setProfile(false)}>
                            <span className="capitalize">
                              {ServeLangItem()?.profile}
                            </span>
                          </Link>
                        </li>
                        <li className="text-base text-qgraytwo cursor-pointer hover:text-qblack hover:font-semibold">
                          <Link href={isSecondHandSite ? marketplaceUrl("/contact") : "/contact"} onClick={() => setProfile(false)}>
                            <span className="capitalize">
                              {ServeLangItem()?.Support}
                            </span>
                          </Link>
                        </li>
                        <li className="text-base text-qgraytwo cursor-pointer hover:text-qblack hover:font-semibold">
                          <Link href={isSecondHandSite ? marketplaceUrl("/faq") : "/faq"} onClick={() => setProfile(false)}>
                            <span className="capitalize">
                              {ServeLangItem()?.FAQ}
                            </span>
                          </Link>
                        </li>
                      </ul>
                    </div>

                    {/* Logout Button */}
                    <div className="w-full h-[50px] flex justify-center items-center border-t border-qgray-border">
                      <button
                        onClick={logout}
                        type="button"
                        className="text-qblack text-base font-semibold hover:text-qred transition-colors"
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

      {/* Mobile Search Overlay */}
      {mobileSearch && (
        <div className="lg:hidden absolute left-0 right-0 top-full z-50 bg-white shadow-lg border-t p-3">
          {isSecondHandSite ? (
            <form action="/ikinci-el" method="get">
              <div className="w-full h-[44px] flex items-center border border-qgray-border bg-white overflow-hidden rounded">
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
