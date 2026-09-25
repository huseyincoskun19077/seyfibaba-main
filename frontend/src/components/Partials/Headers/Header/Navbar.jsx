"use client";

import React, { useEffect, useRef, useState } from "react";
import { useSelector } from "react-redux";
import Link from "next/link";

import Arrow from "../../../Helpers/icons/Arrow";
import FontAwesomeCom from "../../../Helpers/icons/FontAwesomeCom";
import Multivendor from "../../../Shared/Multivendor";
import appConfig from "@/appConfig";
import redirectToSellerPanel from "@/utils/sellerSsoRedirect";
import useAuthSession from "@/hooks/useAuthSession";
import { marketplaceProfileUrl, marketplaceUrl } from "@/utils/secondHandSite";

const navLinkClass =
  "relative flex items-center text-sm font-700 cursor-pointer text-[#04334a] hover:text-[#04334a] transition-colors after:absolute after:left-2.5 after:right-2.5 after:bottom-0 after:h-[2px] after:bg-qyellow after:scale-x-0 after:origin-left after:transition-transform hover:after:scale-x-100";

const promoLinkClass =
  "relative flex items-center px-2.5 py-2 text-sm font-800 cursor-pointer text-[#04334a] whitespace-nowrap hover:bg-[#04334a]/[0.04] rounded-md transition-colors";

