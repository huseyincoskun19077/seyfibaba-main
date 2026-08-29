"use client";
import { useEffect, useState, Suspense } from "react";
import { useRouter, usePathname, useSearchParams } from "next/navigation";
// Redux imports
import { useDispatch } from "react-redux";
// Internal utility and middleware imports
import auth, { clearAccessTokenCookie } from "../../../utils/auth";
import { setWishlistData } from "../../../redux/features/wishlist/wishlistSlice";
// Shared and helper imports
import Multivendor from "../../Shared/Multivendor";
import ServeLangItem from "../../Helpers/ServeLangItem";
import BreadcrumbCom from "../../BreadcrumbCom";
// Icon imports
import IcoAdress from "./icons/IcoAdress";
import IcoCart from "./icons/IcoCart";
import IcoDashboard from "./icons/IcoDashboard";
import IcoLogout from "./icons/IcoLogout";
import IcoLove from "./icons/IcoLove";
import IcoPassword from "./icons/IcoPassword";
import IcoPeople from "./icons/IcoPeople";
import IcoReviewHand from "./icons/IcoReviewHand";
import IcoSupport from "./icons/IcoSupport";
import IcoSecondHand from "./icons/IcoSecondHand";
// Tab component imports
import AddressesTab from "./tabs/AddressesTab";
import ReturnRequestsTab from "./tabs/ReturnRequestsTab";
import SellerOperationsTab from "./tabs/SellerOperationsTab";
import Dashboard from "./tabs/Dashboard";
import OrderTab from "./tabs/OrderTab";
import PasswordTab from "./tabs/PasswordTab";
import ProfileTab from "./tabs/ProfileTab";
import ReviewTab from "./tabs/ReviewTab";
import WishlistTab from "./tabs/WishlistTab";
import { AUTH_STORAGE_SYNC_EVENT } from "@/redux/api/apiSlice";
import {
  useDashboardApiQuery,
  useOrderListApiQuery,
  useReturnRequestsApiQuery,
  useReviewListApiQuery,
  useProfileInfoApiQuery,
  useLazyLogoutApiQuery,
  useBuyerNotificationsApiQuery,
} from "@/redux/features/auth/apiSlice";
import SecondHandTab from "./tabs/SecondHandTab";
import LegalDocumentsTab from "./tabs/LegalDocumentsTab";
import NotificationsTab from "./tabs/NotificationsTab";
import appConfig from "@/appConfig";
import redirectToSellerPanel from "@/utils/sellerSsoRedirect";
import { toast } from "react-toastify";
import {
  useGetCountryListApiQuery,
  useGetStateListApiQuery,
  useGetCityListApiQuery,
} from "@/redux/features/locations/apiSlice";
import { useSecondHandVerificationQuery } from "@/redux/features/secondHand/apiSlice";

/** İkinci el doğrulama onay tikı (yeşil) */
function SecondHandApprovedTick({ className = "", title = "Doğrulandı" }) {
  return (
    <span
      className={`inline-flex items-center justify-center rounded-full bg-green-600 text-white shadow-sm ${className}`}
      title={title}
      aria-label={title}
    >
      <svg className="w-[55%] h-[55%]" viewBox="0 0 20 20" fill="currentColor" aria-hidden>
        <path
          fillRule="evenodd"
          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
          clipRule="evenodd"
        />
      </svg>
    </span>
  );
}

