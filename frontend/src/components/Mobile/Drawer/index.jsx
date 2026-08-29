import Link from "next/link";
import React, { useState, useEffect, useCallback } from "react";
import Compair from "../../Helpers/icons/Compair";
import ThinLove from "../../Helpers/icons/ThinLove";
import CloseButtonIcon from "../../Helpers/icons/CloseButtonIcon";
import SearchIcon from "../../Helpers/icons/SearchIcon";
import ArrowRightIcon from "../../Helpers/icons/ArrowRightIcon";
import { useSelector } from "react-redux";
import { useRouter } from "next/navigation";
import FontAwesomeCom from "../../Helpers/icons/FontAwesomeCom";
import ServeLangItem from "../../Helpers/ServeLangItem";
import Multivendor from "../../Shared/Multivendor";
import useAuthSession from "@/hooks/useAuthSession";
import { useHideMarketplaceSecondHandNav } from "@/components/SecondHand/SecondHandSiteNavLink";
import { marketplaceProfileUrl, marketplaceUrl } from "@/utils/secondHandSite";
import { marketplaceLoginHref } from "@/utils/auth";

function MenuRow({ href, children, onClose }) {
  return (
    <li className="border-b border-gray-100 last:border-b-0">
      <Link
        href={href}
        onClick={onClose}
        className="flex min-h-[52px] items-center justify-between gap-3 px-4 py-3 text-[15px] font-600 text-qblack transition-colors active:bg-amber-50 hover:bg-gray-50"
      >
        <span className="leading-snug">{children}</span>
        <span className="shrink-0 text-qgray opacity-60" aria-hidden>
          <ArrowRightIcon />
        </span>
      </Link>
    </li>
  );
}

