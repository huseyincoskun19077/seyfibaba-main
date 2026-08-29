"use client";
import Image from "next/image";
import Link from "next/link";
import { useEffect, useState } from "react";
import { useSelector } from "react-redux";
import FontAwesomeCom from "../../../Helpers/icons/FontAwesomeCom";
import appConfig from "@/appConfig";
import { getProductImageProps } from "@/utils/productImage";
import {
  FOOTER_CORPORATE_LINKS,
  FOOTER_LEGAL_LINKS,
  legalPath,
} from "@/config/legalDocuments";
import { marketplaceUrl } from "@/utils/secondHandSite";

const importantFooterLinks = [
  { href: "/products", label: "Tüm Ürünler" },
  { href: "/salon-crm", label: "Salon CRM" },
  { href: "/blogs", label: "Blog" },
  { href: "/about", label: "Hakkimizda" },
  { href: "/contact", label: "Iletisim" },
  { href: "/tracking-order", label: "Siparis Takip" },
  { href: "/satici-kayit", label: "Satıcı Ol" },
];

const socialSvgIcons = {
  "fab fa-facebook-f": (
    <svg viewBox="0 0 320 512" fill="currentColor" className="w-4 h-4 text-qblack"><path d="M80 299.3V512H196V299.3h86.5l18-97.8H196V136.9c0-51.7 20.7-71.5 72.7-71.5 16.3 0 29.4.4 37 1.2V7.9C291.4 4 256.4 0 236.2 0 129.3 0 80 50.5 80 159.4v42.1H0v97.8h80z"/></svg>
  ),
  "fab fa-instagram": (
    <svg viewBox="0 0 448 512" fill="currentColor" className="w-4 h-4 text-qblack"><path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/></svg>
  ),
  "fab fa-x-twitter": (
    <svg viewBox="0 0 512 512" fill="currentColor" className="w-4 h-4 text-qblack"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>
  ),
  "fab fa-twitter": ( // Alias for fab fa-twitter
    <svg viewBox="0 0 512 512" fill="currentColor" className="w-4 h-4 text-qblack"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>
  ),
  "fab fa-linkedin-in": (
    <svg viewBox="0 0 448 512" fill="currentColor" className="w-4 h-4 text-qblack"><path d="M100.3 448H7.4V148.9h92.9zM53.8 108.1C24.1 108.1 0 83.5 0 53.8a53.8 53.8 0 0 1 107.6 0c0 29.7-24.1 54.3-53.8 54.3zM447.9 448h-92.7V302.4c0-34.7-.7-79.2-48.3-79.2-48.3 0-55.7 37.7-55.7 76.7V448h-92.8V148.9h89.1v40.8h1.3c12.4-23.5 42.7-48.3 87.9-48.3 94 0 111.3 61.9 111.3 142.3V448z"/></svg>
  ),
  "fab fa-linkedin": ( // Alias for fab fa-linkedin
    <svg viewBox="0 0 448 512" fill="currentColor" className="w-4 h-4 text-qblack"><path d="M100.3 448H7.4V148.9h92.9zM53.8 108.1C24.1 108.1 0 83.5 0 53.8a53.8 53.8 0 0 1 107.6 0c0 29.7-24.1 54.3-53.8 54.3zM447.9 448h-92.7V302.4c0-34.7-.7-79.2-48.3-79.2-48.3 0-55.7 37.7-55.7 76.7V448h-92.8V148.9h89.1v40.8h1.3c12.4-23.5 42.7-48.3 87.9-48.3 94 0 111.3 61.9 111.3 142.3V448z"/></svg>
  ),
  "fab fa-youtube": (
    <svg viewBox="0 0 576 512" fill="currentColor" className="w-4 h-4 text-qblack"><path d="M549.7 124.1c-6.3-23.7-24.8-42.3-48.3-48.6C458.8 64 288 64 288 64S117.2 64 74.6 75.5c-23.5 6.3-42 24.9-48.3 48.6-11.4 42.9-11.4 132.3-11.4 132.3s0 89.4 11.4 132.3c6.3 23.7 24.8 41.5 48.3 47.8C117.2 448 288 448 288 448s170.8 0 213.4-11.5c23.5-6.3 42-24.2 48.3-47.8 11.4-42.9 11.4-132.3 11.4-132.3s0-89.4-11.4-132.3zm-317.5 213.5V175.2l142.7 81.2-142.7 81.2z"/></svg>
  ),
};

const fallbackSocialLinks = [
  {
    icon: "fab fa-facebook-f",
    link: "https://facebook.com/seyfibaba",
    label: "Facebook",
  },
  {
    icon: "fab fa-instagram",
    link: "https://instagram.com/seyfibaba",
    label: "Instagram",
  },
  {
    icon: "fab fa-x-twitter",
    link: "https://x.com/seyfibaba",
    label: "X",
  },
  {
    icon: "fab fa-linkedin-in",
    link: "https://linkedin.com/company/seyfibaba",
    label: "LinkedIn",
  },
  {
    icon: "fab fa-youtube",
    link: "https://youtube.com/@seyfibaba",
    label: "YouTube",
  },
];