export default function Navbar({ className, isSecondHandSite = false }) {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const categoryList = websiteSetup?.payload?.productCategories || [];

  const [menuOpen, setMenuOpen] = useState(false);
  const [activeCatId, setActiveCatId] = useState(null);
  const [multivendor, setMultivendor] = useState(null);
  const hoverRootRef = useRef(null);
  const authUser = useAuthSession();

  useEffect(() => {
    setMultivendor(Multivendor());
  }, []);

  const closeMenu = () => {
    setMenuOpen(false);
  };

  const openMenu = (catId = null) => {
    const list = categoryList;
    const firstId = list[0]?.id ?? null;
    setActiveCatId(catId ?? activeCatId ?? firstId);
    setMenuOpen(true);
  };

  useEffect(() => {
    if (!menuOpen) return undefined;

    const onDocOver = (e) => {
      const root = hoverRootRef.current;
      if (!root) return;
      if (e.target instanceof Node && !root.contains(e.target)) {
        closeMenu();
      }
    };

    const onDocDown = (e) => {
      const root = hoverRootRef.current;
      if (!root) return;
      if (e.target instanceof Node && !root.contains(e.target)) {
        closeMenu();
      }
    };

    document.addEventListener("mouseover", onDocOver, true);
    document.addEventListener("pointerdown", onDocDown, true);
    return () => {
      document.removeEventListener("mouseover", onDocOver, true);
      document.removeEventListener("pointerdown", onDocDown, true);
    };
  }, [menuOpen]);

  const getSubCategories = (cat) =>
    cat?.active_sub_categories ||
    cat?.activeSubCategories ||
    cat?.sub_categories ||
    cat?.subCategories ||
    [];

  const getChildCategories = (sub) =>
    sub?.active_child_categories ||
    sub?.activeChildCategories ||
    sub?.child_categories ||
    sub?.childCategories ||
    sub?.children ||
    [];

  const activeCat =
    !isSecondHandSite && menuOpen
      ? categoryList.find((c) => c.id === activeCatId) || categoryList[0] || null
      : null;
  const activeSubs = activeCat ? getSubCategories(activeCat) : [];

  const renderNavLink = (href, label, classNameExtra = "") => (
    <li>
      <Link href={href} className={`${navLinkClass} px-2.5 py-2 ${classNameExtra}`}>
        <span className="whitespace-nowrap">{label}</span>
      </Link>
    </li>
  );

  const renderSellerButton = () => {
    const isSeller = authUser?.user?.seller;
    if (!isSeller) return null;

    const token = authUser?.access_token;
    const loginUrl = `${appConfig.APPLICATION_URL || "https://kuafortedarik.com"}/satici-giris`;

    return (
      <div className="become-seller-btn">
        <a
          href={token ? "#" : loginUrl}
          target="_blank"
          rel="noopener noreferrer"
          onClick={(event) => {
            if (!token) return;
            event.preventDefault();
            redirectToSellerPanel(token);
          }}
        >
          <div className="w-[150px] h-[40px] flex justify-center items-center cursor-pointer bg-[#04334a] rounded-lg hover:brightness-110 transition">
            <span className="text-sm font-700 text-qyellow">Satıcı Paneli</span>
          </div>
        </a>
      </div>
    );
  };

  return (
    <div
      ref={hoverRootRef}
      className={`nav-widget-wrapper w-full min-h-[60px] relative z-30 ${className || ""}`}
      onMouseLeave={closeMenu}
    >
      <div className="container-x mx-auto h-[60px]">
        <div className="w-full h-full relative">
          <div className="w-full h-full flex justify-between items-center gap-4">
            <div className="nav min-w-0 flex-1 overflow-visible">
              <ul className="nav-wrapper relative z-40 flex items-center gap-0.5 xl:gap-1 flex-nowrap overflow-x-auto overflow-style-none">
                {isSecondHandSite ? (
                  <>
                    {renderNavLink("/ikinci-el", "İlanlar")}
                    <li>
                      <Link
                        href={marketplaceProfileUrl("second-hand-add")}
                        className={`${navLinkClass} px-2.5 py-2`}
                      >
                        <span>İlan ver</span>
                      </Link>
                    </li>
                    {renderNavLink("/ikinci-el-sozlesmesi", "Sözleşme")}
                    {renderNavLink("/ikinci-el-kvkk", "KVKK")}
                    <li>
                      <Link href={marketplaceUrl("/")} className={`${navLinkClass} px-2.5 py-2`}>
                        <span>Mağazaya git</span>
                      </Link>
                    </li>
                  </>
                ) : (
                  <>
                    <li
                      className="relative shrink-0"
                      onMouseEnter={() => openMenu(categoryList[0]?.id ?? null)}
                    >
                      <button
                        type="button"
                        className={`flex items-center gap-2 h-10 px-3 rounded-lg text-sm font-800 text-white bg-[#04334a] hover:brightness-110 transition ${
                          menuOpen ? "ring-2 ring-qyellow/60" : ""
                        }`}
                        aria-expanded={menuOpen}
                        aria-haspopup="menu"
                        onClick={() =>
                          menuOpen ? closeMenu() : openMenu(categoryList[0]?.id ?? null)
                        }
                      >
                        <FontAwesomeCom className="w-4 h-4 text-qyellow" icon="menu" />
                        <span>Kategoriler</span>
                        <Arrow className="fill-current text-qyellow" />
                      </button>
                    </li>

                    {categoryList.slice(0, 6).map((cat) => (
                      <li
                        key={cat.id}
                        className="relative shrink-0"
                        onMouseEnter={() => openMenu(cat.id)}
                      >
                        <Link
                          href={{
                            pathname: "/products",
                            query: { category: cat.slug },
                          }}
                          className={`${navLinkClass} px-2.5 py-2 rounded-md hover:bg-[#04334a]/[0.04] ${
                            menuOpen && activeCatId === cat.id
                              ? "bg-[#04334a]/[0.06] after:scale-x-100"
                              : ""
                          }`}
                        >
                          <span className="whitespace-nowrap">{cat.name}</span>
                        </Link>
                      </li>
                    ))}

                    <li className="shrink-0" onMouseEnter={closeMenu}>
                      <Link
                        href="/products?highlight=discounted"
                        className={promoLinkClass}
                      >
                        İndirimli Ürünler
                      </Link>
                    </li>
                    <li className="shrink-0" onMouseEnter={closeMenu}>
                      <Link
                        href="/products?highlight=best_product"
                        className={promoLinkClass}
                      >
                        Çok Satanlar
                      </Link>
                    </li>
                    <li className="shrink-0" onMouseEnter={closeMenu}>
                      <Link href="/products" className={promoLinkClass}>
                        Tüm Ürünler
                      </Link>
                    </li>
                  </>
                )}
              </ul>
            </div>

            <div
              className="flex items-center gap-3 shrink-0"
              onMouseEnter={closeMenu}
            >
              {isSecondHandSite ? (
                <Link
                  href={marketplaceUrl("/")}
                  className="h-[40px] px-4 inline-flex items-center justify-center rounded-xl bg-qyellow text-qblack text-sm font-800 ring-1 ring-amber-900/10 hover:brightness-95"
                >
                  Mağazaya git
                </Link>
              ) : (
                multivendor === 1 && renderSellerButton()
              )}
            </div>
          </div>
        </div>
      </div>

      {!isSecondHandSite && menuOpen && activeCat ? (
        <div className="absolute left-0 right-0 top-full z-50">
          <div className="container-x mx-auto">
            <div
              className="flex w-full bg-white rounded-b-xl border border-[#04334a]/10 overflow-hidden"
              style={{ boxShadow: "0 16px 40px rgba(4,51,74,0.14)" }}
              role="menu"
            >
              {/* Sol: ana kategoriler */}
              <ul className="w-[240px] lg:w-[280px] shrink-0 border-r border-[#04334a]/10 py-2 max-h-[min(72vh,520px)] overflow-y-auto bg-white">
                {categoryList.map((cat) => {
                  const isActive = activeCat?.id === cat.id;
                  return (
                    <li key={cat.id}>
                      <button
                        type="button"
                        onMouseEnter={() => setActiveCatId(cat.id)}
                        onFocus={() => setActiveCatId(cat.id)}
                        onClick={() => setActiveCatId(cat.id)}
                        className={`w-full flex items-center justify-between gap-2 px-4 py-3 text-left text-sm font-600 transition-colors ${
                          isActive
                            ? "bg-qyellow/35 text-[#04334a]"
                            : "text-[#04334a]/85 hover:bg-[#04334a]/[0.04]"
                        }`}
                      >
                        <span className="flex items-center gap-2.5 min-w-0">
                          {cat.icon ? (
                            <FontAwesomeCom
                              className="w-3.5 h-3.5 shrink-0 text-[#04334a]/60"
                              icon={cat.icon}
                            />
                          ) : null}
                          <span className="truncate">{cat.name}</span>
                        </span>
                        <svg
                          className={`shrink-0 ${isActive ? "text-[#04334a]" : "text-[#04334a]/35"}`}
                          width="6"
                          height="9"
                          viewBox="0 0 6 9"
                          fill="currentColor"
                          aria-hidden
                        >
                          <rect
                            x="1.5"
                            y="0.8"
                            width="5.8"
                            height="1.3"
                            transform="rotate(45 1.5 0.8)"
                          />
                          <rect
                            x="5.6"
                            y="4.9"
                            width="5.8"
                            height="1.3"
                            transform="rotate(135 5.6 4.9)"
                          />
                        </svg>
                      </button>
                    </li>
                  );
                })}
              </ul>

              {/* Sağ: seçilen kategorinin altları — çok sütun */}
              <div className="flex-1 min-w-0 py-5 px-5 lg:px-7 max-h-[min(72vh,520px)] overflow-y-auto bg-[#fafbfc]">
                <div className="flex items-center justify-between gap-3 mb-4 pb-3 border-b border-[#04334a]/10">
                  <h3 className="text-base font-800 text-[#04334a]">
                    {activeCat.name}
                  </h3>
                  <Link
                    href={{
                      pathname: "/products",
                      query: { category: activeCat.slug },
                    }}
                    className="text-xs font-800 text-[#04334a] bg-qyellow px-3 py-1.5 rounded-lg hover:brightness-95 shrink-0"
                    onClick={closeMenu}
                  >
                    Tümünü gör
                  </Link>
                </div>

                {activeSubs.length > 0 ? (
                  <div className="columns-2 lg:columns-3 gap-x-8 space-y-6">
                    {activeSubs.map((sub) => {
                      const children = getChildCategories(sub);
                      const visibleChildren = children.slice(0, 8);
                      const hasMore = children.length > 8;

                      return (
                        <div
                          key={sub.id}
                          className="break-inside-avoid mb-6"
                        >
                          <Link
                            href={{
                              pathname: "/products",
                              query: { sub_category: sub.slug },
                            }}
                            className="inline-flex items-center gap-1 text-sm font-800 text-[#04334a] hover:text-qyellow mb-2"
                            onClick={closeMenu}
                          >
                            {sub.name}
                            <span aria-hidden>›</span>
                          </Link>
                          {visibleChildren.length > 0 ? (
                            <ul className="space-y-1">
                              {visibleChildren.map((child) => (
                                <li key={child.id}>
                                  <Link
                                    href={{
                                      pathname: "/products",
                                      query: { child_category: child.slug },
                                    }}
                                    className="block text-[13px] text-[#04334a]/75 hover:text-[#04334a] py-0.5"
                                    onClick={closeMenu}
                                  >
                                    {child.name}
                                  </Link>
                                </li>
                              ))}
                              {hasMore ? (
                                <li>
                                  <Link
                                    href={{
                                      pathname: "/products",
                                      query: { sub_category: sub.slug },
                                    }}
                                    className="inline-flex items-center gap-1 text-[12px] font-700 text-[#04334a]/55 hover:text-[#04334a] mt-1"
                                    onClick={closeMenu}
                                  >
                                    Daha fazla gör
                                    <span aria-hidden>↓</span>
                                  </Link>
                                </li>
                              ) : null}
                            </ul>
                          ) : (
                            <p className="text-[12px] text-[#04334a]/40">
                              Alt başlık yok
                            </p>
                          )}
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  <p className="text-sm text-[#04334a]/45 py-6">
                    Bu kategoride alt kategori yok.{" "}
                    <Link
                      href={{
                        pathname: "/products",
                        query: { category: activeCat.slug },
                      }}
                      className="font-700 underline"
                      onClick={closeMenu}
                    >
                      Ürünlere git
                    </Link>
                  </p>
                )}
              </div>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
}