// Main Profile component content
function ProfileContent() {
  // Next.js router and navigation hooks
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  // Redux dispatch
  const dispatch = useDispatch();
  // Auth check state — hydration'dan sonra kontrol edilir
  const [authChecked, setAuthChecked] = useState(false);
  // State for switching to seller dashboard
  // State for active tab (dashboard, profile, order, etc.)
  const [active, setActive] = useState(null);
  /** second-hand alt sayfa: verification | listings | add | messages */
  const [secondHandSub, setSecondHandSub] = useState("");
  const [c2cDropdownOpen, setC2cDropdownOpen] = useState(true);

  // Auth — cookie/localStorage bazen birkaç tick gecikebilir; erken /login yönlendirmesini önle
  useEffect(() => {
    let cancelled = false;
    const timeouts = [];
    const tryAuth = (attempt) => {
      if (cancelled) return;
      if (auth()) {
        setAuthChecked(true);
        return;
      }
      if (attempt >= 6) {
        router.replace("/login");
        // Bazı Next navigasyonlarında eski hash (#dashboard) URL'de kalabiliyor.
        // Login sayfasında hash'e göre tab mantığı olmadığından temiz tutuyoruz.
        if (typeof window !== "undefined" && window.location.hash) {
          try {
            window.location.hash = "";
          } catch {
            /* ignore */
          }
        }
        return;
      }
      const delay = attempt === 0 ? 0 : 80 * attempt;
      const id = setTimeout(() => tryAuth(attempt + 1), delay);
      timeouts.push(id);
    };
    tryAuth(0);
    return () => {
      cancelled = true;
      timeouts.forEach(clearTimeout);
    };
  }, [pathname, router]);

  const session = auth();
  const token = session?.access_token;
  const isAuthed = authChecked && !!token;
  const [returnRequestFilters, setReturnRequestFilters] = useState({
    status: "all",
    search: "",
    reason: "",
    dateFrom: "",
    dateTo: "",
    perPage: 20,
  });

  // Set active tab based on URL hash (e.g., #dashboard, #second-hand-listings)
  useEffect(() => {
    const parseProfileHash = () => {
      const rawHash = window.location.hash;
      const segments = rawHash.split("#").filter(Boolean);
      const last = segments[segments.length - 1] || "dashboard";
      if (last.startsWith("second-hand-")) {
        const rawSub = last.slice("second-hand-".length);
        const allowed = ["verification", "listings", "add", "messages"];
        const secondHandSubNorm = allowed.includes(rawSub) ? rawSub : "";
        return { tab: "second-hand", secondHandSub: secondHandSubNorm };
      }
      if (last === "second-hand") {
        return { tab: "second-hand", secondHandSub: "" };
      }
      return { tab: last, secondHandSub: "" };
    };

    const applyHash = () => {
      const { tab, secondHandSub: sh } = parseProfileHash();
      setActive(tab);
      setSecondHandSub(sh);
      if (tab === "second-hand") {
        setC2cDropdownOpen(true);
      }
    };

    applyHash();
    window.addEventListener("hashchange", applyHash);
    return () => window.removeEventListener("hashchange", applyHash);
  }, [pathname, searchParams]);

  const goToSellerDashboard = () => {
    const token = auth()?.access_token;
    if (token) {
      redirectToSellerPanel(token);
      return;
    }
    window.location.href = `${appConfig.APPLICATION_URL || "https://seyfibaba.com"}/satici-giris`;
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
      router.push("/login");
    } else {
      // for force logout
      dispatch(setWishlistData(null));
      toast.success("Çıkış başarılı");
      localStorage.removeItem("auth");
      clearAccessTokenCookie();
      window.dispatchEvent(new Event(AUTH_STORAGE_SYNC_EVENT));
      router.push("/login");
    }
  };

  const logout = async () => {
    if (auth()) {
      await logoutApi({
        token: auth()?.access_token,
        success: logoutSuccessHandler,
      });
    }
  };

  /**
   * check auth token
   * @Inilization dashboardApi, orderListApi, reviewListApi, profileInfoApi
   * @param {object|array}
   * @returns {object|array}
   */

  const { data: dashboardApi, isFetching: isDashboardFetching } =
    useDashboardApiQuery(
      {
        token,
      },
      {
        // is_seller kontrolü tüm sekmelerde gerekli (Satıcı Paneline Geç butonu için)
        skip: !isAuthed,
        refetchOnMountOrArgChange: true,
      }
    );
  const { data: buyerNotificationsApi } = useBuyerNotificationsApiQuery(
    { token, perPage: 1 },
    { skip: !isAuthed, pollingInterval: 60000 }
  );
  const unreadNotificationCount = buyerNotificationsApi?.unread_count || 0;
  const { data: orderListApi, isFetching: isOrderListFetching } =
    useOrderListApiQuery(
      {
        token,
      },
      {
        skip: !isAuthed || active !== "order",
      }
    );
  const { data: returnRequestsApi, isFetching: isReturnRequestsFetching } =
    useReturnRequestsApiQuery(
      {
        token,
        ...returnRequestFilters,
      },
      {
        skip: !isAuthed || active !== "returns",
      }
    );
  const { data: reviewListApi, isFetching: isReviewListFetching } =
    useReviewListApiQuery(
      {
        token,
      },
      {
        skip: !isAuthed || active !== "review",
      }
    );
  const { data: profileInfoApi, isFetching: isProfileInfoFetching } =
    useProfileInfoApiQuery(
      {
        token,
      },
      {
        skip: !isAuthed || active !== "profile",
      }
    );

  const { data: secondHandVerData } = useSecondHandVerificationQuery(undefined, {
    // Sidebar tick/badge için gerekli
    skip: !isAuthed,
  });
  const secondHandVerified = secondHandVerData?.verification?.status === "approved";

  /**
   * get user location
   * useState userLocation
   * @Inilization  getCountryListApi, getStateListApi, getCityListApi
   * @param {object|array}
   * @returns {object|array}
   */
  const [userLocation, setUserLocation] = useState({
    country: null,
    state: null,
    city: null,
  });

  // Location API queries - moved outside conditional to follow Rules of Hooks
  const { data: getCountryListData, isFetching: isGetCountryListLoading } =
    useGetCountryListApiQuery(
      {
        token,
      },
      {
        skip: !isAuthed || active !== "address", // sadece adres sekmesinde
      }
    );

  const { data: getStateListApi, isFetching: isGetStateListLoading } =
    useGetStateListApiQuery(
      {
        countryId: dashboardApi?.personInfo?.country_id || 0, // Provide fallback value
        token,
      },
      {
        skip: !isAuthed || active !== "address" || !dashboardApi?.personInfo?.country_id, // Skip if no country_id
      }
    );

  const { data: getCityListApi, isFetching: isGetCityListLoading } =
    useGetCityListApiQuery(
      {
        stateId: dashboardApi?.personInfo?.state_id || 0, // Provide fallback value
        token,
      },
      {
        skip: !isAuthed || active !== "address" || !dashboardApi?.personInfo?.state_id, // Skip if no state_id
      }
    );

  useEffect(() => {
    if (dashboardApi) {
      const country = getCountryListData?.countries.find(
        (item) =>
          Number(item.id) === Number(dashboardApi?.personInfo?.country_id)
      );
      setUserLocation((prev) => ({
        ...prev,
        country: country ? country?.name : null,
      }));
      const state = getStateListApi?.states.find(
        (item) =>
          Number(item.id) === Number(dashboardApi?.personInfo?.state_id)
      );
      setUserLocation((prev) => ({
        ...prev,
        state: state ? state?.name : null,
      }));
      const city = getCityListApi?.cities.find(
        (item) =>
          Number(item.id) === Number(dashboardApi?.personInfo?.city_id)
      );
      setUserLocation((prev) => ({ ...prev, city: city ? city?.name : null }));
    }
  }, [dashboardApi, getCountryListData, getStateListApi, getCityListApi]);

  if (!authChecked) return (
    <div className="w-full flex justify-center items-center min-h-[400px]">
      <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900"></div>
    </div>
  );
  if (!active) return (
    <div className="w-full flex justify-center items-center min-h-[400px]">
      <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900"></div>
    </div>
  );

  return (
    <div className="profile-page-wrapper w-full">
      <div className="container-x mx-auto">
        <div className="w-full my-10">
          {/* Breadcrumb navigation */}
          <BreadcrumbCom
            paths={[
              { name: ServeLangItem()?.home, path: "/" },
              { name: ServeLangItem()?.profile, path: "/profile" },
            ]}
          />
          <div className="w-full bg-white xl:p-10 p-5">
            <div className="title-area w-full flex justify-between items-center">
              <h2 className="text-[22px] font-bold text-qblack">
                {ServeLangItem()?.Your_Dashboard}
              </h2>
              {/* Seller dashboard switch (if applicable) */}
              {Multivendor() === 1 &&
                dashboardApi &&
                dashboardApi.is_seller && (
                  <div className="switch-dashboard flex md:flex-row md:space-x-3 flex-col space-y-3 md:space-y-0 rtl:space-x-reverse items-center">
                    <button
                      onClick={goToSellerDashboard}
                      type="button"
                      className="px-4 py-2 bg-qblack text-white rounded hover:bg-qyellow hover:text-qblack transition-colors font-semibold"
                    >
                      Satıcı Paneline Geç
                    </button>
                  </div>
                )}
            </div>
            <div className="profile-wrapper w-full mt-8 xl:flex xl:space-x-10 rtl:space-x-reverse">
              {/* Sidebar navigation */}
              <div className="xl:w-[236px] w-full xl:min-h-[600px] ltr:xl:border-r rtl:xl:border-l border-[rgba(0, 0, 0, 0.1)] mb-10 xl:mb-0">
                <div className="flex xl:flex-col flex-row xl:space-y-10 rtl:space-x-reverse flex-wrap gap-3 xl:gap-0">
                  {/* Sidebar tab navigation helper — replaceState kullanıyoruz, history birikmiyor */}
                  {[
                    { tab: "dashboard", icon: <IcoDashboard />, label: ServeLangItem()?.Dashboard },
                    { tab: "profile", icon: <IcoPeople />, label: ServeLangItem()?.Personal_Info },
                    { tab: "order", icon: <IcoCart />, label: ServeLangItem()?.Order },
                    { tab: "notifications", icon: <IcoSupport />, label: "Bildirimler" },
                    { tab: "wishlist", icon: <IcoLove />, label: ServeLangItem()?.Wishlist },
                    { tab: "address", icon: <IcoAdress />, label: ServeLangItem()?.Address },
                    { tab: "review", icon: <IcoReviewHand />, label: ServeLangItem()?.Reviews },
                    { tab: "returns", icon: <IcoSupport />, label: "İade Taleplerim" },
                    { tab: "legal-documents", icon: <IcoSupport />, label: "Yasal Belgeler" },
                  ].map(({ tab, icon, label }) => (
                    <div key={tab} className="item group">
                      <button
                        type="button"
                        onClick={() => {
                          setActive(tab);
                          setSecondHandSub("");
                          window.history.replaceState(null, "", `/profile#${tab}`);
                        }}
                        className="flex space-x-3 rtl:space-x-reverse items-center text-qgray hover:text-qblack capitalize"
                      >
                        <span>{icon}</span>
                        <span className={`font-normal text-base capitalize cursor-pointer${active === tab ? " text-qblack font-semibold" : ""}`}>
                          {label}
                        </span>
                        {tab === "notifications" && unreadNotificationCount > 0 && (
                          <span className="ml-1 inline-flex min-w-[18px] h-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">
                            {unreadNotificationCount > 99 ? "99+" : unreadNotificationCount}
                          </span>
                        )}
                      </button>
                    </div>
                  ))}

                  {/* Satıcı Araçları — yalnızca satıcı hesapları için göster */}
                  {Multivendor() === 1 && dashboardApi?.is_seller && (
                    <div className="item group">
                      <button
                        type="button"
                        onClick={() => {
                          setActive("seller-tools");
                          setSecondHandSub("");
                          window.history.replaceState(null, "", "/profile#seller-tools");
                        }}
                        className="flex space-x-3 rtl:space-x-reverse items-center text-qgray hover:text-qblack"
                      >
                        <span>
                          <svg className="w-5 h-5" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                          </svg>
                        </span>
                        <span className={`font-normal text-base cursor-pointer${active === "seller-tools" ? " text-qblack font-semibold" : ""}`}>
                          Satıcı Doğrulama (KYC)
                        </span>
                      </button>
                    </div>
                  )}

                  <div className="item group w-full xl:max-w-[236px]">
                    <div
                      className={`rounded-xl border-2 border-amber-400 bg-gradient-to-br from-amber-50 via-white to-orange-50/90 shadow-sm ring-1 ring-amber-200/70 ${
                        active === "second-hand" ? "ring-2 ring-amber-300" : ""
                      }`}
                    >
                      <button
                        type="button"
                        onClick={() => setC2cDropdownOpen((o) => !o)}
                        className="w-full flex items-center justify-between gap-2 px-3 py-2.5 text-left"
                        aria-expanded={c2cDropdownOpen}
                      >
                        <span className="flex items-center gap-2 min-w-0">
                          <span className="text-amber-800 shrink-0 [&>svg]:w-5 [&>svg]:h-5">
                            <IcoSecondHand />
                          </span>
                          <span className="flex flex-col min-w-0">
                            <span className="flex items-center gap-2 flex-wrap">
                              <span className="font-700 text-qblack text-sm leading-tight">İkinci El Sat</span>
                              {secondHandVerified && (
                                <span className="inline-flex items-center gap-1 rounded-full bg-green-600/10 text-green-800 text-[10px] font-700 px-2 py-0.5 border border-green-600/25">
                                  <SecondHandApprovedTick className="w-3.5 h-3.5" title="İkinci el doğrulandı" />
                                  <span className="leading-none">Onaylı</span>
                                </span>
                              )}
                            </span>
                            <span className="text-[10px] font-600 uppercase tracking-wide text-amber-900/80 truncate">
                              Kendi ilanını yayınla
                            </span>
                          </span>
                        </span>
                        <svg
                          className={`w-4 h-4 shrink-0 text-amber-900 transition-transform ${c2cDropdownOpen ? "rotate-180" : ""}`}
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                          strokeWidth={2}
                          aria-hidden
                        >
                          <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                      </button>
                      {c2cDropdownOpen && (
                        <div className="border-t border-amber-200/80 px-2 pb-2 pt-1 space-y-0.5">
                          {[
                            { sub: "verification", label: "Doğrulama", hash: "second-hand-verification" },
                            { sub: "listings", label: "İlanlarım", hash: "second-hand-listings" },
                            { sub: "add", label: "İlan ekle", hash: "second-hand-add" },
                            { sub: "messages", label: "Mesajlar", hash: "second-hand-messages" },
                          ].map(({ sub, label, hash }) => (
                            <button
                              key={sub}
                              type="button"
                              onClick={() => {
                                setActive("second-hand");
                                setSecondHandSub(sub);
                                window.history.replaceState(null, "", `/profile#${hash}`);
                              }}
                              className={`w-full text-left text-sm py-2 px-2.5 rounded-lg transition-colors ${
                                active === "second-hand" &&
                                (secondHandSub === sub || (secondHandSub === "" && sub === "verification"))
                                  ? "bg-amber-200/90 text-qblack font-600"
                                  : "text-qgray hover:bg-amber-100/80 hover:text-qblack"
                              }`}
                            >
                              <span className="flex items-center justify-between gap-2">
                                <span>{label}</span>
                                {sub === "verification" && secondHandVerified && (
                                  <SecondHandApprovedTick className="w-5 h-5 shrink-0" title="İkinci el doğrulandı" />
                                )}
                              </span>
                            </button>
                          ))}
                        </div>
                      )}
                    </div>
                  </div>

                  <div className="item group">
                    <button
                      type="button"
                      onClick={() => {
                        setActive("password");
                        setSecondHandSub("");
                        window.history.replaceState(null, "", "/profile#password");
                      }}
                      className="flex space-x-3 rtl:space-x-reverse items-center text-qgray hover:text-qblack capitalize"
                    >
                      <span>
                        <IcoPassword />
                      </span>
                      <span
                        className={`font-normal text-base capitalize cursor-pointer${active === "password" ? " text-qblack font-semibold" : ""}`}
                      >
                        {ServeLangItem()?.Change_Password}
                      </span>
                    </button>
                  </div>
                  {/* Logout button */}
                  <div className="item group">
                    <div
                      onClick={logout}
                      className="flex space-x-3 rtl:space-x-reverse items-center text-qgray hover:text-qblack capitalize"
                    >
                      <span>
                        <IcoLogout />
                      </span>
                      <span className=" font-normal text-base capitalize cursor-pointer">
                        {ServeLangItem()?.Logout}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              {/* Main content area for each tab */}
              <div className="flex-1">
                <div className="item-body dashboard-wrapper w-full">
                  {/* Render tab content based on active state */}
                  {active === "dashboard" ? (
                    <>
                      {!isDashboardFetching && dashboardApi ? (
                        <Dashboard
                          dashBoardData={dashboardApi}
                          userLocation={userLocation}
                        />
                      ) : (
                        <div className="flex justify-center items-center h-full">
                          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900"></div>
                        </div>
                      )}
                    </>
                  ) : active === "profile" ? (
                    <>
                      {!isProfileInfoFetching && profileInfoApi ? (
                        <ProfileTab profileInfo={profileInfoApi} />
                      ) : (
                        <div className="flex justify-center items-center h-full">
                          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900"></div>
                        </div>
                      )}
                    </>
                  ) : active === "order" ? (
                    <>
                      {!isOrderListFetching && orderListApi ? (
                        <OrderTab orders={orderListApi?.orders?.data} />
                      ) : (
                        <div className="flex justify-center items-center h-full">
                          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900"></div>
                        </div>
                      )}
                    </>
                  ) : active === "notifications" ? (
                    <NotificationsTab />
                  ) : active === "wishlist" ? (
                    <WishlistTab />
                  ) : active === "address" ? (
                    <>
                      {!isGetCountryListLoading && getCountryListData ? (
                        <AddressesTab
                          countryLists={getCountryListData?.countries}
                        />
                      ) : (
                        <div className="flex justify-center items-center h-full">
                          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900"></div>
                        </div>
                      )}
                    </>
                  ) : active === "password" ? (
                    <PasswordTab />
                  ) : active === "review" ? (
                    <>
                      {!isReviewListFetching && reviewListApi ? (
                        <ReviewTab reviews={reviewListApi?.reviews?.data} />
                      ) : (
                        <div className="flex justify-center items-center h-full">
                          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900"></div>
                        </div>
                      )}
                    </>
                  ) : active === "returns" ? (
                    <>
                      {!isReturnRequestsFetching && returnRequestsApi ? (
                        <ReturnRequestsTab
                          returns={returnRequestsApi?.returns?.data}
                          stats={returnRequestsApi?.stats}
                          pagination={returnRequestsApi?.returns}
                          filters={returnRequestFilters}
                          onFiltersChange={setReturnRequestFilters}
                          isLoading={isReturnRequestsFetching}
                        />
                      ) : (
                        <div className="flex justify-center items-center h-full">
                          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900"></div>
                        </div>
                      )}
                    </>
                  ) : active === "second-hand" ? (
                    <SecondHandTab subNav={secondHandSub} />
                  ) : active === "legal-documents" ? (
                    <LegalDocumentsTab />
                  ) : active === "seller-tools" ? (
                    <SellerOperationsTab
                      token={auth()?.access_token}
                      isActive={active === "seller-tools"}
                      isSeller={!!dashboardApi?.is_seller}
                    />
                  ) : (
                    ""
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default function Profile() {
  return (
    <Suspense
      fallback={
        <div className="w-full flex justify-center items-center min-h-[400px]">
          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900"></div>
        </div>
      }
    >
      <ProfileContent />
    </Suspense>
  );
}