export default function Footer({ settings, isSecondHandSite = false }) {
  // Get website setup data from Redux store
  const { websiteSetup } = useSelector((state) => state.websiteSetup);

  // Local state for footer content sections
  const [footerData, setFooterData] = useState({
    footerContent: null,
    socialLinks: null,
    firstColumn: null,
    secondColumn: null,
    thirdColumn: null,
  });

  // Initialize footer data from website setup
  useEffect(() => {
    if (websiteSetup?.payload) {
      const { payload } = websiteSetup;

      setFooterData({
        footerContent: payload.footer,
        socialLinks: payload.social_links,
        firstColumn: payload.footer_first_col,
        secondColumn: payload.footer_second_col,
        thirdColumn: payload.footer_third_col,
      });
    }
  }, [websiteSetup]);

  // Destructure footer data for easier access
  const { footerContent, socialLinks, firstColumn, secondColumn, thirdColumn } =
    footerData;
  const resolvedSocialLinks =
    socialLinks?.length > 0 ? socialLinks : fallbackSocialLinks;
  const filterFooterLinks = (links) =>
    Array.isArray(links)
      ? links
          .filter(
            (item) =>
              item?.link !== "/blogs" &&
              item?.link !== "/category-by-blogs" &&
              String(item?.title || "").trim().toLowerCase() !== "blog"
          )
          .map((item) => {
            const link = item?.link === "/become-seller" ? "/satici-kayit" : item?.link;
            const title =
              item?.link === "/become-seller" || String(item?.title || "").toLowerCase().includes("satıcı")
                ? "Satıcı Ol"
                : item?.title;

            return { ...item, link, title };
          })
      : [];

  if (isSecondHandSite) {
    return (
      <footer className="footer-section-wrapper bg-white print:hidden border-t border-gray-100">
        <div className="container-x mx-auto py-10">
          <div className="flex flex-col items-center gap-4 text-center">
            <p className="text-sm font-800 text-qblack">Seyfibaba İkinci El</p>
            <div className="flex flex-wrap justify-center gap-x-5 gap-y-2 text-sm text-qgray">
              <Link href="/" className="hover:text-qblack">İlanlar</Link>
              <Link href="/ikinci-el-sozlesmesi" className="hover:text-qblack">Sözleşme</Link>
              <Link href="/ikinci-el-kvkk" className="hover:text-qblack">KVKK</Link>
              <Link href={marketplaceUrl("/")} className="hover:text-qblack">Mağazaya git</Link>
            </div>
          </div>
        </div>
      </footer>
    );
  }

  return (
    <footer className="footer-section-wrapper bg-white print:hidden">
      <div className="container-x block mx-auto pt-[56px]">
        {/* Logo Section */}
        <div className="w-full flex flex-col items-center mb-[50px]">
          <div className="mb-[40px]">
            <Link href="/">
              {settings?.logo && (
                <div className="w-[153px] h-[44px] relative">
                  <Image
                    fill
                    sizes="100%"
                    style={{ objectFit: "scale-down" }}
                    {...getProductImageProps(settings.logo)}
                    alt="Seyfibaba logo"
                  />
                </div>
              )}
            </Link>
          </div>
          <div className="w-full h-[1px] bg-[#E9E9E9]"></div>
        </div>

        {/* Main Footer Content */}
        <div className="lg:flex justify-between mb-[50px]">
          {/* About Us Section */}
          <div className="lg:w-[424px] ml-0 w-full mb-10 lg:mb-0">
            <h2 className="text-[18px] font-500 text-[#2F2F2F] mb-5">Hakkımızda</h2>
            <p className="text-[#6B7280] text-[15px] w-[247px] leading-[28px]">
              {footerContent?.about_us}
            </p>
          </div>

          {/* Footer Links Columns */}
          <div className="flex-1 lg:flex lg:gap-8">
            {/* Yasal */}
            <div className="lg:w-1/2 w-full mb-10 lg:mb-0">
              <div className="mb-5">
                <h3 className="text-[18px] font-500 text-[#2F2F2F]">Yasal</h3>
              </div>
              <ul className="flex flex-col space-y-3">
                {FOOTER_LEGAL_LINKS.map((item) => (
                  <li key={item.slug}>
                    <Link href={legalPath(item.slug)}>
                      <span className="text-[#4B5563] text-[15px] hover:text-qblack border-b border-transparent hover:border-qblack cursor-pointer">
                        {item.label}
                      </span>
                    </Link>
                  </li>
                ))}
              </ul>
            </div>

            {/* Kurumsal */}
            <div className="lg:w-1/3 w-full mb-10 lg:mb-0">
              <div className="mb-5">
                <h3 className="text-[18px] font-500 text-[#2F2F2F]">Kurumsal</h3>
              </div>
              <ul className="flex flex-col space-y-4">
                {FOOTER_CORPORATE_LINKS.map((item) => (
                  <li key={item.href}>
                    <Link href={item.href}>
                      <span className="text-[#6B7280] text-[15px] hover:text-qblack border-b border-transparent hover:border-qblack cursor-pointer">
                        {item.label}
                      </span>
                    </Link>
                  </li>
                ))}
                {filterFooterLinks(firstColumn?.col_links).slice(0, 3).map((item, i) => (
                  <li key={`legacy-${i}`} className="hidden">
                    <Link href={item.link}>
                      <span>{item.title}</span>
                    </Link>
                  </li>
                ))}
              </ul>
            </div>

            {/* Legacy dynamic columns hidden — admin footer links deprecated for legal/corporate */}
            <div className="hidden">
            {/* First Column */}
            <div className="lg:w-1/3 w-full mb-10 lg:mb-0">
              {firstColumn && (
                <>
                  <div className="mb-5">
                    <h3 className="text-[18px] font-500 text-[#2F2F2F]">
                      {firstColumn.columnTitle}
                    </h3>
                  </div>
                  <div>
                    <ul className="flex flex-col space-y-4">
                      {filterFooterLinks(firstColumn.col_links).length > 0 &&
                        filterFooterLinks(firstColumn.col_links).map((item, i) => (
                          <li key={i}>
                              <Link href={item.link}>
                                <span className="text-[#4B5563] text-[15px] hover:text-qblack border-b border-transparent hover:border-qblack cursor-pointer capitalize">
                                  {item.title}
                                </span>
                              </Link>
                          </li>
                        ))}
                    </ul>
                  </div>
                </>
              )}
            </div>

            {/* Second Column */}
            <div className="lg:w-1/3 lg:flex lg:flex-col items-center w-full mb-10 lg:mb-0">
              <div>
                {secondColumn && (
                  <>
                    <div className="mb-5">
                      <h3 className="text-[18px] font-500 text-[#2F2F2F]">
                        {secondColumn.columnTitle}
                      </h3>
                    </div>
                    <div>
                      <ul className="flex flex-col space-y-4">
                        {filterFooterLinks(secondColumn.col_links).length > 0 &&
                          filterFooterLinks(secondColumn.col_links).map((item, i) => (
                            <li key={i}>
                              <Link href={item.link}>
                                <span className="text-[#6B7280] text-[15px] hover:text-qblack border-b border-transparent hover:border-qblack cursor-pointer capitalize">
                                  {item.title}
                                </span>
                              </Link>
                            </li>
                          ))}
                      </ul>
                    </div>
                  </>
                )}
              </div>
            </div>

            {/* Third Column */}
            <div className="lg:w-1/3 lg:flex lg:flex-col items-center w-full mb-10 lg:mb-0">
              <div>
                {thirdColumn && (
                  <>
                    <div className="mb-5">
                      <h3 className="text-[18px] font-500 text-[#2F2F2F]">
                        {thirdColumn.columnTitle}
                      </h3>
                    </div>
                    <div>
                      <ul className="flex flex-col space-y-4">
                        {filterFooterLinks(thirdColumn.col_links).length > 0 &&
                          filterFooterLinks(thirdColumn.col_links).map((item, i) => (
                            <li key={i}>
                              <Link href={item.link}>
                                <span className="text-[#6B7280] text-[15px] hover:text-qblack border-b border-transparent hover:border-qblack cursor-pointer capitalize">
                                  {item.title}
                                </span>
                              </Link>
                            </li>
                          ))}
                      </ul>
                    </div>
                  </>
                )}
              </div>
            </div>
            </div>
          </div>
        </div>

        {/* Bottom Bar - Copyright & Social Links */}
        <div className="bottom-bar border-t border-qgray-border lg:h-[82px] flex lg:flex-row flex-col-reverse justify-between items-center">
          {/* Social Links & Copyright */}
          <div className="flex rtl:space-x-reverse lg:space-x-5 space-x-2.5 justify-between items-center mb-3">
            <div className="flex rtl:space-x-reverse space-x-5 items-center">
              {/* Social Media Links */}
              {resolvedSocialLinks.map((item, i) => (
                  <a
                    key={i}
                    href={item.link}
                    target="_blank"
                    rel="noreferrer"
                    aria-label={item.label || 'Social Link'}
                    className="text-[#4B5563] hover:text-qblack transition-colors"
                  >
                    {socialSvgIcons[item.icon] || (
                      <FontAwesomeCom
                        className="w-4 h-4"
                        icon={item.icon}
                      />
                    )}
                  </a>
                ))}
            </div>

            {/* Copyright Text */}
            <span className="sm:text-base text-[10px] text-[#4B5563] font-300">
              {footerContent?.copyright || ""}
            </span>
          </div>

          {/* Payment Methods */}
          {footerContent?.payment_image && (
            <div className="mt-2 lg:mt-0">
              <div aria-hidden="true">
                <Image
                  width={318}
                  height={28}
                  src={`${appConfig.BASE_URL + footerContent.payment_image}`}
                  alt="Ödeme Yöntemleri"
                  loading="lazy"
                  style={{ height: "auto", maxWidth: "100%" }}
                />
              </div>
            </div>
          )}
        </div>
      </div>
    </footer>
  );
}