export default function Drawer({ className, open, action, isSecondHandSite = false }) {
  const [isMultivendor, setIsMultivendor] = useState(null);
  useEffect(() => {
    setIsMultivendor(Multivendor());
  }, []);

  const router = useRouter();
  const hideSecondHandNav = useHideMarketplaceSecondHandNav();
  const [tab, setTab] = useState("category");
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const categoryList = websiteSetup?.payload?.productCategories;
  const customPages = websiteSetup?.payload?.customPages;
  const { compareProducts } = useSelector((state) => state.compareProducts);
  const { wishlistData } = useSelector((state) => state.wishlistData);
  const wishlists = wishlistData?.wishlists;
  const [searchKey, setSearchkey] = useState("");

  const closeDrawer = useCallback(() => {
    if (typeof action === "function") action();
  }, [action]);
  const session = useAuthSession();

  useEffect(() => {
    if (!open) return undefined;
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.body.style.overflow = prev;
    };
  }, [open]);

  const searchHandler = () => {
    const q = searchKey.trim();
    if (!q) return;
    router.push(`/search?search=${encodeURIComponent(q)}`);
    closeDrawer();
    setSearchkey("");
  };

  const lang = ServeLangItem();
  const [sessionReady, setSessionReady] = useState(false);
  useEffect(() => {
    setSessionReady(true);
  }, []);

  return (
    <div className={`drawer-wrapper relative block h-full w-full lg:hidden ${className || ""}`}>
      {open && (
        <button
          type="button"
          aria-label="Menüyü kapat"
          className="fixed inset-0 z-40 bg-black/50 backdrop-blur-[2px] transition-opacity"
          onClick={closeDrawer}
        />
      )}

      <aside
        role="dialog"
        aria-modal="true"
        aria-label="Site menüsü"
        className={`fixed left-0 top-0 z-50 flex h-[100dvh] w-[min(100vw-20px,400px)] max-w-full flex-col bg-white shadow-[6px_0_32px_rgba(0,0,0,0.12)] transition-transform duration-300 ease-out ${
          open ? "translate-x-0" : "-translate-x-full pointer-events-none"
        }`}
        style={{
          paddingTop: "max(12px, env(safe-area-inset-top))",
          paddingBottom: "max(12px, env(safe-area-inset-bottom))",
        }}
      >
        {/* Üst çubuk */}
        <div className="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 px-4 pb-3 pt-1">
          <div className="flex items-center gap-1">
            {isSecondHandSite ? (
              <p className="px-1 text-sm font-800 text-qblack">İkinci El</p>
            ) : (
              <>
            <Link
              href="/products-compare"
              onClick={closeDrawer}
              className="relative flex h-11 w-11 items-center justify-center rounded-full text-qblack transition-colors hover:bg-gray-100"
              aria-label="Karşılaştır"
            >
              <Compair className="fill-current" />
              <span className="absolute -right-0.5 -top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-qyellow px-0.5 text-[10px] font-700 leading-none text-qblack">
                {compareProducts?.length ?? 0}
              </span>
            </Link>
            <Link
              href="/wishlist"
              onClick={closeDrawer}
              className="relative flex h-11 w-11 items-center justify-center rounded-full text-qblack transition-colors hover:bg-gray-100"
              aria-label="Favorilerim"
            >
              <ThinLove className="fill-current" />
              <span className="absolute -right-0.5 -top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-qyellow px-0.5 text-[10px] font-700 leading-none text-qblack">
                {wishlists?.length ?? 0}
              </span>
            </Link>
              </>
            )}
          </div>
          <button
            type="button"
            onClick={closeDrawer}
            className="flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 text-qblack transition-colors hover:bg-gray-50"
            aria-label="Menüyü kapat"
          >
            <CloseButtonIcon />
          </button>
        </div>

        {/* Giriş durumu */}
        <div className="shrink-0 px-4 pt-3">
          {!sessionReady ? (
            <div className="flex items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50/80 px-3.5 py-3">
              <div className="min-w-0">
                <p className="text-sm font-700 text-qblack">Yükleniyor…</p>
                <p className="text-[12px] text-qgray"> </p>
              </div>
              <div className="h-9 w-[92px] rounded-lg bg-gray-200/70 animate-pulse" />
            </div>
          ) : session ? (
            <div className="flex items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50/80 px-3.5 py-3">
              <div className="min-w-0">
                <p className="text-sm font-700 text-qblack truncate">Giriş yapıldı</p>
                <p className="text-[12px] text-qgray truncate">{session?.user?.name || session?.user?.email || "Hesabım"}</p>
              </div>
              <Link
                href={isSecondHandSite ? marketplaceUrl("/profile") : "/profile"}
                onClick={closeDrawer}
                className="h-9 px-3 inline-flex items-center justify-center rounded-lg bg-qyellow text-xs font-800 text-qblack ring-1 ring-amber-900/10 hover:brightness-95 transition"
              >
                Hesabım
              </Link>
            </div>
          ) : (
            <div className="flex items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50/80 px-3.5 py-3">
              <div className="min-w-0">
                <p className="text-sm font-700 text-qblack">Giriş yapılmadı</p>
                <p className="text-[12px] text-qgray">Mesaj/teklif için giriş yapmanız gerekir.</p>
              </div>
              <Link
                href={isSecondHandSite ? marketplaceUrl(marketplaceLoginHref()) : "/login"}
                onClick={closeDrawer}
                className="h-9 px-3 inline-flex items-center justify-center rounded-lg bg-qblack text-xs font-800 text-white hover:bg-qblack/90 transition"
              >
                Giriş yap
              </Link>
            </div>
          )}
        </div>

        {isSecondHandSite ? (
          <div className="min-h-0 flex-1 overflow-y-auto pb-6">
            <div className="px-4 py-3">
              <form action="/ikinci-el" method="get">
                <div className="flex h-11 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                  <input
                    type="search"
                    name="q"
                    placeholder="İlan ara…"
                    className="min-w-0 flex-1 border-0 bg-transparent px-3 text-sm text-qblack placeholder:text-qgray outline-none"
                  />
                  <button
                    type="submit"
                    className="flex w-12 shrink-0 items-center justify-center bg-qyellow text-qblack"
                    aria-label="Ara"
                  >
                    <SearchIcon />
                  </button>
                </div>
              </form>
            </div>
            <ul>
              <MenuRow href="/" onClose={closeDrawer}>İlanlar</MenuRow>
              <MenuRow href={marketplaceProfileUrl("second-hand-add")} onClose={closeDrawer}>İlan ver</MenuRow>
              <MenuRow href={marketplaceProfileUrl("second-hand-verification")} onClose={closeDrawer}>Doğrulama</MenuRow>
              <MenuRow href={marketplaceProfileUrl("second-hand-messages")} onClose={closeDrawer}>Mesajlar</MenuRow>
              <MenuRow href="/ikinci-el-sozlesmesi" onClose={closeDrawer}>Sözleşme</MenuRow>
              <MenuRow href="/ikinci-el-kvkk" onClose={closeDrawer}>KVKK</MenuRow>
              <MenuRow href={marketplaceUrl("/")} onClose={closeDrawer}>Mağazaya git</MenuRow>
            </ul>
          </div>
        ) : (
        <>
        {/* Arama */}
        <div className="shrink-0 px-4 py-3">
          <div className="flex h-11 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <input
              value={searchKey}
              onChange={(e) => setSearchkey(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === "Enter") searchHandler();
              }}
              type="search"
              enterKeyHint="search"
              className="min-w-0 flex-1 border-0 bg-transparent px-3 text-sm text-qblack placeholder:text-qgray outline-none focus:ring-0"
              placeholder="Ürün ara…"
            />
            <button
              type="button"
              onClick={searchHandler}
              className="flex w-12 shrink-0 items-center justify-center bg-qyellow text-qblack transition hover:brightness-95"
              aria-label="Ara"
            >
              <SearchIcon />
            </button>
          </div>
        </div>

        {/* Sekmeler */}
        <div className="shrink-0 px-4 pb-2">
          <div className="flex rounded-xl bg-gray-100 p-1" role="tablist">
            <button
              type="button"
              role="tab"
              aria-selected={tab === "category"}
              onClick={() => setTab("category")}
              className={`flex-1 rounded-lg py-2.5 text-center text-sm font-700 transition ${
                tab === "category" ? "bg-white text-qblack shadow-sm" : "text-qgray hover:text-qblack"
              }`}
            >
              {lang?.Categories || "Kategoriler"}
            </button>
            <button
              type="button"
              role="tab"
              aria-selected={tab === "menu"}
              onClick={() => setTab("menu")}
              className={`flex-1 rounded-lg py-2.5 text-center text-sm font-700 transition ${
                tab === "menu" ? "bg-white text-qblack shadow-sm" : "text-qgray hover:text-qblack"
              }`}
            >
              {lang?.Main_Menu || "Menü"}
            </button>
          </div>
        </div>

        {/* Liste — kaydırılabilir */}
        <div className="min-h-0 flex-1 overflow-y-auto overflow-x-hidden overscroll-contain pb-6">
          {tab === "category" ? (
            <div className="w-full">
              <ul>
                {categoryList?.map((item) => (
                  <li key={item.id} className="border-b border-gray-100 last:border-b-0">
                    <Link
                      href={`/kategori/${encodeURIComponent(item.slug)}`}
                      onClick={closeDrawer}
                      className="flex min-h-[52px] items-center justify-between gap-3 px-4 py-3 transition-colors active:bg-amber-50 hover:bg-gray-50"
                    >
                      <span className="flex min-w-0 items-center gap-3">
                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-50 text-qblack">
                          <FontAwesomeCom className="h-4 w-4" icon={item.icon} />
                        </span>
                        <span className="truncate text-[15px] font-600 capitalize text-qblack">{item.name}</span>
                      </span>
                      <span className="shrink-0 text-qgray opacity-60" aria-hidden>
                        <ArrowRightIcon />
                      </span>
                    </Link>
                  </li>
                ))}
              </ul>
              {isMultivendor === 1 && (
                <div className="mt-4 px-4">
                  <Link
                    href="/satici-kayit"
                    onClick={closeDrawer}
                    className="flex h-12 w-full items-center justify-center rounded-xl bg-qyellow text-sm font-700 text-qblack shadow-sm transition hover:brightness-95"
                  >
                    {lang?.Become_seller || "Satıcı Ol"}
                  </Link>
                </div>
              )}
            </div>
          ) : (
            <div className="w-full">
              <ul>
                <MenuRow href="/products" onClose={closeDrawer}>
                  Tüm Ürünler
                </MenuRow>
                {!hideSecondHandNav && (
                  <MenuRow href="/ikinci-el" onClose={closeDrawer}>
                    İkinci el al/sat
                  </MenuRow>
                )}
                <MenuRow href="/blogs" onClose={closeDrawer}>
                  Blog
                </MenuRow>
                <MenuRow href="/about" onClose={closeDrawer}>
                  Hakkımızda
                </MenuRow>
                <MenuRow href="/contact" onClose={closeDrawer}>
                  İletişim
                </MenuRow>
              </ul>

              <div className="mt-2 px-4 pt-2">
                <p className="text-[11px] font-700 uppercase tracking-wide text-qgray">{lang?.Pages || "Sayfalar"}</p>
              </div>
              <ul className="mt-1 rounded-xl border border-gray-100 bg-gray-50/80 mx-2 overflow-hidden">
                <MenuRow href="/privacy-policy" onClose={closeDrawer}>
                  {lang?.Privacy_Policy}
                </MenuRow>
                <MenuRow href="/faq" onClose={closeDrawer}>
                  {lang?.FAQ}
                </MenuRow>
                <MenuRow href="/terms-condition" onClose={closeDrawer}>
                  {lang?.Term_and_Conditions}
                </MenuRow>
                <MenuRow href="/seller-terms-condition" onClose={closeDrawer}>
                  {lang?.Seller_terms_and_conditions}
                </MenuRow>
                {customPages?.map((item) => (
                  <MenuRow key={item.slug || item.id} href={`/${item.slug}`} onClose={closeDrawer}>
                    {item.page_name}
                  </MenuRow>
                ))}
              </ul>
            </div>
          )}
        </div>
        </>
        )}
      </aside>
    </div>
  );
}
